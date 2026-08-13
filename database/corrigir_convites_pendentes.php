<?php

/**
 * Fase 29 (Bug 1, remediacao do Padrao B): dispara o convite de acesso -
 * exatamente a mesma rotina do botao "Convidar acesso" da tela de
 * Homologacao (AcessoParticipanteService::liberarAcesso()) - para todo
 * participante com e-mail cadastrado + vinculo ja homologado que ainda nao
 * tem nenhuma conta vinculada. E' o "Padrao B" identificado por
 * database/investigar_convites_pendentes.php.
 *
 * Reusa o mesmo metodo ja rodando em producao pelo clique manual do admin -
 * inclusive reconcilia sozinho quem ja tinha se auto-cadastrado pela tela
 * publica de Cadastro com o mesmo e-mail (AcessoParticipanteService::
 * vincularUsuarioEPerfil() busca a conta por e-mail antes de criar uma
 * nova; se encontrar rejeitada/pendente, aprova). Nao inventa nenhum
 * caminho novo de acesso.
 *
 * Uso:
 *   php database/corrigir_convites_pendentes.php             (dry-run, so mostra)
 *   php database/corrigir_convites_pendentes.php --confirmar (aplica de verdade)
 */

if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    exit('Acesso negado: este script so pode ser executado via linha de comando.');
}

define('SI_BOOT', true);

require __DIR__ . '/../vendor/autoload.php';

use App\Core\Database;
use App\Repositories\ParticipanteRepository;
use App\Repositories\UsuarioRepository;
use App\Services\AcessoParticipanteService;

function linha($ch = '-')
{
    echo str_repeat($ch, 78) . "\n";
}

$confirmar = in_array('--confirmar', $argv, true);

$pdo = Database::conexao();

$pendentes = $pdo->query(
    "SELECT p.id AS participante_id, p.nome, p.email, e.nome_equipe, e.trilha_id
     FROM participantes p
     JOIN equipe_participante ep ON ep.participante_id = p.id
     JOIN equipes e ON e.id = ep.equipe_id
     WHERE p.email IS NOT NULL
       AND ep.status_homologacao = 'homologado'
       AND NOT EXISTS (SELECT 1 FROM usuario_participante up WHERE up.participante_id = p.id)
     ORDER BY p.id ASC"
)->fetchAll();

if (empty($pendentes)) {
    echo "Nenhum convite pendente encontrado. Nada a fazer.\n";
    exit;
}

echo "Encontrados " . count($pendentes) . " participante(s) com convite pendente (Padrao B).\n";
linha();

$usuarios = new UsuarioRepository();
$participantes = new ParticipanteRepository();
$acesso = new AcessoParticipanteService();

$reaproveitados = 0;
$criados = 0;
$reaprovados = 0;

foreach ($pendentes as $item) {
    $contaExistente = $usuarios->buscarPorEmail($item['email']);

    printf(
        "participante_id %-4d | %-30s | %-30s | equipe \"%s\"\n",
        $item['participante_id'],
        mb_substr($item['nome'], 0, 30),
        mb_substr($item['email'], 0, 30),
        $item['nome_equipe']
    );

    if ($contaExistente !== null) {
        printf(
            "  -> ja existe conta usuarios #%d (status atual: %s) - sera reaproveitada%s.\n",
            $contaExistente['id'],
            $contaExistente['status'],
            $contaExistente['status'] !== 'aprovado' ? ' e aprovada' : ''
        );
        $reaproveitados++;
        if ($contaExistente['status'] === 'rejeitado') {
            $reaprovados++;
            echo "  -> ATENCAO: esta conta estava REJEITADA por um admin - sera revertida para aprovada.\n";
        }
    } else {
        echo "  -> nenhuma conta encontrada - sera criada uma nova (aprovada, sem senha) e o e-mail de acesso sera enviado.\n";
        $criados++;
    }

    if ($confirmar) {
        $participante = $participantes->buscarPorId($item['participante_id']);
        $acesso->liberarAcesso($participante, $item['trilha_id'], $item['nome_equipe']);
        echo "  -> OK, acesso liberado.\n";
    }

    echo "\n";
}

linha();
printf(
    "Resumo: %d conta(s) nova(s) seriam criadas | %d conta(s) existente(s) reaproveitadas (sendo %d revertidas de 'rejeitado').\n",
    $criados,
    $reaproveitados,
    $reaprovados
);

if (!$confirmar) {
    echo "\nModo consulta (dry-run). Nada foi alterado. Repita com --confirmar para aplicar de verdade.\n";
} else {
    echo "\nConcluido: " . count($pendentes) . " participante(s) processado(s).\n";
}
