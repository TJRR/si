<?php

namespace App\Repositories;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Core\Auditoria;
use App\Core\Database;

/**
 * Fase 17 (Bug 2): renomeado de TemaDesafioRepository - a tabela "temas_desafios"
 * virou "temas" (RENAME TABLE, migration 055), agora so' o nivel superior da
 * hierarquia. O nivel "Desafio" (filho de Tema) vive em DesafioRepository.
 */
class TemaRepository
{
    /**
     * Fase 18 (3.8) - set pre-definido de icones tematicos (nao upload
     * livre de arquivo). Chave = valor gravado em temas.icone; usado tanto
     * pelo <select> do admin quanto pela home publica pra escolher o SVG.
     */
    public const ICONES_DISPONIVEIS = [
        'sustentabilidade' => 'Sustentabilidade',
        'acessibilidade' => 'Acessibilidade',
        'inovacao' => 'Inovação',
        'tecnologia' => 'Tecnologia',
        'saude' => 'Saúde',
        'educacao' => 'Educação',
        'seguranca' => 'Segurança',
        'comunidade' => 'Comunidade',
        'gestao' => 'Gestão/Processos',
        'dados' => 'Dados/Indicadores',
        'automacao' => 'Automação',
        'juridico' => 'Jurídico/Judicial',
        'atendimento' => 'Atendimento ao Cidadão',
        'pessoas' => 'Gestão de Pessoas',
        'financeiro' => 'Financeiro/Orçamento',
        'patrimonio' => 'Patrimônio/Ativos',
        'infraestrutura' => 'Infraestrutura de TI',
        'comunicacao' => 'Comunicação',
        'qualidade' => 'Qualidade',
        'transparencia' => 'Transparência',
        'inteligencia_artificial' => 'Inteligência Artificial',
        'documentos' => 'Documentos/Processos',
        'tempo' => 'Eficiência de Tempo',
        'viagem' => 'Viagens/Deslocamento',
    ];

    /**
     * Fase 19 (#104): glifo + cor de fundo do "badge" de cada icone tematico
     * - cor fixa por icone (decisao do usuario), o Admin so' escolhe o icone
     * no <select> de ICONES_DISPONIVEIS, nunca uma cor avulsa. 24 opcoes
     * (ampliado de 8 na Fase 19, achavam pouco).
     */
    public const ICONES_VISUAL = [
        'sustentabilidade' => ['glifo' => '🌱', 'cor' => '#D6F5E3'],
        'acessibilidade' => ['glifo' => '♿', 'cor' => '#E3D6F5'],
        'inovacao' => ['glifo' => '💡', 'cor' => '#FFE9C7'],
        'tecnologia' => ['glifo' => '💻', 'cor' => '#D6E8F5'],
        'saude' => ['glifo' => '⚕️', 'cor' => '#FFD6E0'],
        'educacao' => ['glifo' => '📚', 'cor' => '#FFF4C7'],
        'seguranca' => ['glifo' => '🔒', 'cor' => '#D6D6F5'],
        'comunidade' => ['glifo' => '🤝', 'cor' => '#FFDDC7'],
        'gestao' => ['glifo' => '⚙️', 'cor' => '#E8E3D6'],
        'dados' => ['glifo' => '📊', 'cor' => '#C7F0EE'],
        'automacao' => ['glifo' => '🤖', 'cor' => '#C7D2F5'],
        'juridico' => ['glifo' => '⚖️', 'cor' => '#F5EAC7'],
        'atendimento' => ['glifo' => '🎧', 'cor' => '#C7E9F5'],
        'pessoas' => ['glifo' => '👥', 'cor' => '#F5D6E3'],
        'financeiro' => ['glifo' => '💰', 'cor' => '#C7F5D9'],
        'patrimonio' => ['glifo' => '🏢', 'cor' => '#E8D9C7'],
        'infraestrutura' => ['glifo' => '🏗️', 'cor' => '#FFDFC0'],
        'comunicacao' => ['glifo' => '📣', 'cor' => '#F5C7EA'],
        'qualidade' => ['glifo' => '✅', 'cor' => '#D0F5C7'],
        'transparencia' => ['glifo' => '🔍', 'cor' => '#C7F5F0'],
        'inteligencia_artificial' => ['glifo' => '🧠', 'cor' => '#E0C7F5'],
        'documentos' => ['glifo' => '📄', 'cor' => '#F0E8D6'],
        'tempo' => ['glifo' => '⏱️', 'cor' => '#F5E3C7'],
        'viagem' => ['glifo' => '✈️', 'cor' => '#C7DCF5'],
    ];

    public function listarPorTrilha($trilhaId)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('SELECT * FROM temas WHERE trilha_id = :trilha_id ORDER BY ordem ASC, nome ASC');
        $stmt->execute(['trilha_id' => $trilhaId]);

        return $stmt->fetchAll();
    }

    public function listarAtivosPorTrilha($trilhaId)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'SELECT * FROM temas WHERE trilha_id = :trilha_id AND ativo = 1 ORDER BY ordem ASC, nome ASC'
        );
        $stmt->execute(['trilha_id' => $trilhaId]);

        return $stmt->fetchAll();
    }

    public function buscarPorId($id)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('SELECT * FROM temas WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);

        $tema = $stmt->fetch();

        return $tema !== false ? $tema : null;
    }

    public function criar($trilhaId, $nome, $descricaoLonga, $ativo, $icone = null, $ordem = 0)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'INSERT INTO temas (trilha_id, nome, descricao_longa, ativo, icone, ordem)
             VALUES (:trilha_id, :nome, :descricao_longa, :ativo, :icone, :ordem)'
        );
        $dados = [
            'trilha_id' => $trilhaId,
            'nome' => $nome,
            'descricao_longa' => $descricaoLonga !== '' ? $descricaoLonga : null,
            'ativo' => $ativo,
            'icone' => $icone,
            'ordem' => $ordem,
        ];
        $stmt->execute($dados);
        $id = (int) $pdo->lastInsertId();

        Auditoria::registrar('criar', 'temas', $id, null, $dados);

        return $id;
    }

    public function atualizar($id, $nome, $descricaoLonga, $ativo, $icone = null, $ordem = 0)
    {
        $antes = $this->buscarPorId($id);
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'UPDATE temas SET nome = :nome, descricao_longa = :descricao_longa, ativo = :ativo, icone = :icone, ordem = :ordem WHERE id = :id'
        );
        $depois = [
            'nome' => $nome,
            'descricao_longa' => $descricaoLonga !== '' ? $descricaoLonga : null,
            'ativo' => $ativo,
            'icone' => $icone,
            'ordem' => $ordem,
        ];
        $stmt->execute($depois + ['id' => $id]);

        Auditoria::registrar('atualizar', 'temas', $id, $antes, $depois);
    }

    /**
     * Remocao real (sem soft-delete) — a FK de "desafios.tema_id" (sem
     * CASCADE) ja protege contra remover um tema com desafios cadastrados.
     */
    public function remover($id)
    {
        $antes = $this->buscarPorId($id);
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('DELETE FROM temas WHERE id = :id');
        $stmt->execute(['id' => $id]);

        Auditoria::registrar('remover', 'temas', $id, $antes, null);
    }

    /**
     * Fase 19 (#102): reordenacao em lote por arrastar/botoes, mesmo padrao
     * de BlocoConteudoRepository::reordenar()/SlideRepository::reordenar().
     */
    public function reordenar($trilhaId, array $ids)
    {
        $pdo = Database::conexao();
        $pdo->beginTransaction();

        try {
            $stmt = $pdo->prepare('UPDATE temas SET ordem = :ordem WHERE id = :id AND trilha_id = :trilha_id');

            foreach ($ids as $indice => $id) {
                $stmt->execute(['ordem' => $indice, 'id' => (int) $id, 'trilha_id' => $trilhaId]);
            }

            $pdo->commit();
            Auditoria::registrar('reordenar', 'temas', null, null, ['trilha_id' => $trilhaId, 'ids' => $ids]);
        } catch (\Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
    }
}
