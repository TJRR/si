<?php

/**
 * Gera, para uma conta ja ativa, um endereco de definicao de senha, em uso
 * excepcional autorizado. Ver Implantar.md, secao 13.7.
 *
 * Ensaio por padrao. Para gravar:
 *   php liberar_acesso_auditoria.php --email=pessoa@exemplo.com --dominio=https://endereco-do-sistema --confirmar
 */

if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    exit('Acesso negado: este script so pode ser executado via linha de comando.');
}

define('SI_BOOT', true);

require __DIR__ . '/../vendor/autoload.php';

use App\Core\Auditoria;
use App\Repositories\TokenSenhaRepository;
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

$confirmar = in_array('--confirmar', $argv, true);
$email = lerArgumento($argv, 'email');
$dominio = lerArgumento($argv, 'dominio');

if ($email === null || ($confirmar && $dominio === null)) {
    echo "Uso: php liberar_acesso_auditoria.php --email=fulano@exemplo.com --dominio=https://npi.tjrr.jus.br [--confirmar]\n";
    exit(1);
}

$usuarios = new UsuarioRepository();
$tokens = new TokenSenhaRepository();

$usuario = $usuarios->buscarPorEmail($email);

if ($usuario === null) {
    echo "ERRO: nenhum usuário encontrado com o e-mail '{$email}'.\n";
    exit(1);
}

echo "Usuário: '{$usuario['nome']}' <{$usuario['email']}> (id {$usuario['id']})\n";
echo "Acesso atual: " . ($usuario['senha_hash'] !== null ? 'tem senha local' : 'sem senha local') . ($usuario['google_id'] !== null ? ' + Google vinculado' : '') . "\n";
echo "Seria gerado um novo link de definição de senha, válido por 72h.\n";

if (!$confirmar) {
    echo "\nModo consulta (dry-run). Nada foi gravado.\n";
    echo "Para gravar de verdade, rode com --confirmar\n";
    exit(0);
}

$tokens->invalidarPendentes($usuario['id'], 'definir');
$token = $tokens->criar($usuario['id'], 'definir');
$link = rtrim($dominio, '/') . url('auth/definirSenha/' . $token);

Auditoria::registrar('liberar_acesso_auditoria', 'usuarios', $usuario['id'], null, ['email' => $usuario['email']]);

echo "\n[ok] link gerado (válido por 72h):\n{$link}\n";
echo "\nEntregue este link somente à pessoa autorizada a acessar esta conta.\n";
