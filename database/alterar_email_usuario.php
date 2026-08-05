<?php

/**
 * Fase 26: altera o e-mail de LOGIN de um usuario (usuarios.email) - sem
 * tela nenhuma, de proposito (decisao do usuario). So' mexe na credencial
 * de login em si:
 *   - NAO mexe no e-mail de inscricao do participante (participantes.email,
 *     tabela separada) - quem precisar trocar aquele, e' outro dado.
 *   - NAO afeta login por Google ja vinculado: AuthService::
 *     resolverUsuarioGoogle() busca primeiro por google_id, so' cai pra
 *     busca por e-mail se o usuario ainda nao tiver google_id (Fase 1).
 *   - Login manual (senha) passa a exigir o e-mail NOVO a partir daqui -
 *     esperado, e' a definicao de "mudar o e-mail de login".
 *
 * usuarios.email tem UNIQUE KEY - o script recusa (com mensagem clara,
 * antes de tentar o UPDATE) se o e-mail novo ja pertencer a outro usuario.
 *
 * Uso (identifique o usuario por id, e-mail atual OU nome exato - se o
 * nome bater com mais de um usuario, o script lista os ids e para):
 *   php database/alterar_email_usuario.php --id=42 --novo-email=novo@exemplo.com
 *   php database/alterar_email_usuario.php --email=antigo@exemplo.com --novo-email=novo@exemplo.com
 *   php database/alterar_email_usuario.php --nome="Fulano da Silva" --novo-email=novo@exemplo.com
 *   ... --confirmar (em qualquer uma das formas acima, pra aplicar de verdade)
 *
 * Por padrao roda em modo consulta (dry-run): so mostra o que seria feito.
 */

if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    exit('Acesso negado: este script so pode ser executado via linha de comando.');
}

define('SI_BOOT', true);

require __DIR__ . '/../vendor/autoload.php';

use App\Repositories\UsuarioRepository;

function lerArgumento($argv, $nome)
{
    foreach ($argv as $arg) {
        if (strpos($arg, "--{$nome}=") === 0) {
            return substr($arg, strlen("--{$nome}="));
        }
    }

    return null;
}

$id = lerArgumento($argv, 'id');
$email = lerArgumento($argv, 'email');
$nome = lerArgumento($argv, 'nome');
$novoEmail = lerArgumento($argv, 'novo-email');
$confirmar = in_array('--confirmar', $argv, true);

if ($id === null && ($email === null || $email === '') && ($nome === null || $nome === '')) {
    echo "Uso: php database/alterar_email_usuario.php --id=42 --novo-email=novo@exemplo.com [--confirmar]\n";
    echo "  ou: php database/alterar_email_usuario.php --email=antigo@exemplo.com --novo-email=novo@exemplo.com [--confirmar]\n";
    echo "  ou: php database/alterar_email_usuario.php --nome=\"Fulano da Silva\" --novo-email=novo@exemplo.com [--confirmar]\n";
    exit(1);
}

if ($novoEmail === null || $novoEmail === '') {
    echo "Informe --novo-email=algum@exemplo.com.\n";
    exit(1);
}

if (!filter_var($novoEmail, FILTER_VALIDATE_EMAIL)) {
    echo "\"$novoEmail\" não é um e-mail válido.\n";
    exit(1);
}

$usuarios = new UsuarioRepository();

if ($id !== null) {
    $usuario = $usuarios->buscarPorId((int) $id);
} elseif ($email !== null && $email !== '') {
    $usuario = $usuarios->buscarPorEmail($email);
} else {
    $encontrados = array_filter($usuarios->listarTodos(), function ($item) use ($nome) {
        return $item['nome'] === $nome;
    });

    if (count($encontrados) > 1) {
        echo "Mais de um usuário com o nome \"$nome\" - rode de novo com --id= usando um destes ids:\n";
        foreach ($encontrados as $item) {
            echo "  id {$item['id']} - {$item['email']}\n";
        }
        exit(1);
    }

    $usuario = !empty($encontrados) ? reset($encontrados) : null;
}

if ($usuario === null) {
    echo "Nenhum usuário encontrado.\n";
    exit(1);
}

$usuarioId = (int) $usuario['id'];

echo "Usuário: {$usuario['nome']} <{$usuario['email']}> (id $usuarioId)\n";
echo str_repeat('-', 60) . "\n";

if (strcasecmp($usuario['email'], $novoEmail) === 0) {
    echo "O usuário já tem exatamente este e-mail. Nada a fazer.\n";
    exit;
}

$conflito = $usuarios->buscarPorEmail($novoEmail);

if ($conflito !== null && (int) $conflito['id'] !== $usuarioId) {
    echo "RECUSADO: o e-mail \"$novoEmail\" já pertence a outro usuário: {$conflito['nome']} (id {$conflito['id']}).\n";
    exit(1);
}

echo "E-mail atual: {$usuario['email']}\n";
echo "E-mail novo:  $novoEmail\n";

if (!$confirmar) {
    echo "\nModo consulta (dry-run). Nada foi alterado.\n";
    echo "Depois da troca, o login manual (senha) passa a exigir o e-mail novo;\n";
    echo "login por Google já vinculado não é afetado (busca por google_id, não por e-mail).\n";
    echo "Para aplicar de verdade, repita o comando com --confirmar.\n";
    exit;
}

$usuarios->atualizarEmail($usuarioId, $novoEmail);

echo "\nE-mail de {$usuario['nome']} (id $usuarioId) atualizado para \"$novoEmail\".\n";
