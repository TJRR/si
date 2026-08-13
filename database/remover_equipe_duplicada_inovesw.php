<?php

/**
 * Remocao pontual (nao generica) da equipe duplicada "InoveSW" id 106 -
 * casca criada pela importacao inicial (Fase 3) porque a mesma equipe
 * respondeu os DOIS Google Forms de inscricao (Externa e Interna) em
 * 02/07/2026 e as duas linhas sobreviveram a curadoria das planilhas
 * (ext_homologado.csv linha 42, int_homologado.csv linha 31). A equipe
 * real e' a id 41 (trilha Externa), que detem a submissao avaliavel da
 * Etapa 1 e esta' sob avaliacao. A investigacao de 13/08/2026 confirmou em
 * producao que a 106 nao tem NENHUM vinculo com avaliacao (zero
 * designacoes, notas, resultados de etapa, feedback, resultado de trilha) -
 * ver RelatorioEquipeDuplicadaInoveSW.md na raiz do repositorio NPI.
 *
 * Motivo tecnico de remover (alem da consistencia do dado):
 * EquipeRepository::buscarPorParticipante() resolve "a equipe do
 * participante" com LIMIT 1 sem ORDER BY - enquanto a 106 existir, a tela
 * do participante e a gravacao de submissao nova dos participantes 104/105
 * podem resolver para a equipe errada de forma nao deterministica.
 *
 * O script e' deliberadamente TRAVADO neste caso especifico: confere o
 * estado exato documentado na investigacao (nomes, trilhas, membros,
 * submissao unica de cadastro, zero vinculos de avaliacao, zero reservas de
 * mentoria/oficina e zero duvidas apontando para a 106) e SE RECUSA a
 * aplicar se qualquer detalhe divergir - nesse caso nada e' alterado e o
 * desvio e' reportado na tela para nova analise.
 *
 * O que e' removido (nesta ordem, em transacao unica):
 *   1. linhas de submissao_cpfs da submissao historica de cadastro
 *   2. a propria submissao historica ("Cadastro das Equipes", aba INT_homologado)
 *   3. os 2 vinculos em equipe_participante (participantes 104 e 105)
 *   4. a equipe 106
 *
 * Tudo registrado em log_auditoria (acao "excluir", visivel na tela
 * Auditoria do sistema). participantes, usuarios e perfis NAO sao tocados -
 * as mesmas pessoas continuam integralmente vinculadas a equipe real 41.
 *
 * Uso:
 *   php database/remover_equipe_duplicada_inovesw.php             (dry-run, so confere e mostra)
 *   php database/remover_equipe_duplicada_inovesw.php --confirmar (aplica de verdade)
 */

if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    exit('Acesso negado: este script so pode ser executado via linha de comando.');
}

define('SI_BOOT', true);

require __DIR__ . '/../vendor/autoload.php';

use App\Core\Auditoria;
use App\Core\Database;

// Estado esperado, documentado na investigacao de 13/08/2026. Qualquer
// divergencia em relacao a isso aborta o script sem alterar nada.
const EQUIPE_CASCA_ID = 106;   // trilha Interna, sem submissao avaliavel
const EQUIPE_REAL_ID = 41;     // trilha Externa, sob avaliacao - NAO e' tocada
const NOME_EQUIPE = 'InoveSW';
const TRILHA_CASCA_ID = 3;
const TRILHA_REAL_ID = 2;
const PARTICIPANTES_ESPERADOS = [104, 105];

function linha($ch = '-')
{
    echo str_repeat($ch, 78) . "\n";
}

$confirmar = in_array('--confirmar', $argv, true);
$pdo = Database::conexao();
$problemas = [];

echo "\n";
linha('=');
echo "Remocao da equipe duplicada \"" . NOME_EQUIPE . "\" (id " . EQUIPE_CASCA_ID . ") - verificacao previa\n";
linha('=');

// ---------------------------------------------------------------------
// 1) As duas equipes existem e sao exatamente as documentadas.
// ---------------------------------------------------------------------
$stmt = $pdo->prepare('SELECT * FROM equipes WHERE id = :id');
$stmt->execute(['id' => EQUIPE_CASCA_ID]);
$casca = $stmt->fetch();

$stmt = $pdo->prepare('SELECT * FROM equipes WHERE id = :id');
$stmt->execute(['id' => EQUIPE_REAL_ID]);
$real = $stmt->fetch();

if ($casca === false) {
    $problemas[] = 'Equipe ' . EQUIPE_CASCA_ID . ' nao existe (ja removida?).';
} else {
    if ($casca['nome_equipe'] !== NOME_EQUIPE || (int) $casca['trilha_id'] !== TRILHA_CASCA_ID) {
        $problemas[] = "Equipe " . EQUIPE_CASCA_ID . " nao confere: nome '{$casca['nome_equipe']}', trilha_id {$casca['trilha_id']} (esperado '" . NOME_EQUIPE . "', trilha " . TRILHA_CASCA_ID . ").";
    }
}

if ($real === false) {
    $problemas[] = 'Equipe real ' . EQUIPE_REAL_ID . ' nao encontrada - NUNCA rode este script sem ela existir.';
} else {
    if ($real['nome_equipe'] !== NOME_EQUIPE || (int) $real['trilha_id'] !== TRILHA_REAL_ID) {
        $problemas[] = "Equipe real " . EQUIPE_REAL_ID . " nao confere: nome '{$real['nome_equipe']}', trilha_id {$real['trilha_id']} (esperado '" . NOME_EQUIPE . "', trilha " . TRILHA_REAL_ID . ").";
    }
}

echo "Equipe casca (" . EQUIPE_CASCA_ID . "): " . ($casca !== false ? "'{$casca['nome_equipe']}', trilha_id {$casca['trilha_id']}" : 'NAO ENCONTRADA') . "\n";
echo "Equipe real  (" . EQUIPE_REAL_ID . "): " . ($real !== false ? "'{$real['nome_equipe']}', trilha_id {$real['trilha_id']}" : 'NAO ENCONTRADA') . "\n";

// ---------------------------------------------------------------------
// 2) Vinculos da casca sao exatamente os 2 participantes esperados, e
//    cada um deles tem vinculo homologado tambem na equipe real (garantia
//    de que ninguem perde acesso ao remover a casca).
// ---------------------------------------------------------------------
$vinculosCasca = [];

if ($casca !== false) {
    $stmt = $pdo->prepare(
        'SELECT ep.*, p.nome
         FROM equipe_participante ep
         JOIN participantes p ON p.id = ep.participante_id
         WHERE ep.equipe_id = :id'
    );
    $stmt->execute(['id' => EQUIPE_CASCA_ID]);
    $vinculosCasca = $stmt->fetchAll();

    $idsEncontrados = array_map(function ($v) {
        return (int) $v['participante_id'];
    }, $vinculosCasca);
    sort($idsEncontrados);

    $idsEsperados = PARTICIPANTES_ESPERADOS;
    sort($idsEsperados);

    if ($idsEncontrados !== $idsEsperados) {
        $problemas[] = 'Vinculos da equipe ' . EQUIPE_CASCA_ID . ' divergem do esperado: participantes [' . implode(', ', $idsEncontrados) . '] (esperado [' . implode(', ', $idsEsperados) . ']).';
    }

    foreach (PARTICIPANTES_ESPERADOS as $participanteId) {
        $stmt = $pdo->prepare(
            "SELECT COUNT(*) FROM equipe_participante
             WHERE equipe_id = :equipe_id AND participante_id = :participante_id
               AND status_homologacao = 'homologado'"
        );
        $stmt->execute(['equipe_id' => EQUIPE_REAL_ID, 'participante_id' => $participanteId]);

        if ((int) $stmt->fetchColumn() !== 1) {
            $problemas[] = "Participante {$participanteId} NAO tem vinculo homologado na equipe real " . EQUIPE_REAL_ID . " - remover a casca o deixaria sem equipe.";
        }
    }

    echo "\nVinculos na casca:\n";
    foreach ($vinculosCasca as $v) {
        echo "  vinculo #{$v['id']} | participante {$v['participante_id']} ({$v['nome']}) | papel {$v['papel']} | {$v['status_homologacao']}\n";
    }
}

// ---------------------------------------------------------------------
// 3) A casca tem exatamente 1 submissao, e ela e' a historica de cadastro
//    da importacao (sem formulario dinamico, aba INT_homologado).
// ---------------------------------------------------------------------
$submissaoCasca = null;
$qtdCpfs = 0;

if ($casca !== false) {
    $stmt = $pdo->prepare('SELECT * FROM submissoes WHERE equipe_id = :id');
    $stmt->execute(['id' => EQUIPE_CASCA_ID]);
    $submissoesCasca = $stmt->fetchAll();

    if (count($submissoesCasca) !== 1) {
        $problemas[] = 'Equipe ' . EQUIPE_CASCA_ID . ' tem ' . count($submissoesCasca) . ' submissao(oes) (esperada exatamente 1, a historica de cadastro).';
    } else {
        $submissaoCasca = $submissoesCasca[0];
        $dados = json_decode((string) $submissaoCasca['dados_json'], true);

        if ($submissaoCasca['formulario_dinamico_id'] !== null) {
            $problemas[] = "Submissao {$submissaoCasca['id']} tem formulario_dinamico_id preenchido - pode ser submissao avaliavel, NAO remover.";
        }

        if (!is_array($dados) || !isset($dados['aba']) || $dados['aba'] !== 'INT_homologado' || !isset($dados['importado_de']) || $dados['importado_de'] !== 'google_forms') {
            $problemas[] = "Submissao {$submissaoCasca['id']} nao e' a historica de cadastro esperada (aba INT_homologado importada do google_forms).";
        }

        echo "\nSubmissao historica de cadastro: #{$submissaoCasca['id']} (etapa_id {$submissaoCasca['etapa_id']}, criada em {$submissaoCasca['criado_em']})\n";

        // Nenhum vinculo de avaliacao pode existir para essa submissao.
        foreach (['avaliador_designacoes', 'notas_lancadas', 'resultados_etapa', 'feedback_submissao'] as $tabela) {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM {$tabela} WHERE submissao_id = :sid");
            $stmt->execute(['sid' => $submissaoCasca['id']]);
            $qtd = (int) $stmt->fetchColumn();
            echo "  {$tabela}: {$qtd}\n";

            if ($qtd > 0) {
                $problemas[] = "Submissao {$submissaoCasca['id']} tem {$qtd} registro(s) em {$tabela} - vinculo de avaliacao encontrado, NAO remover.";
            }
        }

        $stmt = $pdo->prepare('SELECT COUNT(*) FROM submissao_cpfs WHERE submissao_id = :sid');
        $stmt->execute(['sid' => $submissaoCasca['id']]);
        $qtdCpfs = (int) $stmt->fetchColumn();
        echo "  submissao_cpfs (trava anti-duplicidade da importacao, sera removida junto): {$qtdCpfs}\n";
    }
}

// ---------------------------------------------------------------------
// 4) Nenhuma outra tabela do sistema aponta para a equipe casca
//    (mentorias, oficinas, duvidas, resultado final de trilha).
// ---------------------------------------------------------------------
if ($casca !== false) {
    echo "\nOutras referencias a equipe " . EQUIPE_CASCA_ID . ":\n";

    foreach (['resultados_trilha', 'mentoria_horarios', 'oficina_inscricoes', 'duvidas'] as $tabela) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM {$tabela} WHERE equipe_id = :id");
        $stmt->execute(['id' => EQUIPE_CASCA_ID]);
        $qtd = (int) $stmt->fetchColumn();
        echo "  {$tabela}: {$qtd}\n";

        if ($qtd > 0) {
            $problemas[] = "Tabela {$tabela} tem {$qtd} registro(s) apontando para a equipe " . EQUIPE_CASCA_ID . " - precisa de analise caso a caso antes de remover.";
        }
    }
}

// ---------------------------------------------------------------------
// Veredito.
// ---------------------------------------------------------------------
echo "\n";
linha('=');

if (!empty($problemas)) {
    echo "ESTADO DIVERGENTE DO DOCUMENTADO - NADA FOI ALTERADO.\n";
    linha('=');

    foreach ($problemas as $p) {
        echo "  [PROBLEMA] {$p}\n";
    }

    echo "\nReanalise a situacao antes de qualquer remocao.\n\n";
    exit(1);
}

echo "Todas as verificacoes conferem com o estado documentado na investigacao.\n";
linha('=');

echo "\nPlano de remocao (transacao unica):\n";
echo "  1. {$qtdCpfs} linha(s) de submissao_cpfs da submissao #{$submissaoCasca['id']}\n";
echo "  2. submissao #{$submissaoCasca['id']} (historica de cadastro, aba INT_homologado)\n";

foreach ($vinculosCasca as $v) {
    echo "  3. vinculo equipe_participante #{$v['id']} (participante {$v['participante_id']} - {$v['nome']})\n";
}

echo "  4. equipe #" . EQUIPE_CASCA_ID . " ('" . NOME_EQUIPE . "', trilha Interna)\n";
echo "\nNAO serao tocados: equipe " . EQUIPE_REAL_ID . ", participantes, usuarios, perfis, notas,\n";
echo "designacoes, resultados, log de auditoria.\n";

if (!$confirmar) {
    echo "\nModo consulta (dry-run). Nada foi alterado.\n";
    echo "Para aplicar de verdade: php database/remover_equipe_duplicada_inovesw.php --confirmar\n\n";
    exit(0);
}

$pdo->beginTransaction();

try {
    $stmt = $pdo->prepare('DELETE FROM submissao_cpfs WHERE submissao_id = :sid');
    $stmt->execute(['sid' => $submissaoCasca['id']]);
    Auditoria::registrar('excluir', 'submissao_cpfs', null, [
        'submissao_id' => (int) $submissaoCasca['id'],
        'quantidade' => $qtdCpfs,
        'motivo' => 'remocao equipe duplicada InoveSW 106',
    ], null);

    $stmt = $pdo->prepare('DELETE FROM submissoes WHERE id = :id');
    $stmt->execute(['id' => $submissaoCasca['id']]);
    Auditoria::registrar('excluir', 'submissoes', (int) $submissaoCasca['id'], $submissaoCasca, null);

    foreach ($vinculosCasca as $v) {
        $stmt = $pdo->prepare('DELETE FROM equipe_participante WHERE id = :id');
        $stmt->execute(['id' => $v['id']]);
        Auditoria::registrar('excluir', 'equipe_participante', (int) $v['id'], $v, null);
    }

    $stmt = $pdo->prepare('DELETE FROM equipes WHERE id = :id');
    $stmt->execute(['id' => EQUIPE_CASCA_ID]);
    Auditoria::registrar('excluir', 'equipes', EQUIPE_CASCA_ID, $casca, null);

    $pdo->commit();
} catch (\Exception $e) {
    $pdo->rollBack();
    echo "\n[ERRO] Falha durante a remocao - transacao desfeita, nada foi alterado: " . $e->getMessage() . "\n\n";
    exit(1);
}

echo "\n[ok] Equipe duplicada removida com sucesso. Registros de exclusao gravados na Auditoria.\n";
echo "\nConferencias recomendadas agora:\n";
echo "  1. php database/investigar_convites_pendentes.php  (Michel deve aparecer 1x no Padrao B)\n";
echo "  2. Tela 'Minha inscricao' do lider (visualizar como) - deve seguir mostrando a trilha Externa\n";
echo "  3. Homologacao publica da trilha Interna - InoveSW nao deve mais aparecer\n\n";
