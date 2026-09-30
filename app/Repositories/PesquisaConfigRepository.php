<?php

namespace App\Repositories;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Core\Auditoria;
use App\Core\Database;

/**
 * Fase 57: configuracao da pesquisa de satisfacao por evento, no molde de
 * DivulgacaoConfigRepository (Fase 56), inclusive a janela propria com as
 * duas datas em branco significando "use as datas do evento".
 *
 * Nao ha' coluna de pontos: quem credita quem responde e' um bonus
 * cadastrado do tipo responder_pesquisa. A pesquisa funciona igual sem esse
 * bonus, apenas sem creditar nada.
 */
class PesquisaConfigRepository
{
    const PADRAO = [
        'ativo' => 0,
        'data_inicio' => null,
        'data_fim' => null,
        'titulo' => null,
        'texto_abertura_html' => null,
        'convite_assunto' => null,
        'convite_corpo_html' => null,
        'convite_enviado_em' => null,
        'convite_comunicacao_id' => null,
    ];

    const TITULO_PADRAO = 'Pesquisa de satisfação';

    public function buscarPorEvento($eventoId)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('SELECT * FROM evento_pesquisa_config WHERE evento_id = :evento_id LIMIT 1');
        $stmt->execute(['evento_id' => $eventoId]);

        $linha = $stmt->fetch();

        return $linha !== false ? $linha : null;
    }

    /**
     * Recurso opcional da tela do participante: falha de banco devolve o
     * padrao desligado em vez de derrubar o painel de todo inscrito.
     */
    public function vigente($eventoId)
    {
        try {
            $linha = $this->buscarPorEvento($eventoId);
        } catch (\PDOException $e) {
            error_log('[Pesquisa] Falha ao ler a configuracao do evento ' . (int) $eventoId . ': ' . $e->getMessage());

            return self::PADRAO;
        }

        if ($linha === null) {
            return self::PADRAO;
        }

        return [
            'ativo' => (int) $linha['ativo'],
            'data_inicio' => $linha['data_inicio'],
            'data_fim' => $linha['data_fim'],
            'titulo' => $linha['titulo'],
            'texto_abertura_html' => $linha['texto_abertura_html'],
            'convite_assunto' => $linha['convite_assunto'],
            'convite_corpo_html' => $linha['convite_corpo_html'],
            'convite_enviado_em' => $linha['convite_enviado_em'],
            'convite_comunicacao_id' => $linha['convite_comunicacao_id'] !== null ? (int) $linha['convite_comunicacao_id'] : null,
        ];
    }

    public function estaAtivo($eventoId)
    {
        $config = $this->vigente($eventoId);

        return $config['ativo'] === 1;
    }

    public function tituloDe(array $config)
    {
        $titulo = isset($config['titulo']) ? trim((string) $config['titulo']) : '';

        return $titulo !== '' ? $titulo : self::TITULO_PADRAO;
    }

    public function salvar($eventoId, array $dados)
    {
        $antes = $this->buscarPorEvento($eventoId);
        $campos = [
            'evento_id' => $eventoId,
            'ativo' => !empty($dados['ativo']) ? 1 : 0,
            'data_inicio' => $this->dataOuNulo(isset($dados['data_inicio']) ? $dados['data_inicio'] : null),
            'data_fim' => $this->dataOuNulo(isset($dados['data_fim']) ? $dados['data_fim'] : null),
            'titulo' => $this->textoOuNulo(isset($dados['titulo']) ? $dados['titulo'] : null, 150),
            'texto_abertura_html' => $this->htmlOuNulo(isset($dados['texto_abertura_html']) ? $dados['texto_abertura_html'] : null),
            'convite_assunto' => $this->textoOuNulo(isset($dados['convite_assunto']) ? $dados['convite_assunto'] : null, 200),
            'convite_corpo_html' => $this->htmlOuNulo(isset($dados['convite_corpo_html']) ? $dados['convite_corpo_html'] : null),
        ];

        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'INSERT INTO evento_pesquisa_config
                 (evento_id, ativo, data_inicio, data_fim, titulo, texto_abertura_html, convite_assunto, convite_corpo_html)
             VALUES (:evento_id, :ativo, :data_inicio, :data_fim, :titulo, :texto_abertura_html, :convite_assunto, :convite_corpo_html)
             ON DUPLICATE KEY UPDATE ativo = VALUES(ativo), data_inicio = VALUES(data_inicio),
                 data_fim = VALUES(data_fim), titulo = VALUES(titulo),
                 texto_abertura_html = VALUES(texto_abertura_html),
                 convite_assunto = VALUES(convite_assunto), convite_corpo_html = VALUES(convite_corpo_html)'
        );
        $stmt->execute($campos);

        Auditoria::registrar('salvar', 'evento_pesquisa_config', (int) $eventoId, $antes, $campos);
    }

    /**
     * Guarda quando o convite saiu e qual campanha o levou. E' por estas duas
     * colunas que a tela descobre o ultimo convite: evento_comunicacoes.tipo
     * e' um ENUM de tres valores, e criar um tipo proprio exigiria alterar
     * ENUM existente, o que a regra do projeto proibe.
     */
    public function registrarConvite($eventoId, $comunicacaoId)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'UPDATE evento_pesquisa_config
                SET convite_enviado_em = NOW(), convite_comunicacao_id = :comunicacao
              WHERE evento_id = :evento'
        );
        $stmt->execute(['comunicacao' => (int) $comunicacaoId, 'evento' => (int) $eventoId]);

        Auditoria::registrar('convidar_pesquisa', 'evento_pesquisa_config', (int) $eventoId, null, ['comunicacao_id' => (int) $comunicacaoId]);
    }

    private function dataOuNulo($valor)
    {
        $valor = is_string($valor) ? trim($valor) : '';

        if ($valor === '') {
            return null;
        }

        return preg_match('/^\d{4}-\d{2}-\d{2}$/', $valor) === 1 ? $valor : null;
    }

    private function textoOuNulo($valor, $limite)
    {
        $valor = is_string($valor) ? trim($valor) : '';

        return $valor !== '' ? mb_substr($valor, 0, $limite) : null;
    }

    private function htmlOuNulo($valor)
    {
        $valor = is_string($valor) ? trim($valor) : '';

        return $valor !== '' ? $valor : null;
    }
}
