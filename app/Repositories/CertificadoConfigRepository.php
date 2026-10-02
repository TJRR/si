<?php

namespace App\Repositories;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Core\Auditoria;
use App\Core\Database;

/**
 * Fase 59: configuracao dos Certificados do evento (migration 198), no molde
 * de GamificacaoConfigRepository. A linha nasce no primeiro salvamento da
 * tela Certificados, Configuracoes; evento sem linha equivale ao padrao
 * abaixo, resolvido aqui e nunca semeado em migration.
 *
 * Todo valor de regua nasce NULO: nulo significa "nao exige", e o sistema
 * nunca assume numero que o Administrador nao escreveu.
 */
class CertificadoConfigRepository
{
    const PADRAO = [
        'ativo' => 0,
        'min_atividades' => null,
        'min_dias' => null,
        'min_horas' => null,
        'exige_credenciamento' => 0,
        'carga_horaria_avaliador_horas' => null,
        'texto_evento_html' => null,
        'texto_atividade_html' => null,
        'texto_apresentacao_html' => null,
        'fundo_url' => null,
        'fundo_atividade_url' => null,
        'fundo_apresentacao_url' => null,
        'fundo_cor' => null,
        'fundo_atividade_cor' => null,
        'fundo_apresentacao_cor' => null,
        'emissao_liberada_em' => null,
        'emissao_liberada_por' => null,
    ];

    public function buscarPorEvento($eventoId)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('SELECT * FROM evento_certificado_config WHERE evento_id = :evento_id LIMIT 1');
        $stmt->execute(['evento_id' => (int) $eventoId]);

        $linha = $stmt->fetch();

        return $linha !== false ? $linha : null;
    }

    /**
     * Leitura protegida, para as telas do participante: falha de banco
     * (tabela ainda nao criada, por exemplo) devolve o padrao, com o modulo
     * desligado, em vez de derrubar o painel de todo inscrito. Mesma protecao
     * das quatro fases anteriores.
     */
    public function vigente($eventoId)
    {
        try {
            $linha = $this->buscarPorEvento($eventoId);
        } catch (\PDOException $e) {
            error_log('[Certificado] Falha ao ler a configuracao do evento ' . (int) $eventoId . ': ' . $e->getMessage());

            return self::PADRAO;
        }

        if ($linha === null) {
            return self::PADRAO;
        }

        return [
            'ativo' => (int) $linha['ativo'],
            'min_atividades' => $linha['min_atividades'] !== null ? (int) $linha['min_atividades'] : null,
            'min_dias' => $linha['min_dias'] !== null ? (int) $linha['min_dias'] : null,
            'min_horas' => $linha['min_horas'] !== null ? (int) $linha['min_horas'] : null,
            'exige_credenciamento' => (int) $linha['exige_credenciamento'],
            'carga_horaria_avaliador_horas' => $linha['carga_horaria_avaliador_horas'] !== null
                ? (int) $linha['carga_horaria_avaliador_horas']
                : null,
            'texto_evento_html' => $linha['texto_evento_html'],
            'texto_atividade_html' => $linha['texto_atividade_html'],
            'texto_apresentacao_html' => $linha['texto_apresentacao_html'],
            'fundo_url' => $linha['fundo_url'],
            'fundo_atividade_url' => $linha['fundo_atividade_url'],
            'fundo_apresentacao_url' => $linha['fundo_apresentacao_url'],
            'fundo_cor' => $linha['fundo_cor'],
            'fundo_atividade_cor' => $linha['fundo_atividade_cor'],
            'fundo_apresentacao_cor' => $linha['fundo_apresentacao_cor'],
            'emissao_liberada_em' => $linha['emissao_liberada_em'],
            'emissao_liberada_por' => $linha['emissao_liberada_por'] !== null ? (int) $linha['emissao_liberada_por'] : null,
        ];
    }

    public function estaAtivo($eventoId)
    {
        return $this->vigente($eventoId)['ativo'] === 1;
    }

    /**
     * Grava as opcoes da tela. NAO toca na liberacao da emissao, que tem
     * gravacao propria (definirLiberacao) com a trava dela.
     */
    public function salvar($eventoId, array $dados)
    {
        $antes = $this->buscarPorEvento($eventoId);
        $campos = [
            'evento_id' => (int) $eventoId,
            'ativo' => !empty($dados['ativo']) ? 1 : 0,
            'min_atividades' => $dados['min_atividades'] !== null ? (int) $dados['min_atividades'] : null,
            'min_dias' => $dados['min_dias'] !== null ? (int) $dados['min_dias'] : null,
            'min_horas' => $dados['min_horas'] !== null ? (int) $dados['min_horas'] : null,
            'exige_credenciamento' => !empty($dados['exige_credenciamento']) ? 1 : 0,
            'carga_horaria_avaliador_horas' => $dados['carga_horaria_avaliador_horas'] !== null
                ? (int) $dados['carga_horaria_avaliador_horas']
                : null,
            'texto_evento_html' => $dados['texto_evento_html'],
            'texto_atividade_html' => $dados['texto_atividade_html'],
            'texto_apresentacao_html' => $dados['texto_apresentacao_html'],
            'fundo_url' => $dados['fundo_url'],
            'fundo_atividade_url' => $dados['fundo_atividade_url'],
            'fundo_apresentacao_url' => $dados['fundo_apresentacao_url'],
            'fundo_cor' => $dados['fundo_cor'],
            'fundo_atividade_cor' => $dados['fundo_atividade_cor'],
            'fundo_apresentacao_cor' => $dados['fundo_apresentacao_cor'],
        ];

        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'INSERT INTO evento_certificado_config
                 (evento_id, ativo, min_atividades, min_dias, min_horas, exige_credenciamento,
                  carga_horaria_avaliador_horas, texto_evento_html, texto_atividade_html,
                  texto_apresentacao_html, fundo_url, fundo_atividade_url, fundo_apresentacao_url,
                  fundo_cor, fundo_atividade_cor, fundo_apresentacao_cor)
             VALUES (:evento_id, :ativo, :min_atividades, :min_dias, :min_horas, :exige_credenciamento,
                     :carga_horaria_avaliador_horas, :texto_evento_html, :texto_atividade_html,
                     :texto_apresentacao_html, :fundo_url, :fundo_atividade_url, :fundo_apresentacao_url,
                     :fundo_cor, :fundo_atividade_cor, :fundo_apresentacao_cor)
             ON DUPLICATE KEY UPDATE
                 ativo = VALUES(ativo),
                 min_atividades = VALUES(min_atividades),
                 min_dias = VALUES(min_dias),
                 min_horas = VALUES(min_horas),
                 exige_credenciamento = VALUES(exige_credenciamento),
                 carga_horaria_avaliador_horas = VALUES(carga_horaria_avaliador_horas),
                 texto_evento_html = VALUES(texto_evento_html),
                 texto_atividade_html = VALUES(texto_atividade_html),
                 texto_apresentacao_html = VALUES(texto_apresentacao_html),
                 fundo_url = VALUES(fundo_url),
                 fundo_atividade_url = VALUES(fundo_atividade_url),
                 fundo_apresentacao_url = VALUES(fundo_apresentacao_url),
                 fundo_cor = VALUES(fundo_cor),
                 fundo_atividade_cor = VALUES(fundo_atividade_cor),
                 fundo_apresentacao_cor = VALUES(fundo_apresentacao_cor)'
        );
        $stmt->execute($campos);

        Auditoria::registrar('salvar', 'evento_certificado_config', (int) $eventoId, $antes, $campos);
    }

    /**
     * Abre ou fecha a emissao para o participante. Diferente do encerramento
     * da gincana, esta chave vai e volta: fechar depois de aberta nao desfaz
     * nada, porque o certificado ja' emitido continua guardado e continua
     * sendo entregue a quem o tem.
     *
     * $instante nulo fecha. Nao confere o fim do evento: essa e' a outra
     * metade da trava, conferida em CertificadoElegibilidadeService, e vale
     * tambem para o Administrador.
     */
    public function definirLiberacao($eventoId, $instante, $usuarioId)
    {
        $antes = $this->buscarPorEvento($eventoId);
        $pdo = Database::conexao();

        if ($antes === null) {
            $stmt = $pdo->prepare(
                'INSERT INTO evento_certificado_config (evento_id, emissao_liberada_em, emissao_liberada_por)
                 VALUES (:evento_id, :instante, :usuario)'
            );
        } else {
            $stmt = $pdo->prepare(
                'UPDATE evento_certificado_config
                    SET emissao_liberada_em = :instante, emissao_liberada_por = :usuario
                  WHERE evento_id = :evento_id'
            );
        }

        $stmt->execute([
            'evento_id' => (int) $eventoId,
            'instante' => $instante,
            'usuario' => $instante !== null ? (int) $usuarioId : null,
        ]);

        Auditoria::registrar(
            $instante !== null ? 'liberar_certificados' : 'fechar_certificados',
            'evento_certificado_config',
            (int) $eventoId,
            $antes !== null ? ['emissao_liberada_em' => $antes['emissao_liberada_em']] : null,
            ['emissao_liberada_em' => $instante, 'definido_por' => (int) $usuarioId]
        );
    }
}
