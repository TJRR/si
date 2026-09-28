<?php

namespace App\Repositories;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Core\Auditoria;
use App\Core\Database;

/**
 * Fase 53: Anais de um evento (evento_anais, uma linha por evento), os
 * trabalhos que ficam de fora deles (evento_anais_exclusoes) e o ponteiro
 * de publicacao. O volume e' montado fora do servidor e chega como um unico
 * PDF; este repositorio nunca gera nem junta arquivo.
 *
 * Publicar e despublicar rodam cada um numa unica transacao de banco, com a
 * linha de evento_anais travada (FOR UPDATE): duas chamadas simultaneas se
 * serializam, a segunda enxerga o estado novo e falha sem gravar nada, e o
 * aviso aos autores so' sai para quem de fato publicou. O arquivo publico e'
 * copiado por quem chama ANTES da transacao (EventoAnaisService); se a
 * transacao falhar, nenhum Documento aponta para ele e quem chamou o apaga.
 */
class EventoAnaisRepository
{
    public const IDENTIFICADORES = ['nenhum', 'issn', 'isbn'];

    private $documentos;
    private $config;

    public function __construct()
    {
        $this->documentos = new EventoDocumentoRepository();
        $this->config = new TrabalhoConfigRepository();
    }

    public function buscarPorEvento($eventoId)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('SELECT * FROM evento_anais WHERE evento_id = :evento_id LIMIT 1');
        $stmt->execute(['evento_id' => $eventoId]);

        $anais = $stmt->fetch();

        return $anais !== false ? $anais : null;
    }

    /**
     * Upsert (evento_id e' chave unica). Quem chama decide o titulo: depois da
     * primeira publicacao ele nao muda mais (ver EventoAnaisService).
     */
    public function salvarDados($eventoId, array $dados)
    {
        $antes = $this->buscarPorEvento($eventoId);
        $pdo = Database::conexao();

        $valores = [
            'evento_id' => $eventoId,
            'titulo' => $dados['titulo'],
            'identificador_tipo' => $dados['identificador_tipo'],
            'identificador' => $dados['identificador'],
            'descricao' => $dados['descricao'],
            'mensagem_publicacao_html' => $dados['mensagem_publicacao_html'],
        ];

        if ($antes === null) {
            $stmt = $pdo->prepare(
                'INSERT INTO evento_anais (evento_id, titulo, identificador_tipo, identificador, descricao, mensagem_publicacao_html)
                 VALUES (:evento_id, :titulo, :identificador_tipo, :identificador, :descricao, :mensagem_publicacao_html)'
            );
        } else {
            $stmt = $pdo->prepare(
                'UPDATE evento_anais
                 SET titulo = :titulo, identificador_tipo = :identificador_tipo, identificador = :identificador,
                     descricao = :descricao, mensagem_publicacao_html = :mensagem_publicacao_html
                 WHERE evento_id = :evento_id'
            );
        }

        $stmt->execute($valores);

        $depois = $this->buscarPorEvento($eventoId);
        Auditoria::registrar('salvar', 'evento_anais', (int) $depois['id'], $antes, $valores);
    }

    public function estaPublicado($eventoId)
    {
        $anais = $this->buscarPorEvento($eventoId);

        return $anais !== null && !empty($anais['versao_publicada_id']);
    }

    /**
     * Mensagem do que impede a publicacao agora, ou null se nada impede.
     * Usada pela tela (para desabilitar o botao com a explicacao) e de novo
     * dentro da transacao de publicacao (a conferencia que vale).
     */
    public function motivoQueImpedePublicar($eventoId)
    {
        $anais = $this->buscarPorEvento($eventoId);

        if ($anais === null || trim((string) $anais['titulo']) === '') {
            return 'Salve o título dos Anais antes de publicar.';
        }

        $config = $this->config->buscarPorEvento($eventoId);

        if ($config === null || empty($config['resultado_publicado_em'])) {
            return 'O resultado de Trabalhos ainda não foi publicado. Publique-o na aba Resultado antes de publicar os Anais.';
        }

        return null;
    }

    /**
     * Publica a versao $versaoId numa transacao so': cria o Documento do
     * Evento do tipo "anais" ja publicado (desativando o anterior), marca a
     * versao e grava o ponteiro em evento_anais. $caminhoPublico e' o
     * arquivo ja copiado para a pasta publica, relativo a assets/.
     *
     * Devolve ['numero' => numero da versao, 'documento_id' => id do Documento].
     */
    public function publicarVersao($eventoId, $versaoId, $usuarioId, $caminhoPublico)
    {
        $pdo = Database::conexao();
        $pdo->beginTransaction();

        try {
            $stmt = $pdo->prepare('SELECT * FROM evento_anais WHERE evento_id = :evento_id LIMIT 1 FOR UPDATE');
            $stmt->execute(['evento_id' => $eventoId]);
            $anais = $stmt->fetch();

            if ($anais === false || trim((string) $anais['titulo']) === '') {
                throw new \RuntimeException('Salve o título dos Anais antes de publicar.');
            }

            // Trava compartilhada: se o resultado estiver sendo reaberto
            // agora, esta leitura espera e enxerga o estado novo.
            $stmt = $pdo->prepare('SELECT resultado_publicado_em FROM evento_trabalhos_config WHERE evento_id = :evento_id LIMIT 1 LOCK IN SHARE MODE');
            $stmt->execute(['evento_id' => $eventoId]);
            $config = $stmt->fetch();

            if ($config === false || empty($config['resultado_publicado_em'])) {
                throw new \RuntimeException('O resultado de Trabalhos ainda não foi publicado. Publique-o na aba Resultado antes de publicar os Anais.');
            }

            $stmt = $pdo->prepare('SELECT * FROM evento_anais_versoes WHERE id = :id AND evento_id = :evento_id LIMIT 1 FOR UPDATE');
            $stmt->execute(['id' => $versaoId, 'evento_id' => $eventoId]);
            $versao = $stmt->fetch();

            if ($versao === false) {
                throw new \RuntimeException('Versão dos Anais não encontrada.');
            }

            if (!empty($anais['versao_publicada_id']) && (int) $anais['versao_publicada_id'] === (int) $versaoId) {
                throw new \RuntimeException('Esta versão já está publicada.');
            }

            $tituloDocumento = !empty($anais['documento_titulo']) ? $anais['documento_titulo'] : $anais['titulo'];

            $documento = $this->documentos->inserirVersaoNaTransacaoAtual($eventoId, [
                'tipo' => EventoDocumentoRepository::TIPO_ANAIS,
                'titulo' => $tituloDocumento,
                'arquivo_path' => $caminhoPublico,
                'criado_por' => $usuarioId,
            ], true);

            $pdo->prepare('UPDATE evento_anais_versoes SET publicado_em = NOW(), documento_id = :documento_id WHERE id = :id')
                ->execute(['documento_id' => $documento['id'], 'id' => $versaoId]);

            $pdo->prepare(
                'UPDATE evento_anais
                 SET versao_publicada_id = :versao_id, publicado_em = NOW(), publicado_por = :usuario_id, documento_titulo = :documento_titulo
                 WHERE evento_id = :evento_id'
            )->execute([
                'versao_id' => $versaoId,
                'usuario_id' => $usuarioId,
                'documento_titulo' => $tituloDocumento,
                'evento_id' => $eventoId,
            ]);

            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();

            throw $e;
        }

        Auditoria::registrar('criar', 'evento_documentos', $documento['id'], null, $documento['registro']);
        Auditoria::registrar('publicar', 'evento_anais', (int) $anais['id'], [
            'versao_publicada_id' => $anais['versao_publicada_id'],
        ], [
            'evento_id' => (int) $eventoId,
            'versao_publicada_id' => (int) $versaoId,
            'versao_numero' => (int) $versao['numero'],
            'documento_id' => $documento['id'],
            'publicado_por' => $usuarioId,
        ]);

        return ['numero' => (int) $versao['numero'], 'documento_id' => $documento['id']];
    }

    /**
     * Tira os Anais do ar numa transacao so': o Documento vigente passa a
     * despublicado (o botao da pagina publica some) e o ponteiro e' limpo.
     * Nada e' apagado.
     */
    public function despublicar($eventoId, $usuarioId)
    {
        $pdo = Database::conexao();
        $pdo->beginTransaction();

        try {
            $stmt = $pdo->prepare('SELECT * FROM evento_anais WHERE evento_id = :evento_id LIMIT 1 FOR UPDATE');
            $stmt->execute(['evento_id' => $eventoId]);
            $anais = $stmt->fetch();

            if ($anais === false || empty($anais['versao_publicada_id'])) {
                throw new \RuntimeException('Os Anais não estão publicados.');
            }

            $stmt = $pdo->prepare('SELECT documento_id FROM evento_anais_versoes WHERE id = :id LIMIT 1');
            $stmt->execute(['id' => $anais['versao_publicada_id']]);
            $documentoId = $stmt->fetchColumn();

            if (!empty($documentoId)) {
                $pdo->prepare('UPDATE evento_documentos SET publicado = 0 WHERE id = :id AND evento_id = :evento_id')
                    ->execute(['id' => $documentoId, 'evento_id' => $eventoId]);
            }

            $pdo->prepare(
                'UPDATE evento_anais SET versao_publicada_id = NULL, publicado_em = NULL, publicado_por = NULL WHERE evento_id = :evento_id'
            )->execute(['evento_id' => $eventoId]);

            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();

            throw $e;
        }

        Auditoria::registrar('despublicar', 'evento_anais', (int) $anais['id'], [
            'versao_publicada_id' => $anais['versao_publicada_id'],
            'publicado_em' => $anais['publicado_em'],
        ], [
            'evento_id' => (int) $eventoId,
            'despublicado_por' => $usuarioId,
        ]);

        return true;
    }

    /**
     * Anais publicados, prontos para o participante (aplicativo, Meus
     * trabalhos), ou null. So' devolve se o Documento vigente continuar
     * ativo e publicado.
     */
    public function buscarPublicadoParaParticipante($eventoId)
    {
        $pdo = Database::conexao();

        // Recurso opcional na tela do participante (botao do aplicativo,
        // indicacao em Meus trabalhos): uma falha de banco aqui nunca pode
        // derrubar a tela inteira do participante. Fica no registro de erros.
        try {
            $stmt = $pdo->prepare(
                'SELECT a.titulo, a.identificador_tipo, a.identificador, a.descricao, a.publicado_em, d.arquivo_path
                 FROM evento_anais a
                 INNER JOIN evento_anais_versoes v ON v.id = a.versao_publicada_id
                 INNER JOIN evento_documentos d ON d.id = v.documento_id AND d.ativo = 1 AND d.publicado = 1
                 WHERE a.evento_id = :evento_id
                 LIMIT 1'
            );
            $stmt->execute(['evento_id' => $eventoId]);

            $anais = $stmt->fetch();
        } catch (\PDOException $e) {
            error_log('[Anais] falha ao buscar os Anais publicados do evento ' . (int) $eventoId . ': ' . $e->getMessage());

            return null;
        }

        if ($anais === false) {
            return null;
        }

        $anais['url'] = config('base_path') . '/assets/' . $anais['arquivo_path'];
        $anais['rotulo_identificador'] = self::rotuloIdentificador($anais);

        return $anais;
    }

    /**
     * "ISSN 0000-0000", "ISBN 978-..." ou texto vazio quando nao ha
     * identificador.
     */
    public static function rotuloIdentificador(array $anais)
    {
        if (empty($anais['identificador']) || !in_array($anais['identificador_tipo'], ['issn', 'isbn'], true)) {
            return '';
        }

        return strtoupper($anais['identificador_tipo']) . ' ' . $anais['identificador'];
    }

    /**
     * O trabalho consta nos Anais publicados? Sim quando ha versao publicada,
     * o trabalho esta aprovado e nao esta na lista de exclusoes.
     */
    public function trabalhoConstaNosAnais($eventoId, $trabalhoId)
    {
        $pdo = Database::conexao();

        // Mesma razao de buscarPublicadoParaParticipante(): recurso opcional
        // na tela do autor, nunca derruba a tela.
        try {
            $stmt = $pdo->prepare(
                'SELECT COUNT(*)
                 FROM evento_anais a
                 INNER JOIN trabalhos t ON t.evento_id = a.evento_id
                 WHERE a.evento_id = :evento_id AND a.versao_publicada_id IS NOT NULL
                   AND t.id = :trabalho_id AND t.status = \'aprovado\'
                   AND NOT EXISTS (SELECT 1 FROM evento_anais_exclusoes x WHERE x.trabalho_id = t.id)'
            );
            $stmt->execute(['evento_id' => $eventoId, 'trabalho_id' => $trabalhoId]);

            return (int) $stmt->fetchColumn() > 0;
        } catch (\PDOException $e) {
            error_log('[Anais] falha ao conferir o trabalho ' . (int) $trabalhoId . ' nos Anais: ' . $e->getMessage());

            return false;
        }
    }

    /**
     * Trabalhos aprovados que nao estao na lista de exclusoes: quem recebe o
     * aviso de publicacao e a indicacao nos Anais.
     */
    public function listarTrabalhosIncluidos($eventoId)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'SELECT t.id, t.titulo
             FROM trabalhos t
             WHERE t.evento_id = :evento_id AND t.status = \'aprovado\'
               AND NOT EXISTS (SELECT 1 FROM evento_anais_exclusoes x WHERE x.trabalho_id = t.id)
             ORDER BY t.id ASC'
        );
        $stmt->execute(['evento_id' => $eventoId]);

        return $stmt->fetchAll();
    }

    /**
     * Exclusoes do evento, indexadas pelo id do trabalho.
     */
    public function listarExclusoes($eventoId)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('SELECT * FROM evento_anais_exclusoes WHERE evento_id = :evento_id');
        $stmt->execute(['evento_id' => $eventoId]);

        $porTrabalho = [];

        foreach ($stmt->fetchAll() as $linha) {
            $porTrabalho[(int) $linha['trabalho_id']] = $linha;
        }

        return $porTrabalho;
    }

    /**
     * Substitui a lista de exclusoes do evento. $exclusoes: id do trabalho
     * para motivo (texto ou null). So' entram trabalhos aprovados DESTE
     * evento; qualquer outro id (POST manipulado) e' ignorado.
     */
    public function salvarExclusoes($eventoId, array $exclusoes, $usuarioId)
    {
        $pdo = Database::conexao();
        $antes = $this->listarExclusoes($eventoId);

        $stmt = $pdo->prepare('SELECT id FROM trabalhos WHERE evento_id = :evento_id AND status = \'aprovado\'');
        $stmt->execute(['evento_id' => $eventoId]);
        $aprovados = array_map('intval', $stmt->fetchAll(\PDO::FETCH_COLUMN));

        $pdo->beginTransaction();

        try {
            $pdo->prepare('DELETE FROM evento_anais_exclusoes WHERE evento_id = :evento_id')->execute(['evento_id' => $eventoId]);

            $insercao = $pdo->prepare(
                'INSERT INTO evento_anais_exclusoes (evento_id, trabalho_id, motivo, criado_por)
                 VALUES (:evento_id, :trabalho_id, :motivo, :criado_por)'
            );

            $gravadas = [];

            foreach ($exclusoes as $trabalhoId => $motivo) {
                $trabalhoId = (int) $trabalhoId;

                if (!in_array($trabalhoId, $aprovados, true)) {
                    continue;
                }

                $motivo = $motivo !== null && trim($motivo) !== '' ? trim($motivo) : null;

                $insercao->execute([
                    'evento_id' => $eventoId,
                    'trabalho_id' => $trabalhoId,
                    'motivo' => $motivo,
                    'criado_por' => $usuarioId,
                ]);

                $gravadas[$trabalhoId] = $motivo;
            }

            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();

            throw $e;
        }

        $motivosAntes = [];

        foreach ($antes as $trabalhoId => $linha) {
            $motivosAntes[$trabalhoId] = $linha['motivo'];
        }

        Auditoria::registrar('salvar_exclusoes', 'evento_anais_exclusoes', null, $motivosAntes, $gravadas);

        return count($gravadas);
    }
}
