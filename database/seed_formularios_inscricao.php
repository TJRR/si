<?php

/**
 * Cria os 2 formularios de Inscricao de Equipe (Trilha Externa e Interna),
 * espelhando EXATAMENTE os campos dos formularios reais dos editais 2026
 * (form_externo.pdf / form_interno.pdf, estruturalmente identicos) - via os
 * mesmos repositorios que a tela admin usa, para o resultado ficar 100%
 * editavel/duplicavel/removivel pela interface (formularios/index).
 *
 * Cada campo de participante leva uma marca semantica em config_json
 * ("_papel") para a Inscricao saber que campo e o que, independente do texto
 * do rotulo que o Admin possa editar depois.
 *
 * Idempotente: pula a trilha se ja existir um formulario com o mesmo nome.
 */

if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    exit('Acesso negado: este script so pode ser executado via linha de comando.');
}

define('SI_BOOT', true);

require __DIR__ . '/../vendor/autoload.php';

use App\Core\Database;
use App\Repositories\CampoDinamicoRepository;
use App\Repositories\EtapaRepository;
use App\Repositories\FormularioDinamicoRepository;
use App\Repositories\TrilhaRepository;

$formularios = new FormularioDinamicoRepository();
$campos = new CampoDinamicoRepository();
$etapas = new EtapaRepository();
$trilhas = new TrilhaRepository();

function camposDaInscricao()
{
    $campos = [
        ['rotulo' => 'E-mail para contato', 'tipo' => 'email', 'obrigatorio' => 1, 'papel' => ['_papel' => 'email_contato']],
        ['rotulo' => 'Nome da equipe', 'tipo' => 'texto', 'obrigatorio' => 1, 'papel' => ['_papel' => 'nome_equipe']],
    ];

    $definicaoParticipantes = [
        1 => ['obrigatorio' => 1, 'rotulo_extra' => ' (Líder da Equipe)', 'com_contato' => true],
        2 => ['obrigatorio' => 1, 'rotulo_extra' => '', 'com_contato' => false],
        3 => ['obrigatorio' => 0, 'rotulo_extra' => '', 'com_contato' => false],
        4 => ['obrigatorio' => 0, 'rotulo_extra' => '', 'com_contato' => false],
        5 => ['obrigatorio' => 0, 'rotulo_extra' => '', 'com_contato' => false],
    ];

    foreach ($definicaoParticipantes as $indice => $def) {
        $sufixo = 'Participante ' . $indice . $def['rotulo_extra'];

        $campos[] = ['rotulo' => 'Nome completo - ' . $sufixo, 'tipo' => 'texto', 'obrigatorio' => $def['obrigatorio'], 'papel' => ['_papel' => 'participante', 'indice' => $indice, 'campo' => 'nome']];
        $campos[] = ['rotulo' => 'CPF - ' . $sufixo, 'tipo' => 'cpf', 'obrigatorio' => $def['obrigatorio'], 'papel' => ['_papel' => 'participante', 'indice' => $indice, 'campo' => 'cpf']];

        if ($def['com_contato']) {
            $campos[] = ['rotulo' => 'Telefone (WhatsApp) - ' . $sufixo, 'tipo' => 'telefone', 'obrigatorio' => $def['obrigatorio'], 'papel' => ['_papel' => 'participante', 'indice' => $indice, 'campo' => 'telefone']];
            $campos[] = ['rotulo' => 'E-mail - ' . $sufixo, 'tipo' => 'email', 'obrigatorio' => $def['obrigatorio'], 'papel' => ['_papel' => 'participante', 'indice' => $indice, 'campo' => 'email']];
        }

        $campos[] = ['rotulo' => 'Local de Trabalho/Estudo - ' . $sufixo, 'tipo' => 'texto', 'obrigatorio' => $def['obrigatorio'], 'papel' => ['_papel' => 'participante', 'indice' => $indice, 'campo' => 'local_trabalho']];
        $campos[] = ['rotulo' => 'Profissão/função que exerce - ' . $sufixo, 'tipo' => 'texto', 'obrigatorio' => $def['obrigatorio'], 'papel' => ['_papel' => 'participante', 'indice' => $indice, 'campo' => 'profissao']];
    }

    return $campos;
}

function criarFormularioSeNaoExistir(FormularioDinamicoRepository $formularios, CampoDinamicoRepository $campos, EtapaRepository $etapas, $etapaId, $nomeFormulario, $descricao)
{
    $etapa = $etapas->buscarPorId($etapaId);

    if ($etapa === null) {
        echo "ERRO: etapa {$etapaId} nao encontrada.\n";
        return;
    }

    if ($etapa['formulario_dinamico_id'] !== null) {
        echo "Ja existe formulario vinculado a etapa {$etapaId} ({$etapa['nome']}) - pulando.\n";
        return;
    }

    $formularioId = $formularios->criar($nomeFormulario, $descricao, 1, 'rascunho');

    foreach (camposDaInscricao() as $campo) {
        $campos->criar($formularioId, $campo['rotulo'], $campo['tipo'], $campo['obrigatorio'], $campo['papel']);
    }

    $formularios->atualizarStatus($formularioId, 'publicado');

    $pdo = Database::conexao();
    $stmt = $pdo->prepare('UPDATE etapas SET formulario_dinamico_id = :formulario_id WHERE id = :etapa_id');
    $stmt->execute(['formulario_id' => $formularioId, 'etapa_id' => $etapaId]);

    echo "Criado formulario #{$formularioId} ({$nomeFormulario}) e vinculado a etapa {$etapaId} ({$etapa['nome']}).\n";
}

$trilhaExterna = $trilhas->buscarPorNome('Trilha Externa');
$trilhaInterna = $trilhas->buscarPorNome('Trilha Interna');

if ($trilhaExterna === null || $trilhaInterna === null) {
    exit("ERRO: nao encontrei as trilhas 'Trilha Externa'/'Trilha Interna'.\n");
}

$etapaCadastroExterna = null;
$etapaCadastroInterna = null;

foreach ($etapas->listarPorTrilha($trilhaExterna['id']) as $etapa) {
    if ((int) $etapa['ordem'] === 1) {
        $etapaCadastroExterna = $etapa;
    }
}

foreach ($etapas->listarPorTrilha($trilhaInterna['id']) as $etapa) {
    if ((int) $etapa['ordem'] === 1) {
        $etapaCadastroInterna = $etapa;
    }
}

if ($etapaCadastroExterna === null || $etapaCadastroInterna === null) {
    exit("ERRO: nao encontrei a etapa 'ordem=1' (Cadastro de Equipe) em uma das trilhas.\n");
}

criarFormularioSeNaoExistir(
    $formularios,
    $campos,
    $etapas,
    $etapaCadastroExterna['id'],
    'Inscrição de Equipe - Trilha Externa',
    'Inscrição para o público externo do 5º Prêmio de Inovação do TJRR (Edital 12/2026). Não precisa apresentar a ideia neste momento - ela é submetida em etapa posterior, após a homologação.'
);

criarFormularioSeNaoExistir(
    $formularios,
    $campos,
    $etapas,
    $etapaCadastroInterna['id'],
    'Inscrição de Equipe - Trilha Interna',
    'Inscrição para membros/servidores do Poder Judiciário do 5º Prêmio de Inovação do TJRR (Edital 13/2026). Não precisa apresentar a ideia neste momento - ela é submetida em etapa posterior, após a homologação.'
);

echo "Concluido.\n";
