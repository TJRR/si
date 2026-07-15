<?php

namespace App\Services;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Core\Auditoria;
use App\Repositories\PerfilRepository;
use App\Repositories\TokenSenhaRepository;
use App\Repositories\TrilhaRepository;
use App\Repositories\UsuarioParticipanteRepository;
use App\Repositories\UsuarioRepository;

/**
 * Dispara a homologacao virar acesso de verdade: cria/aprova a conta
 * (usuarios), vincula ao participante (usuario_participante), atribui o
 * perfil "participante" escopado ao concurso, e envia o e-mail com o link
 * de definir senha (o participante tambem pode simplesmente entrar com
 * Google usando o mesmo e-mail - ja funciona via AuthService::resolverUsuarioGoogle).
 */
class AcessoParticipanteService
{
    private $usuarios;
    private $usuarioParticipante;
    private $perfis;
    private $tokens;
    private $trilhas;

    public function __construct()
    {
        $this->usuarios = new UsuarioRepository();
        $this->usuarioParticipante = new UsuarioParticipanteRepository();
        $this->perfis = new PerfilRepository();
        $this->tokens = new TokenSenhaRepository();
        $this->trilhas = new TrilhaRepository();
    }

    public function liberarAcesso(array $participante, $trilhaId, $nomeEquipe)
    {
        if (empty($participante['email'])) {
            return;
        }

        $trilha = $this->trilhas->buscarPorId($trilhaId);
        $perfilParticipante = $this->perfis->buscarPorChave('participante');

        $resultado = $this->vincularUsuarioEPerfil(
            $participante['nome'],
            $participante['email'],
            $perfilParticipante['id'],
            $trilha['concurso_id']
        );

        $this->usuarioParticipante->vincular($resultado['usuario_id'], $participante['id']);

        $token = $this->tokens->criar($resultado['usuario_id'], 'definir');
        $link = urlAbsoluta('auth/definirSenha/' . $token);

        try {
            (new NotificacaoService())->acessoLiberado($participante['email'], $participante['nome'], $nomeEquipe, $link);
        } catch (\Exception $e) {
            // Falha de notificacao nunca deve quebrar a homologacao ja gravada.
        }
    }

    /**
     * Convite direto do admin: mesma base de vinculo usuario+perfil da
     * homologacao, mas so gera token/e-mail de acesso quando o e-mail e
     * novo - se o e-mail ja existia, so o perfil novo e adicionado (decisao
     * confirmada com o usuario: evita reenviar link de definir senha pra
     * quem ja tem acesso funcionando).
     */
    public function convidarUsuario($nome, $email, $perfilId, $concursoId)
    {
        $resultado = $this->vincularUsuarioEPerfil($nome, $email, $perfilId, $concursoId);

        if ($resultado['ja_existia']) {
            return $resultado;
        }

        $token = $this->tokens->criar($resultado['usuario_id'], 'definir');
        $link = urlAbsoluta('auth/definirSenha/' . $token);

        try {
            (new NotificacaoService())->conviteAdministrativo($email, $nome, $link);
        } catch (\Exception $e) {
            // Falha de notificacao nunca deve quebrar o convite ja gravado.
        }

        return $resultado;
    }

    /**
     * Reenvio manual do convite (tela de Usuarios) - so' se aplica a quem
     * ainda nao entrou (sem senha e sem Google vinculados). Invalida o token
     * "definir" pendente antes de gerar um novo, pra nao deixar dois links
     * validos ao mesmo tempo pro mesmo convite.
     */
    public function reenviarConvite($usuarioId)
    {
        $usuario = $this->usuarios->buscarPorId($usuarioId);

        if ($usuario === null || $usuario['senha_hash'] !== null || $usuario['google_id'] !== null) {
            return false;
        }

        $this->tokens->invalidarPendentes($usuarioId, 'definir');

        $token = $this->tokens->criar($usuarioId, 'definir');
        $link = urlAbsoluta('auth/definirSenha/' . $token);

        try {
            (new NotificacaoService())->conviteAdministrativo($usuario['email'], $usuario['nome'], $link);
        } catch (\Exception $e) {
            // Falha de notificacao nunca deve quebrar o reenvio ja gravado (token novo ja existe).
        }

        Auditoria::registrar('reenviar_convite', 'usuarios', $usuarioId, null, ['email' => $usuario['email']]);

        return true;
    }

    private function vincularUsuarioEPerfil($nome, $email, $perfilId, $concursoId)
    {
        $usuario = $this->usuarios->buscarPorEmail($email);
        $jaExistia = $usuario !== null;

        if ($usuario === null) {
            $usuarioId = $this->usuarios->criarAprovadoSemSenha($nome, $email);
        } else {
            $usuarioId = $usuario['id'];

            if ($usuario['status'] !== 'aprovado') {
                $this->usuarios->atualizarStatus($usuarioId, 'aprovado');
            }
        }

        if (!$this->perfis->possuiPerfil($usuarioId, $perfilId, $concursoId)) {
            $this->perfis->atribuir($usuarioId, $perfilId, $concursoId);
        }

        return ['usuario_id' => $usuarioId, 'ja_existia' => $jaExistia];
    }
}
