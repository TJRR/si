<?php

namespace App\Services;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Repositories\PerfilRepository;
use App\Repositories\TokenSenhaRepository;
use App\Repositories\TrabalhoAutorRepository;
use App\Repositories\TrabalhoAvaliadorRepository;
use App\Repositories\UsuarioRepository;

/**
 * Fase 49: convite de avaliador avulso de Trabalhos, por e-mail. Classe
 * NOVA e isolada: replica (copia, nao refatora) a logica interna de
 * criacao de conta ja usada em
 * AcessoParticipanteService::vincularUsuarioEPerfil()
 * (app/Services/AcessoParticipanteService.php), sem alterar aquele
 * arquivo, que continua servindo so' a homologacao e o convite do
 * Concurso, como antes.
 *
 * Diferenca central: NUNCA atribui o perfil global `avaliador` (que da'
 * acesso a avaliacao/* do Concurso) nem grava em usuario_perfil_concurso -
 * atribui o perfil generico `inscrito` (so' para Auth::destinoPainel()
 * rotear a pessoa, mesmo mecanismo do Facilitador na Fase 48) e grava a
 * autorizacao real de avaliar em trabalho_avaliadores, escopada ao
 * evento. Isso evita que um avaliador avulso convidado so' para Trabalhos
 * de um Evento ganhe acesso ao Premio de Inovacao em andamento.
 */
class TrabalhoAvaliadorConviteService
{
    private $usuarios;
    private $perfis;
    private $tokens;
    private $trabalhoAvaliadores;
    private $trabalhoAutores;

    public function __construct()
    {
        $this->usuarios = new UsuarioRepository();
        $this->perfis = new PerfilRepository();
        $this->tokens = new TokenSenhaRepository();
        $this->trabalhoAvaliadores = new TrabalhoAvaliadorRepository();
        $this->trabalhoAutores = new TrabalhoAutorRepository();
    }

    public function convidar($nome, $email, array $evento, $convidadoPor)
    {
        // Fase 49B, achado do teste de fumaça (item 6.b): exclusão mútua
        // autor/avaliador dentro do mesmo evento - quem já submeteu um
        // trabalho (autor principal ou coautor) não pode virar avaliador
        // avulso dele. Checagem por e-mail, antes de qualquer conta ser
        // criada/reaproveitada.
        if ($this->trabalhoAutores->possuiTrabalhoNoEventoPorEmail((int) $evento['id'], $email)) {
            throw new \RuntimeException('Este e-mail já consta como autor (principal ou coautor) de um trabalho submetido neste evento e não pode ser convidado como avaliador.');
        }

        $usuario = $this->usuarios->buscarPorEmail($email);
        $jaExistia = $usuario !== null;

        if ($usuario === null) {
            $usuarioId = $this->usuarios->criarAprovadoSemSenha($nome, $email);
        } else {
            $usuarioId = (int) $usuario['id'];

            if ($usuario['status'] !== 'aprovado') {
                $this->usuarios->atualizarStatus($usuarioId, 'aprovado');
            }
        }

        $perfilInscrito = $this->perfis->buscarPorChave('inscrito');

        if ($perfilInscrito !== null && !$this->perfis->possuiPerfil($usuarioId, $perfilInscrito['id'], null)) {
            $this->perfis->atribuir($usuarioId, $perfilInscrito['id'], null);
        }

        $this->trabalhoAvaliadores->criar((int) $evento['id'], $usuarioId, $convidadoPor);

        // Achado do usuário no teste de fumaça: mesmo quando a pessoa já
        // tem conta, o convite continua sendo enviado - ela precisa saber
        // que ganhou autorização nova (avaliar Trabalhos deste evento),
        // só que com texto diferente (sem link de definir senha, ela já
        // tem acesso funcionando). Nome do e-mail vem do cadastro já
        // existente nesse caso, não do que o Admin digitou no convite
        // (pode divergir).
        try {
            if ($jaExistia) {
                (new NotificacaoService())->conviteAvaliadorTrabalhosContaExistente($email, $usuario['nome'], $evento);
            } else {
                $token = $this->tokens->criar($usuarioId, 'definir');
                $link = urlAbsoluta('auth/definirSenha/' . $token);
                (new NotificacaoService())->conviteAvaliadorTrabalhos($email, $nome, $evento, $link);
            }
        } catch (\Exception $e) {
            // Falha de notificacao nunca deve quebrar o convite ja gravado.
        }

        return ['usuario_id' => $usuarioId, 'ja_existia' => $jaExistia];
    }
}
