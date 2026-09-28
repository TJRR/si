<?php

namespace App\Repositories;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Core\Auditoria;
use App\Core\Database;

/**
 * Fase 54: dados da montagem automatica dos Anais de um evento
 * (evento_anais_montagem, uma linha por evento): textos editoriais, capa
 * enviada e prazo do PDF final dos autores. A linha tambem serve de trava
 * por evento para o pedido de geracao (EventoAnaisGeracaoRepository).
 */
class EventoAnaisMontagemRepository
{
    public function buscarPorEvento($eventoId)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('SELECT * FROM evento_anais_montagem WHERE evento_id = :evento_id LIMIT 1');
        $stmt->execute(['evento_id' => $eventoId]);

        $montagem = $stmt->fetch();

        return $montagem !== false ? $montagem : null;
    }

    /**
     * Cria a linha do evento se ainda nao existir. Sem INSERT IGNORE de
     * proposito: ele esconderia tambem uma falha de chave estrangeira.
     */
    public function garantirLinha($eventoId)
    {
        $pdo = Database::conexao();
        $pdo->prepare(
            'INSERT INTO evento_anais_montagem (evento_id) VALUES (:evento_id)
             ON DUPLICATE KEY UPDATE evento_id = evento_id'
        )->execute(['evento_id' => $eventoId]);
    }

    public function salvarEditorial($eventoId, array $dados)
    {
        $antes = $this->buscarPorEvento($eventoId);
        $this->garantirLinha($eventoId);

        $valores = [
            'subtitulo' => $dados['subtitulo'],
            'local_ano' => $dados['local_ano'],
            'organizadores_html' => $dados['organizadores_html'],
            'ficha_catalografica_html' => $dados['ficha_catalografica_html'],
            'expediente_html' => $dados['expediente_html'],
            'apresentacao_html' => $dados['apresentacao_html'],
        ];

        $pdo = Database::conexao();
        $pdo->prepare(
            'UPDATE evento_anais_montagem
             SET subtitulo = :subtitulo, local_ano = :local_ano, organizadores_html = :organizadores_html,
                 ficha_catalografica_html = :ficha_catalografica_html, expediente_html = :expediente_html,
                 apresentacao_html = :apresentacao_html
             WHERE evento_id = :evento_id'
        )->execute($valores + ['evento_id' => $eventoId]);

        $depois = $this->buscarPorEvento($eventoId);
        Auditoria::registrar('salvar_editorial', 'evento_anais_montagem', (int) $depois['id'], $antes, $valores);
    }

    /**
     * $prazo ja convertido para 'Y-m-d H:i:s' (ou null, que fecha o envio).
     */
    public function salvarPrazo($eventoId, $prazo, $instrucoesHtml, $mensagemHtml)
    {
        $antes = $this->buscarPorEvento($eventoId);
        $this->garantirLinha($eventoId);

        $valores = [
            'prazo_pdf_final' => $prazo,
            'instrucoes_pdf_final_html' => $instrucoesHtml,
            'mensagem_aviso_pdf_final_html' => $mensagemHtml,
        ];

        $pdo = Database::conexao();
        $pdo->prepare(
            'UPDATE evento_anais_montagem
             SET prazo_pdf_final = :prazo_pdf_final, instrucoes_pdf_final_html = :instrucoes_pdf_final_html,
                 mensagem_aviso_pdf_final_html = :mensagem_aviso_pdf_final_html
             WHERE evento_id = :evento_id'
        )->execute($valores + ['evento_id' => $eventoId]);

        $depois = $this->buscarPorEvento($eventoId);
        Auditoria::registrar('salvar_prazo_pdf_final', 'evento_anais_montagem', (int) $depois['id'], [
            'prazo_pdf_final' => $antes !== null ? $antes['prazo_pdf_final'] : null,
        ], $valores);
    }

    /**
     * Grava a capa nova e devolve o caminho da capa anterior (ou null), para
     * quem chamou apagar o arquivo antigo so' depois da gravacao confirmada.
     */
    public function gravarCapa($eventoId, $caminho, $nomeOriginal)
    {
        return $this->trocarCapa($eventoId, $caminho, $nomeOriginal, 'enviar_capa');
    }

    /**
     * Tira a capa e devolve o caminho do arquivo que estava gravado (ou null).
     */
    public function removerCapa($eventoId)
    {
        return $this->trocarCapa($eventoId, null, null, 'remover_capa');
    }

    private function trocarCapa($eventoId, $caminho, $nomeOriginal, $acao)
    {
        $this->garantirLinha($eventoId);

        $pdo = Database::conexao();
        $pdo->beginTransaction();

        try {
            $stmt = $pdo->prepare('SELECT id, capa_path FROM evento_anais_montagem WHERE evento_id = :evento_id LIMIT 1 FOR UPDATE');
            $stmt->execute(['evento_id' => $eventoId]);
            $linha = $stmt->fetch();

            $pdo->prepare(
                'UPDATE evento_anais_montagem SET capa_path = :capa_path, capa_nome_original = :capa_nome_original WHERE evento_id = :evento_id'
            )->execute([
                'capa_path' => $caminho,
                'capa_nome_original' => $nomeOriginal,
                'evento_id' => $eventoId,
            ]);

            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();

            throw $e;
        }

        $anterior = $linha !== false && !empty($linha['capa_path']) ? $linha['capa_path'] : null;

        Auditoria::registrar($acao, 'evento_anais_montagem', $linha !== false ? (int) $linha['id'] : null, [
            'capa_path' => $anterior,
        ], [
            'capa_path' => $caminho,
            'capa_nome_original' => $nomeOriginal,
        ]);

        return $anterior;
    }
}
