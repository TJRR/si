<?php

namespace App\Controllers;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Core\Auth;
use App\Core\Controller;
use App\Middleware\RoleMiddleware;
use App\Repositories\NotificacaoPainelRepository;
use App\Repositories\PerfilRepository;
use App\Repositories\RequerimentoEscalonamentoRepository;
use App\Repositories\RequerimentoRepository;
use App\Repositories\RequerimentoRespostaRepository;
use App\Repositories\UsuarioParticipanteRepository;
use App\Services\ArquivoPrivadoService;
use App\Services\CertificadoExtratorService;
use App\Services\ItiValidadorService;
use App\Services\SlaService;
use App\Validation\UploadPdfValidador;

/**
 * Fase 30: atendimento (administrador/suporte/colaborador) de requerimentos
 * - responder, escalar pra outro atendente, retomar uma escalada parada.
 * Espelha DuvidaAdminController (Fase 29), com uma diferenca de governanca:
 * aprovado/recusado/revogado (os tres desfechos que mexem com acesso a
 * ambiente/dados internos do TJRR) so' quem passa em podeGerenciar()
 * (perfil Administrador, escopado ao concurso) pode registrar - Suporte e
 * Colaborador, mesmo sendo o responsavel atual (podeAgir()), so' podem
 * escalar ou pedir esclarecimento. Ver plano da Fase 30, secao
 * "Conferência da assinatura e quem decide".
 */
class RequerimentoAdminController extends Controller
{
    const LIMITE_UPLOAD_BYTES = 15728640; // 15 MB, mesmo limite de SubmissaoService/RequerimentoController.

    private $requerimentos;
    private $respostas;
    private $escalonamentos;
    private $usuarioParticipante;
    private $notificacoes;
    private $perfis;

    public function __construct()
    {
        // exigirEmQualquerConcurso() (nao exigir()) de proposito, mesmo
        // motivo ja documentado em DuvidaAdminController: na entrada do
        // controller ainda nao se sabe qual requerimento vai ser aberto,
        // entao nao ha concurso pra comparar. A checagem fina por concurso
        // acontece depois, dentro de podeGerenciar()/podeAgir().
        RoleMiddleware::exigirEmQualquerConcurso(['administrador', 'suporte', 'colaborador']);
        $this->requerimentos = new RequerimentoRepository();
        $this->respostas = new RequerimentoRespostaRepository();
        $this->escalonamentos = new RequerimentoEscalonamentoRepository();
        $this->usuarioParticipante = new UsuarioParticipanteRepository();
        $this->notificacoes = new NotificacaoPainelRepository();
        $this->perfis = new PerfilRepository();
    }

    /**
     * Tela propria do Colaborador (sem acesso ao Painel administrativo) -
     * mesma solucao ja usada por DuvidaAdminController::minhasEscaladas().
     */
    public function minhasEscaladas()
    {
        $statusFiltro = isset($_GET['status']) ? $_GET['status'] : null;
        $minhas = $this->requerimentos->listarPorResponsavel(Auth::usuarioId(), $statusFiltro);

        foreach ($minhas as &$item) {
            $item['atrasado'] = $this->requerimentoEmAtraso($item);
        }
        unset($item);

        $this->renderizar('admin/requerimentos/minhas_escaladas', [
            'minhasEscaladas' => $minhas,
            'statusFiltro' => $statusFiltro,
            'flash' => !empty($_SESSION['flash']) ? $_SESSION['flash'] : null,
        ], 'Requerimentos');

        unset($_SESSION['flash']);
    }

    public function ver($id)
    {
        $requerimento = $this->requerimentos->buscarPorId($id);

        if ($requerimento === null) {
            http_response_code(404);
            exit('Requerimento não encontrado.');
        }

        if (!$this->podeAgir($requerimento)) {
            http_response_code(403);
            exit('Acesso negado: este requerimento está com outro responsável.');
        }

        $this->renderizar('admin/requerimentos/ver', [
            'requerimento' => $requerimento,
            'respostas' => $this->respostas->listarPorRequerimento((int) $id),
            'escalonamentos' => $this->escalonamentos->listarPorRequerimento((int) $id),
            'atendentesDisponiveis' => $this->atendentesDisponiveis((int) $requerimento['concurso_id']),
            'podeGerenciar' => $this->podeGerenciar($requerimento),
            'atrasado' => $this->requerimentoEmAtraso($requerimento),
            'certificadoDeclarado' => $this->certificadoDeclaradoOuNulo($requerimento),
            'flash' => !empty($_SESSION['flash']) ? $_SESSION['flash'] : null,
        ], 'Requerimento');

        unset($_SESSION['flash']);
    }

    /**
     * Aceita quatro desfechos (aprovado/recusado/esclarecimento_solicitado/
     * revogado). "revogado" so' e' aceito a partir de status='aprovado' -
     * os outros tres, a partir de 'recebido'/'escalado'. aprovado/recusado/
     * revogado exigem podeGerenciar() (Administrador); Suporte/Colaborador
     * autorizados so' por podeAgir() (responsavel atual) so' conseguem
     * esclarecimento_solicitado.
     */
    public function responder($id)
    {
        $requerimento = $this->requerimentos->buscarPorId($id);

        if ($requerimento === null) {
            http_response_code(404);
            exit('Requerimento não encontrado.');
        }

        $desfecho = isset($_POST['desfecho']) ? $_POST['desfecho'] : '';

        if (!in_array($desfecho, ['aprovado', 'recusado', 'esclarecimento_solicitado', 'revogado'], true)) {
            $_SESSION['flash'] = 'Desfecho inválido.';
            $this->redirecionar('requerimentoAdmin/ver/' . (int) $id);
            return;
        }

        $statusPermiteEsteDesfecho = $desfecho === 'revogado'
            ? $requerimento['status'] === 'aprovado'
            : in_array($requerimento['status'], ['recebido', 'escalado'], true);

        if (!$statusPermiteEsteDesfecho || !$this->podeAgir($requerimento)) {
            http_response_code(403);
            exit('Acesso negado ou pedido em status que não permite esta ação.');
        }

        $desfechoDefinitivo = in_array($desfecho, ['aprovado', 'recusado', 'revogado'], true);

        if ($desfechoDefinitivo && !$this->podeGerenciar($requerimento)) {
            http_response_code(403);
            exit('Acesso negado: só o Administrador pode registrar este desfecho.');
        }

        $resposta = trim(isset($_POST['resposta']) ? $_POST['resposta'] : '');

        if ($resposta === '') {
            $_SESSION['flash'] = 'Escreva o texto da resposta antes de enviar.';
            $this->redirecionar('requerimentoAdmin/ver/' . (int) $id);
            return;
        }

        $assinaturaConferida = isset($_POST['assinatura_conferida']) ? 1 : 0;

        if ($desfecho === 'aprovado' && $assinaturaConferida !== 1) {
            $_SESSION['flash'] = 'Confirme que validou a assinatura no site oficial do gov.br antes de aprovar.';
            $this->redirecionar('requerimentoAdmin/ver/' . (int) $id);
            return;
        }

        list($anexoPath, $anexoNomeOriginal, $erroAnexo) = $this->processarAnexoOpcional((int) $id);

        if ($erroAnexo !== null) {
            $_SESSION['flash'] = $erroAnexo;
            $this->redirecionar('requerimentoAdmin/ver/' . (int) $id);
            return;
        }

        $this->respostas->criar((int) $id, Auth::usuarioId(), $desfecho, $resposta, $assinaturaConferida, $anexoPath, $anexoNomeOriginal);

        if ($desfecho === 'esclarecimento_solicitado') {
            $this->requerimentos->reabrirParaNovoEnvio((int) $id, $requerimento['necessidade']);
        } else {
            $this->requerimentos->registrarDesfecho((int) $id, $desfecho);
        }

        $this->notificarLider($requerimento, $desfecho);

        $_SESSION['flash'] = 'Resposta registrada.';
        $this->redirecionar('requerimentoAdmin/ver/' . (int) $id);
    }

    /**
     * Abre uma janela publica curta e de uso unico (ver ValidacaoPublicaController
     * e RequerimentoRepository::gravarTokenValidacaoIti()), chama a rota que
     * validar.iti.gov.br usa no proprio site pra checar um PDF assinado a
     * partir de uma URL, e fecha a janela assim que a chamada termina -
     * sucesso ou falha. So' um apoio/atalho pra conferencia da assinatura:
     * o resultado, quando existe, aparece na tela junto do mesmo aviso que
     * ja vale pro certificado extraido localmente - nunca dispensa a
     * conferencia manual no site oficial, nem a caixa de confirmacao
     * obrigatoria pra aprovar.
     */
    public function validarIti($id)
    {
        $requerimento = $this->requerimentos->buscarPorId($id);

        if ($requerimento === null || !$this->podeAgir($requerimento)) {
            http_response_code(404);
            exit('Requerimento não encontrado.');
        }

        if ($requerimento['pdf_assinado_path'] === null || $requerimento['expurgado_em'] !== null) {
            $_SESSION['flash'] = 'Não há documento assinado disponível para validar.';
            $this->redirecionar('requerimentoAdmin/ver/' . (int) $id);
            return;
        }

        $token = bin2hex(random_bytes(32));
        $expiraEm = date('Y-m-d H:i:s', time() + 180);
        $this->requerimentos->gravarTokenValidacaoIti((int) $id, hash('sha256', $token), $expiraEm);

        $urlPublicaTemporaria = urlAbsoluta('validacaoPublica/pdf/' . (int) $id . '/' . $token);
        $resultado = ItiValidadorService::validar($urlPublicaTemporaria);

        $this->requerimentos->invalidarTokenValidacaoIti((int) $id);
        $this->requerimentos->registrarValidacaoIti((int) $id, $resultado);

        $_SESSION['flash'] = $this->mensagemResultadoIti($resultado, $requerimento);
        $this->redirecionar('requerimentoAdmin/ver/' . (int) $id);
    }

    private function mensagemResultadoIti($resultado, array $requerimento)
    {
        if ($resultado === null) {
            return 'Não foi possível confirmar automaticamente no ITI — confira manualmente no site oficial, abaixo.';
        }

        $partes = ['Validação automática ITI: ' . $resultado['status_geral']];

        if ($resultado['nome'] !== null) {
            $partes[] = 'assinante ' . $resultado['nome'];
        }

        if ($resultado['cpf'] !== null) {
            $partes[] = 'CPF ' . $resultado['cpf'];
        }

        if ($resultado['data_assinatura'] !== null) {
            $partes[] = 'assinado em ' . $resultado['data_assinatura'];
        }

        $mensagem = implode(' — ', $partes) . '. Isto não substitui a conferência manual abaixo.';
        $alerta = $this->alertaDivergenciaIti($resultado, $requerimento);

        return $alerta !== null ? ($alerta . ' ' . $mensagem) : $mensagem;
    }

    /**
     * So' um alerta a mais pra chamar atencao na conferencia manual - nunca
     * bloqueia nada sozinho (aprovar continua exigindo a caixa de
     * confirmacao, com ou sem esse alerta). O CPF que o ITI devolve vem
     * sempre mascarado (so' os 6 digitos do meio visiveis, ex.:
     * "***.666.331-**"), entao a comparacao usa so' esse miolo comum aos
     * dois lados - nunca da' pra comparar o CPF completo. Qualquer formato
     * fora do esperado (nem 6 nem 11 digitos) nao arrisca alarme falso.
     */
    private function alertaDivergenciaIti(array $resultado, array $requerimento)
    {
        $avisos = [];

        if ($resultado['nome'] !== null
            && $this->normalizarNome($resultado['nome']) !== $this->normalizarNome($requerimento['participante_nome'])) {
            $avisos[] = 'nome do assinante não bate com o líder cadastrado';
        }

        if ($resultado['cpf'] !== null && $requerimento['participante_cpf'] !== null
            && !$this->miolosCpfEquivalentes($resultado['cpf'], $requerimento['participante_cpf'])) {
            $avisos[] = 'CPF do assinante não bate com o líder cadastrado';
        }

        return !empty($avisos) ? ('⚠ ATENÇÃO: ' . implode('; ', $avisos) . '.') : null;
    }

    private function normalizarNome($nome)
    {
        $maiusculo = mb_strtoupper(trim($nome), 'UTF-8');
        $transliterado = iconv('UTF-8', 'ASCII//TRANSLIT', $maiusculo);

        return $transliterado !== false ? $transliterado : $maiusculo;
    }

    private function miolosCpfEquivalentes($cpfMascaradoIti, $cpfCadastrado)
    {
        $digitosIti = preg_replace('/\D/', '', $cpfMascaradoIti);
        $digitosCadastrado = preg_replace('/\D/', '', $cpfCadastrado);

        if (strlen($digitosIti) !== 6 || strlen($digitosCadastrado) !== 11) {
            return true;
        }

        return $digitosIti === substr($digitosCadastrado, 3, 6);
    }

    public function escalar($id)
    {
        $requerimento = $this->requerimentos->buscarPorId($id);

        if ($requerimento === null) {
            http_response_code(404);
            exit('Requerimento não encontrado.');
        }

        if (!in_array($requerimento['status'], ['recebido', 'escalado'], true) || !$this->podeAgir($requerimento)) {
            http_response_code(403);
            exit('Acesso negado ou pedido em status que não permite escalar.');
        }

        $novoResponsavelId = (int) (isset($_POST['usuario_id']) ? $_POST['usuario_id'] : 0);
        $disponiveis = $this->atendentesDisponiveis((int) $requerimento['concurso_id']);

        if (!in_array($novoResponsavelId, array_column($disponiveis, 'id'), false)) {
            $_SESSION['flash'] = 'Selecione um administrador, suporte ou colaborador válido.';
            $this->redirecionar('requerimentoAdmin/ver/' . (int) $id);
            return;
        }

        $this->escalonamentos->criar((int) $id, Auth::usuarioId(), $novoResponsavelId);
        $this->requerimentos->escalar((int) $id, $novoResponsavelId);
        $this->notificarResponsavel($novoResponsavelId, $requerimento);

        $_SESSION['flash'] = 'Requerimento escalado.';
        $this->redirecionar('requerimentoAdmin/ver/' . (int) $id);
    }

    /**
     * Admin puxando de volta uma escalada parada sem resposta - diferente
     * do fluxo do lider (que so' reabre apos esclarecimento_solicitado).
     */
    public function retomar($id)
    {
        $requerimento = $this->requerimentos->buscarPorId($id);

        if ($requerimento === null) {
            http_response_code(404);
            exit('Requerimento não encontrado.');
        }

        if ($requerimento['status'] !== 'escalado' || !$this->podeGerenciar($requerimento)) {
            http_response_code(403);
            exit('Só é possível retomar (Administrador) um requerimento escalado, dentro do seu escopo.');
        }

        $this->requerimentos->retomar((int) $id);
        $this->notificarAdministradoresFilaGeral($requerimento);

        $_SESSION['flash'] = 'Requerimento retomado — de volta à fila geral.';
        $this->redirecionar('home/administrativo');
    }

    public function baixarAssinado($id)
    {
        $requerimento = $this->requerimentos->buscarPorId($id);

        if ($requerimento === null || !$this->podeAgir($requerimento)) {
            http_response_code(404);
            exit('Requerimento não encontrado.');
        }

        if ($requerimento['expurgado_em'] !== null) {
            http_response_code(404);
            exit('Este documento foi expurgado em ' . formatarData($requerimento['expurgado_em']) . ', conforme a política de retenção de dados.');
        }

        if ($requerimento['pdf_assinado_path'] === null) {
            http_response_code(404);
            exit('Nenhum documento assinado enviado ainda.');
        }

        ArquivoPrivadoService::servir($requerimento['pdf_assinado_path'], $requerimento['pdf_assinado_nome_original']);
    }

    public function baixarAnexoResposta($id, $respostaId)
    {
        $requerimento = $this->requerimentos->buscarPorId($id);

        if ($requerimento === null || !$this->podeAgir($requerimento)) {
            http_response_code(404);
            exit('Requerimento não encontrado.');
        }

        $resposta = $this->respostas->buscarPorId($respostaId);

        if ($resposta === null || (int) $resposta['requerimento_id'] !== (int) $id) {
            http_response_code(404);
            exit('Resposta não encontrada.');
        }

        if ($resposta['anexo_expurgado_em'] !== null) {
            http_response_code(404);
            exit('Este documento foi expurgado em ' . formatarData($resposta['anexo_expurgado_em']) . ', conforme a política de retenção de dados.');
        }

        if ($resposta['anexo_path'] === null) {
            http_response_code(404);
            exit('Esta resposta não tem anexo.');
        }

        ArquivoPrivadoService::servir($resposta['anexo_path'], $resposta['anexo_nome_original']);
    }

    /**
     * Administrador com escopo sobre o concurso do requerimento - poder de
     * supervisao (responder/escalar/retomar QUALQUER requerimento nao
     * terminal do(s) concurso(s) dele, nao so' os que estao com ele).
     */
    private function podeGerenciar(array $requerimento)
    {
        if (!Auth::possuiPerfil('administrador')) {
            return false;
        }

        $concursosPermitidos = $this->perfis->concursosDoUsuario(Auth::usuarioId(), 'administrador');

        return $concursosPermitidos === null || in_array((int) $requerimento['concurso_id'], $concursosPermitidos, true);
    }

    /**
     * Administrador no escopo (podeGerenciar) OU sou eu mesmo o responsavel
     * atual (Suporte/Colaborador, ou Administrador que recebeu
     * escalonamento pessoal).
     */
    private function podeAgir(array $requerimento)
    {
        if ($this->podeGerenciar($requerimento)) {
            return true;
        }

        return $requerimento['responsavel_atual_usuario_id'] !== null
            && (int) $requerimento['responsavel_atual_usuario_id'] === (int) Auth::usuarioId();
    }

    /**
     * Extracao best-effort do que o certificado embutido no PDF assinado
     * declara (nome/CPF) - so' tenta se ha' um arquivo assinado, ainda nao
     * expurgado. Nunca bloqueia a tela: qualquer falha na extracao (ver
     * CertificadoExtratorService) devolve null aqui, e a view so' deixa de
     * mostrar o quadro de apoio, sem afetar o resto do fluxo.
     */
    private function certificadoDeclaradoOuNulo(array $requerimento)
    {
        if ($requerimento['pdf_assinado_path'] === null || $requerimento['expurgado_em'] !== null) {
            return null;
        }

        $caminhoFisico = ArquivoPrivadoService::caminhoFisico($requerimento['pdf_assinado_path']);

        return $caminhoFisico !== null ? CertificadoExtratorService::extrairDeclarado($caminhoFisico) : null;
    }

    private function requerimentoEmAtraso(array $requerimento)
    {
        if (!in_array($requerimento['status'], ['recebido', 'escalado'], true)) {
            return false;
        }

        return SlaService::emAtraso($requerimento['enviado_em']);
    }

    /**
     * Administrador/suporte/colaborador do concurso do requerimento,
     * exceto eu mesmo - mesmo padrao de DuvidaAdminController::
     * atendentesDisponiveis().
     */
    private function atendentesDisponiveis($concursoId)
    {
        $porId = [];

        foreach (['administrador', 'suporte', 'colaborador'] as $perfilChave) {
            foreach ($this->perfis->listarUsuariosPorPerfilConcurso($perfilChave, $concursoId) as $usuario) {
                if ((int) $usuario['id'] !== (int) Auth::usuarioId()) {
                    $porId[(int) $usuario['id']] = $usuario;
                }
            }
        }

        usort($porId, function ($a, $b) {
            return strcmp($a['nome'], $b['nome']);
        });

        return array_values($porId);
    }

    private function notificarLider(array $requerimento, $desfecho)
    {
        $rotulos = [
            'aprovado' => 'Requerimento aprovado',
            'recusado' => 'Requerimento recusado',
            'esclarecimento_solicitado' => 'Esclarecimento solicitado no seu requerimento',
            'revogado' => 'Acesso revogado',
        ];

        foreach ($this->usuarioParticipante->usuariosDoParticipante($requerimento['participante_id']) as $usuarioId) {
            $this->notificacoes->criar(
                $usuarioId,
                'requerimento_' . $desfecho,
                $rotulos[$desfecho],
                'Seu requerimento "' . $requerimento['modelo_nome'] . '" teve uma nova resposta.',
                ['url' => url('requerimento/ver/' . (int) $requerimento['id'])]
            );
        }
    }

    private function notificarResponsavel($usuarioId, array $requerimento)
    {
        $this->notificacoes->criar(
            (int) $usuarioId,
            'requerimento_escalado',
            'Requerimento escalado para você',
            '"' . $requerimento['participante_nome'] . '" (equipe "' . $requerimento['nome_equipe'] . '") - requerimento escalado pra você.',
            ['url' => url('requerimentoAdmin/ver/' . (int) $requerimento['id'])]
        );
    }

    private function notificarAdministradoresFilaGeral(array $requerimento)
    {
        foreach ($this->perfis->listarUsuariosPorPerfilConcurso('administrador', (int) $requerimento['concurso_id']) as $admin) {
            $this->notificacoes->criar(
                (int) $admin['id'],
                'requerimento_novo',
                'Requerimento de volta à fila geral',
                '"' . $requerimento['participante_nome'] . '" (equipe "' . $requerimento['nome_equipe'] . '") - requerimento retomado, aguardando novo atendimento.',
                ['url' => url('requerimentoAdmin/ver/' . (int) $requerimento['id'])]
            );
        }
    }

    private function processarAnexoOpcional($requerimentoId)
    {
        if (empty($_FILES['anexo']) || $_FILES['anexo']['error'] === UPLOAD_ERR_NO_FILE) {
            return [null, null, null];
        }

        $validacao = UploadPdfValidador::validar($_FILES['anexo'], self::LIMITE_UPLOAD_BYTES);

        if (!$validacao['valido']) {
            return [null, null, $validacao['mensagem']];
        }

        try {
            $caminho = ArquivoPrivadoService::salvar($_FILES['anexo'], 'requerimentos/' . $requerimentoId . '/respostas');
        } catch (\RuntimeException $e) {
            return [null, null, $e->getMessage()];
        }

        return [$caminho, $_FILES['anexo']['name'], null];
    }
}
