<?php

if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    exit('Acesso negado: este script so pode ser executado via linha de comando.');
}

define('SI_BOOT', true);

require __DIR__ . '/../vendor/autoload.php';

use App\Core\Database;
use App\Repositories\PerfilRepository;
use App\Repositories\UsuarioRepository;

function perguntar($rotulo)
{
    fwrite(STDOUT, $rotulo);

    return trim(fgets(STDIN));
}

$nome = perguntar('Nome do administrador: ');
$email = perguntar('E-mail do administrador: ');
$senha = perguntar('Senha do administrador: ');

if ($nome === '' || $email === '' || $senha === '') {
    exit("Nome, e-mail e senha sao obrigatorios.\n");
}

$usuarios = new UsuarioRepository();
$perfis = new PerfilRepository();

if ($usuarios->buscarPorEmail($email) !== null) {
    exit("Ja existe um usuario com este e-mail.\n");
}

$perfilAdministrador = $perfis->buscarPorChave('administrador');

if ($perfilAdministrador === null) {
    exit("Perfil 'administrador' nao encontrado. Rode 'php database/migrate.php' primeiro.\n");
}

$pdo = Database::conexao();
$pdo->beginTransaction();

try {
    $hash = password_hash($senha, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare(
        "INSERT INTO usuarios (nome, email, senha_hash, status) VALUES (:nome, :email, :senha_hash, 'aprovado')"
    );
    $stmt->execute(['nome' => $nome, 'email' => $email, 'senha_hash' => $hash]);
    $usuarioId = (int) $pdo->lastInsertId();

    $perfis->atribuir($usuarioId, $perfilAdministrador['id'], null);

    $pdo->commit();
    echo "Administrador criado com sucesso (id {$usuarioId}).\n";
} catch (Exception $e) {
    $pdo->rollBack();
    echo 'ERRO ao criar administrador: ' . $e->getMessage() . "\n";
    exit(1);
}
