<?php

namespace App\Repositories;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Core\Auditoria;
use App\Core\Database;

/**
 * Fase 49: configuracao do processo de Trabalhos de um evento, 1:1 com
 * eventos. Toda regra de negocio especifica de uma edicao mora aqui como
 * coluna configuravel, nunca constante fixa em codigo (ver auto-auditoria
 * de genericidade no plano da fase). buscarPorEventoParaAtualizar() usa
 * FOR UPDATE e so' deve ser chamado de dentro de uma transacao ja aberta
 * pelo chamador (TrabalhoSubmissaoService), mesmo padrao de
 * EventoAtividadeInscricaoRepository::inscrever() para serializar
 * submissoes concorrentes do mesmo evento.
 */
class TrabalhoConfigRepository
{
    public function buscarPorEvento($eventoId)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('SELECT * FROM evento_trabalhos_config WHERE evento_id = :evento_id LIMIT 1');
        $stmt->execute(['evento_id' => $eventoId]);

        $config = $stmt->fetch();

        return $config !== false ? $config : null;
    }

    /**
     * So' chamar dentro de uma transacao ja aberta pelo chamador - trava a
     * linha de configuracao do evento para o restante da transacao,
     * serializando qualquer submissao/checagem de duplicidade concorrente
     * daquele evento.
     */
    public function buscarPorEventoParaAtualizar($eventoId)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('SELECT * FROM evento_trabalhos_config WHERE evento_id = :evento_id LIMIT 1 FOR UPDATE');
        $stmt->execute(['evento_id' => $eventoId]);

        $config = $stmt->fetch();

        return $config !== false ? $config : null;
    }

    /**
     * Upsert (UNIQUE evento_id garante 1 registro por evento) - usado pela
     * tela de Configuracoes de Trabalhos, sempre grava o conjunto completo
     * de campos.
     */
    public function salvar($eventoId, array $dados)
    {
        $antes = $this->buscarPorEvento($eventoId);
        $pdo = Database::conexao();

        $campos = [
            'data_abertura_submissao', 'data_fim_submissao', 'data_inicio_avaliacao', 'data_fim_avaliacao',
            'quantidade_maxima_autores', 'permite_multiplos_trabalhos_por_pessoa',
            'quantidade_avaliadores_por_trabalho', 'sigilo_cego', 'metodo_agregacao_nota',
            'metodos_submissao_json', 'extensoes_editavel_json', 'tamanho_maximo_mb',
            'exige_telefone_contato', 'nota_corte_aprovacao', 'regra_selecao_tipo',
            'regra_selecao_valor', 'status',
            // Reabertura da Fase 51: inscricao automatica dos autores ao
            // submeter e texto editavel do e-mail de recebimento.
            'inscrever_autores_ao_submeter', 'mensagem_recebimento_html',
        ];

        $colunas = implode(', ', $campos);
        $marcadores = ':' . implode(', :', $campos);
        $atualizacoes = implode(', ', array_map(function ($campo) {
            return "{$campo} = VALUES({$campo})";
        }, $campos));

        $stmt = $pdo->prepare(
            "INSERT INTO evento_trabalhos_config (evento_id, {$colunas})
             VALUES (:evento_id, {$marcadores})
             ON DUPLICATE KEY UPDATE {$atualizacoes}"
        );

        $parametros = ['evento_id' => $eventoId];
        foreach ($campos as $campo) {
            $parametros[$campo] = isset($dados[$campo]) ? $dados[$campo] : null;
        }

        $stmt->execute($parametros);

        $depois = $this->buscarPorEvento($eventoId);
        Auditoria::registrar('salvar', 'evento_trabalhos_config', (int) $depois['id'], $antes, $depois);

        return (int) $depois['id'];
    }

    /**
     * Fase 49B, achado do usuário: a tela "Critérios de avaliação" edita
     * só este campo, nunca o conjunto completo de Configurações -
     * salvar() (upsert completo) apagaria prazos/métodos/etc. se chamado
     * só com este dado. Upsert cirúrgico: cria a linha com o mínimo
     * necessário se ainda não existir nenhuma configuração para o
     * evento, ou atualiza só esta coluna se já existir.
     */
    public function atualizarResumoCriterios($eventoId, $html)
    {
        $pdo = Database::conexao();
        $antes = $this->buscarPorEvento($eventoId);

        if ($antes === null) {
            $stmt = $pdo->prepare(
                'INSERT INTO evento_trabalhos_config (evento_id, metodos_submissao_json, criterios_resumo_html)
                 VALUES (:evento_id, :metodos_submissao_json, :criterios_resumo_html)'
            );
            $stmt->execute(['evento_id' => $eventoId, 'metodos_submissao_json' => '[]', 'criterios_resumo_html' => $html]);
        } else {
            $stmt = $pdo->prepare('UPDATE evento_trabalhos_config SET criterios_resumo_html = :criterios_resumo_html WHERE evento_id = :evento_id');
            $stmt->execute(['evento_id' => $eventoId, 'criterios_resumo_html' => $html]);
        }

        $depois = $this->buscarPorEvento($eventoId);
        Auditoria::registrar('salvar', 'evento_trabalhos_config', (int) $depois['id'], $antes, ['criterios_resumo_html' => $html]);
    }

    public function metodosHabilitados($eventoId)
    {
        $config = $this->buscarPorEvento($eventoId);

        if ($config === null || $config['metodos_submissao_json'] === null) {
            return [];
        }

        $metodos = json_decode($config['metodos_submissao_json'], true);

        return is_array($metodos) ? $metodos : [];
    }

    public function extensoesEditavelHabilitadas($eventoId)
    {
        $config = $this->buscarPorEvento($eventoId);

        if ($config === null || $config['extensoes_editavel_json'] === null) {
            return [];
        }

        $extensoes = json_decode($config['extensoes_editavel_json'], true);

        return is_array($extensoes) ? $extensoes : [];
    }
}
