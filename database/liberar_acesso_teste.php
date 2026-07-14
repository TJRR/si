<?php

/**
 * Vincula um usuario JA CADASTRADO (perfil e senha ja definidos manualmente
 * pelo Admin) ao participante de mesmo e-mail - caso do participante cujo
 * cadastro de acesso foi feito manualmente (perfil "participante" atribuido
 * direto na tela de Usuarios), mas ficou sem o vinculo com a equipe porque
 * esse vinculo so e criado automaticamente pelo fluxo normal de homologacao
 * (HomologacaoController).
 *
 * Nao cria usuario, nao atribui perfil, nao mexe em senha, nao envia e-mail -
 * so faz o INSERT em usuario_participante que estava faltando.
 *
 * Uso (so um --email por execucao):
 *   php database/liberar_acesso_teste.php --email=fulano@exemplo.com
 *   php database/liberar_acesso_teste.php --email=fulano@exemplo.com --confirmar
 *
 * Por padrao roda em modo consulta (dry-run): so mostra o que seria feito.
 */

if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    exit('Acesso negado: este script so pode ser executado via linha de comando.');
}

define('SI_BOOT', true);

require __DIR__ . '/../vendor/autoload.php';

use App\Repositories\EquipeRepository;
use App\Repositories\ParticipanteRepository;
use App\Repositories\UsuarioParticipanteRepository;
use App\Repositories\UsuarioRepository;

$confirmar = in_array('--confirmar', $argv, true);
$email = null;

foreach ($argv as $arg) {
    if (strpos($arg, '--email=') === 0) {
        $email = substr($arg, strlen('--email='));
    }
}

if ($email === null || $email === '') {
    echo "Uso: php database/liberar_acesso_teste.php --email=fulano@exemplo.com [--confirmar]\n";
    exit(1);
}

$usuarios = new UsuarioRepository();
$participantes = new ParticipanteRepository();
$equipes = new EquipeRepository();

$usuario = $usuarios->buscarPorEmail($email);

if ($usuario === null) {
    echo "Nenhum usuario cadastrado com o e-mail \"$email\". Cadastre o usuario (com perfil \"participante\") antes de rodar este script.\n";
    exit(1);
}

$participante = $participantes->buscarPorEmail($email);

if ($participante === null) {
    echo "Nenhum participante encontrado com o e-mail \"$email\".\n";
    exit(1);
}

$equipe = $equipes->buscarPorParticipante($participante['id']);

echo "Usuario: {$usuario['nome']} <{$email}> (id {$usuario['id']})\n";
echo "Participante: {$participante['nome']} (id {$participante['id']})\n";
echo $equipe !== null ? "Equipe: {$equipe['nome_equipe']}\n" : "Aviso: este participante ainda nao tem equipe vinculada.\n";
echo "Isso vai vincular este usuario a este participante (tabela usuario_participante) -- nada mais e alterado.\n";

if (!$confirmar) {
    echo "\nModo consulta (dry-run). Nada foi alterado.\n";
    echo "Para aplicar de verdade, rode: php database/liberar_acesso_teste.php --email=$email --confirmar\n";
    exit;
}

(new UsuarioParticipanteRepository())->vincular($usuario['id'], $participante['id']);

echo "\nVinculado. O usuario ja pode logar (com a senha ja definida) e ver a equipe/submissoes normalmente.\n";
