<?php

namespace App\Services;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

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
        $usuario = $this->usuarios->buscarPorEmail($participante['email']);

        if ($usuario === null) {
            $usuarioId = $this->usuarios->criarAprovadoSemSenha($participante['nome'], $participante['email']);
        } else {
            $usuarioId = $usuario['id'];

            if ($usuario['status'] !== 'aprovado') {
                $this->usuarios->atualizarStatus($usuarioId, 'aprovado');
            }
        }

        $this->usuarioParticipante->vincular($usuarioId, $participante['id']);

        $perfilParticipante = $this->perfis->buscarPorChave('participante');

        if (!$this->perfis->possuiPerfil($usuarioId, $perfilParticipante['id'], $trilha['concurso_id'])) {
            $this->perfis->atribuir($usuarioId, $perfilParticipante['id'], $trilha['concurso_id']);
        }

        $token = $this->tokens->criar($usuarioId, 'definir');
        $link = urlAbsoluta('auth/definirSenha/' . $token);

        try {
            (new NotificacaoService())->acessoLiberado($participante['email'], $participante['nome'], $nomeEquipe, $link);
        } catch (\Exception $e) {
            // Falha de notificacao nunca deve quebrar a homologacao ja gravada.
        }
    }
}
