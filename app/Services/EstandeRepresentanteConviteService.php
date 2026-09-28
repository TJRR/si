<?php

namespace App\Services;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Core\Database;
use App\Repositories\EstandeConfigRepository;
use App\Repositories\EstandeRepresentanteRepository;
use App\Repositories\PerfilRepository;
use App\Repositories\TokenSenhaRepository;
use App\Repositories\UsuarioRepository;

/**
 * Fase 54: convite, troca e remocao do representante de um estande. Classe
 * NOVA e isolada, no mesmo criterio de TrabalhoAvaliadorConviteService
 * (Fase 49): copia (nao refatora) a logica de criacao de conta de
 * AcessoParticipanteService::vincularUsuarioEPerfil(), sem chamar nem
 * alterar aquele arquivo, que continua servindo so' a homologacao e ao
 * convite do Concurso.
 *
 * Nunca atribui perfil do Concurso: so' o perfil global
 * representante_estande (concurso_id nulo). A autorizacao real, qual
 * estande, fica em evento_estande_representantes, escopada ao evento.
 * Conta, perfil e vinculo sao gravados numa transacao so'; o e-mail sai
 * depois da confirmacao, e falha nele nunca desfaz o que foi gravado.
 */
class EstandeRepresentanteConviteService
{
    private $usuarios;
    private $perfis;
    private $tokens;
    private $representantes;

    public function __construct()
    {
        $this->usuarios = new UsuarioRepository();
        $this->perfis = new PerfilRepository();
        $this->tokens = new TokenSenhaRepository();
        $this->representantes = new EstandeRepresentanteRepository();
    }

    public function convidar($nome, $email, array $estande, array $evento)
    {
        list($nome, $email) = $this->validarNomeEEmail($nome, $email);

        if ($this->representantes->buscarPorEstande((int) $estande['id']) !== null) {
            throw new \RuntimeException('Este estande já tem representante. Para trocar, use "Substituir representante".');
        }

        return $this->gravarRepresentante($nome, $email, $estande, $evento, false);
    }

    public function substituir($nome, $email, array $estande, array $evento)
    {
        list($nome, $email) = $this->validarNomeEEmail($nome, $email);
        $atual = $this->representantes->buscarPorEstande((int) $estande['id']);

        if ($atual !== null && strcasecmp((string) $atual['email'], $email) === 0) {
            throw new \RuntimeException('Esta pessoa já é a representante deste estande.');
        }

        return $this->gravarRepresentante($nome, $email, $estande, $evento, $atual !== null);
    }

    public function remover(array $estande)
    {
        $this->representantes->desvincular((int) $estande['id']);
    }

    private function validarNomeEEmail($nome, $email)
    {
        $nome = trim((string) $nome);
        $email = trim((string) $email);

        if ($nome === '') {
            throw new \RuntimeException('Informe o nome do representante.');
        }

        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new \RuntimeException('Informe um e-mail válido para o representante.');
        }

        return [$nome, $email];
    }

    private function gravarRepresentante($nome, $email, array $estande, array $evento, $substituirAtual)
    {
        $perfil = $this->perfis->buscarPorChave(EstandeRepresentanteRepository::PERFIL);

        if ($perfil === null) {
            throw new \RuntimeException('O perfil de representante de estande não existe no banco. Confira se a atualização do banco desta versão foi aplicada.');
        }

        $usuario = $this->usuarios->buscarPorEmail($email);

        if ($usuario !== null) {
            $outro = $this->representantes->buscarPorEventoEUsuario((int) $estande['evento_id'], (int) $usuario['id']);

            if ($outro !== null && (int) $outro['id'] !== (int) $estande['id']) {
                throw new \RuntimeException('Esta pessoa já representa o estande "' . $outro['nome'] . '" neste evento.');
            }
        }

        $pdo = Database::conexao();
        $pdo->beginTransaction();

        try {
            if ($substituirAtual) {
                $this->representantes->desvincularNaTransacaoAtual((int) $estande['id']);
            }

            if ($usuario === null) {
                $usuarioId = $this->usuarios->criarAprovadoSemSenha($nome, $email);
            } else {
                $usuarioId = (int) $usuario['id'];

                if ($usuario['status'] !== 'aprovado') {
                    $this->usuarios->atualizarStatus($usuarioId, 'aprovado');
                }
            }

            if (!$this->perfis->possuiPerfil($usuarioId, $perfil['id'], null)) {
                $this->perfis->atribuir($usuarioId, $perfil['id'], null);
            }

            $this->representantes->vincularNaTransacaoAtual((int) $estande['id'], $usuarioId);

            $pdo->commit();
        } catch (\PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            if ($e->getCode() === '23000') {
                throw new \RuntimeException('Não foi possível gravar: o estande já tem representante ou esta pessoa já representa outro estande deste evento. Recarregue a página e confira.');
            }

            throw $e;
        } catch (\Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            throw $e;
        }

        // Conta sem senha e sem Google (nova, ou criada antes e nunca
        // usada) recebe o endereco de definir senha; conta em uso recebe o
        // aviso sem esse endereco, como no convite de avaliador de Trabalhos.
        $precisaDefinirSenha = $usuario === null || ($usuario['senha_hash'] === null && $usuario['google_id'] === null);
        $nomeDestinatario = $usuario !== null ? $usuario['nome'] : $nome;
        $mensagemHtml = (new EstandeConfigRepository())->mensagemConvite((int) $estande['evento_id']);

        try {
            if ($precisaDefinirSenha) {
                $token = $this->tokens->criar($usuarioId, 'definir');
                $link = urlAbsoluta('auth/definirSenha/' . $token);
                (new NotificacaoService())->conviteRepresentanteEstande($email, $nomeDestinatario, $evento, $estande, $link, $mensagemHtml);
            } else {
                (new NotificacaoService())->conviteRepresentanteEstandeContaExistente($email, $nomeDestinatario, $evento, $estande, $mensagemHtml);
            }
        } catch (\Exception $e) {
            error_log('[Estandes] Falha ao enviar o convite do representante do estande ' . (int) $estande['id'] . ': ' . $e->getMessage());
        }

        // So' conta como "outro perfil" o que vem antes do representante em
        // Auth::destinoPainel(): o inscrito vem depois e nao desvia a entrada.
        $perfisQueDesviam = array_filter($this->usuarios->perfisDoUsuario($usuarioId), function ($vinculo) {
            return !in_array($vinculo['perfil'], [EstandeRepresentanteRepository::PERFIL, 'inscrito'], true);
        });

        return [
            'usuario_id' => $usuarioId,
            'ja_existia' => $usuario !== null,
            'enviou_endereco_senha' => $precisaDefinirSenha,
            'tinha_outro_perfil' => $usuario !== null && !empty($perfisQueDesviam),
        ];
    }
}
