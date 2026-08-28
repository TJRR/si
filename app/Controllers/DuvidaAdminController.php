<?php

namespace App\Controllers;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Core\Auth;
use App\Core\Controller;
use App\Middleware\RoleMiddleware;
use App\Repositories\ConcursoRepository;
use App\Repositories\DuvidaEscalonamentoRepository;
use App\Repositories\DuvidaRepository;
use App\Repositories\DuvidaRespostaRepository;
use App\Repositories\EquipeRepository;
use App\Repositories\FaqConcursoRepository;
use App\Repositories\NotificacaoPainelRepository;
use App\Repositories\PerfilRepository;
use App\Repositories\PerguntaFrequenteRepository;
use App\Repositories\UsuarioParticipanteRepository;
use App\Services\ArquivoService;
use App\Services\PermissaoParticipanteService;

/**
 * Fase 29 (Tira-Duvidas): atendimento (administrador/suporte) - responder,
 * escalar pra outro atendente, retomar uma escalada parada (painel de
 * gestao) e o dashboard consolidado.
 *
 * Autorizacao SEMPRE checada dentro de cada acao (nao so' no filtro da
 * listagem) - ver podeGerenciar()/podeAgir(). Administrador so' age dentro
 * do(s) concurso(s) que ele acessa (concursosDoUsuario() - null = global,
 * sem filtro); Suporte so' age na duvida que estiver com ele
 * (responsavel_atual_usuario_id), nunca na fila geral nem na de outro
 * atendente.
 */
class DuvidaAdminController extends Controller
{
    /** Espelha perguntas_frequentes.pergunta VARCHAR(255) (migration 070). */
    const LIMITE_PERGUNTA = 255;

    private $duvidas;
    private $respostas;
    private $escalonamentos;
    private $equipes;
    private $usuarioParticipante;
    private $notificacoes;
    private $perfis;
    private $arquivos;
    private $faqs;
    private $faqConcurso;
    private $concursos;

    public function __construct()
    {
        // Fase 29 (ajuste pos-push): 'colaborador' entra so' aqui, nao nos
        // outros 10+ controllers que aceitam 'suporte' - podeAgir() ja
        // restringe a acao a duvida onde ele e' o responsavel atual,
        // entao nao precisa de mais nenhuma checagem especial pra ele.
        //
        // exigirEmQualquerConcurso() (nao exigir()) de proposito: exigir()
        // usa Auth::temPerfil(), que so' reconhece o vinculo se ele for
        // GLOBAL (concurso_id NULL) ou se o concurso for passado pra
        // comparar - e aqui, na entrada do controller, ainda nao se sabe
        // qual duvida vai ser aberta, entao nao ha concurso pra passar.
        // Um colaborador/suporte/administrador escopado a UM concurso
        // especifico (nao "Global") cairia em "Acesso negado" mesmo sendo
        // o perfil certo - mesmo bug ja corrigido antes em
        // HomeController::administrativo() (ver comentario la'). A checagem
        // fina por concurso de fato acontece depois, dentro de
        // podeGerenciar()/podeAgir(), por duvida especifica.
        RoleMiddleware::exigirEmQualquerConcurso(['administrador', 'suporte', 'colaborador']);
        $this->duvidas = new DuvidaRepository();
        $this->respostas = new DuvidaRespostaRepository();
        $this->escalonamentos = new DuvidaEscalonamentoRepository();
        $this->equipes = new EquipeRepository();
        $this->usuarioParticipante = new UsuarioParticipanteRepository();
        $this->notificacoes = new NotificacaoPainelRepository();
        $this->perfis = new PerfilRepository();
        $this->arquivos = new ArquivoService();
        $this->faqs = new PerguntaFrequenteRepository();
        $this->faqConcurso = new FaqConcursoRepository();
        $this->concursos = new ConcursoRepository();
    }

    /**
     * Fase 29 (ajuste pos-push): tela dedicada, exclusiva do perfil
     * Colaborador (pessoa externa ao NPI, sem acesso ao Painel
     * administrativo) - mesma listagem "Escaladas para mim" que
     * administrador/suporte ja veem dentro de home/administrativo, so' que
     * numa pagina propria e minima, sem estatisticas nem fila geral.
     */
    public function minhasEscaladas()
    {
        $statusFiltro = isset($_GET['status']) ? $_GET['status'] : null;
        $minhasEscaladas = $this->duvidas->listarPorResponsavel(Auth::usuarioId(), $statusFiltro);

        foreach ($minhasEscaladas as &$item) {
            $item['atrasada'] = $this->emAtraso($item);
        }
        unset($item);

        $this->renderizar('admin/duvidas/minhas_escaladas', [
            'minhasEscaladas' => $minhasEscaladas,
            'statusFiltro' => $statusFiltro,
            'flash' => !empty($_SESSION['flash']) ? $_SESSION['flash'] : null,
        ], 'Dúvidas');

        unset($_SESSION['flash']);
    }

    public function ver($id)
    {
        $duvida = $this->duvidas->buscarPorId((int) $id);

        if ($duvida === null) {
            http_response_code(404);
            exit('Dúvida não encontrada.');
        }

        if (!$this->podeAgir($duvida)) {
            http_response_code(403);
            exit('Acesso negado: esta dúvida está com outro responsável.');
        }

        $respostas = $this->respostas->listarPorDuvida((int) $id);

        $this->renderizar('admin/duvidas/ver', [
            'duvida' => $duvida,
            'estadoParticipante' => (new PermissaoParticipanteService())->estadoDoParticipante((int) $duvida['participante_id']),
            'respostas' => $respostas,
            'escalonamentos' => $this->escalonamentos->listarPorDuvida((int) $id),
            'atendentesDisponiveis' => $this->atendentesDisponiveis((int) $duvida['concurso_id']),
            'limiteMB' => ArquivoService::limiteMaximoMB(),
            'atrasada' => $this->emAtraso($duvida),
            // Fase 35: dois flags distintos de proposito. O card da acao so'
            // aparece com resposta ja registrada; o LINK do selo "ja virou
            // FAQ" depende so' do perfil, porque leva a FaqAdminController,
            // que exige perfil global - pra quem nao tem, o selo aparece sem
            // link, em vez de oferecer um caminho que da' 403.
            'podePromoverFaq' => $this->podePromoverFaq() && !empty($respostas),
            'perfilPublicaFaq' => $this->podePromoverFaq(),
            'faqsGerados' => $this->faqs->listarPorDuvida((int) $id),
            'flash' => !empty($_SESSION['flash']) ? $_SESSION['flash'] : null,
        ], 'Dúvida');

        unset($_SESSION['flash']);
    }

    public function responder($id)
    {
        $duvida = $this->duvidas->buscarPorId((int) $id);

        if ($duvida === null) {
            http_response_code(404);
            exit('Dúvida não encontrada.');
        }

        if ($duvida['status'] === 'respondida' || !$this->podeAgir($duvida)) {
            http_response_code(403);
            exit('Acesso negado ou dúvida já respondida.');
        }

        $resposta = trim(isset($_POST['resposta']) ? $_POST['resposta'] : '');

        if ($resposta === '') {
            flashErro('Escreva a resposta antes de enviar.');
            $this->redirecionar('duvidaAdmin/ver/' . (int) $id);
            return;
        }

        list($anexoPath, $anexoNomeOriginal, $erroAnexo) = $this->processarAnexoOpcional();

        if ($erroAnexo !== null) {
            flashErro($erroAnexo);
            $this->redirecionar('duvidaAdmin/ver/' . (int) $id);
            return;
        }

        $this->respostas->criar((int) $id, Auth::usuarioId(), $resposta, $anexoPath, $anexoNomeOriginal);
        $this->duvidas->responder((int) $id);
        $this->notificarEquipe($duvida, 'Dúvida respondida', 'Sua dúvida foi respondida.');

        $_SESSION['flash'] = 'Resposta enviada.';
        $this->redirecionar('duvidaAdmin/ver/' . (int) $id);
    }

    public function escalar($id)
    {
        $duvida = $this->duvidas->buscarPorId((int) $id);

        if ($duvida === null) {
            http_response_code(404);
            exit('Dúvida não encontrada.');
        }

        if ($duvida['status'] === 'respondida' || !$this->podeAgir($duvida)) {
            http_response_code(403);
            exit('Acesso negado ou dúvida já respondida.');
        }

        $novoResponsavelId = (int) (isset($_POST['usuario_id']) ? $_POST['usuario_id'] : 0);
        $disponiveis = $this->atendentesDisponiveis((int) $duvida['concurso_id']);

        if (!in_array($novoResponsavelId, array_column($disponiveis, 'id'), false)) {
            flashErro('Selecione um administrador ou suporte válido.');
            $this->redirecionar('duvidaAdmin/ver/' . (int) $id);
            return;
        }

        $this->escalonamentos->criar((int) $id, Auth::usuarioId(), $novoResponsavelId);
        $this->duvidas->escalar((int) $id, $novoResponsavelId);
        $this->notificarResponsavel($novoResponsavelId, $duvida);

        $_SESSION['flash'] = 'Dúvida escalada.';
        $this->redirecionar('duvidaAdmin/ver/' . (int) $id);
    }

    /**
     * Admin puxando de volta uma escalada parada sem resposta (painel de
     * gestao) - diferente de DuvidaController::reabrir(), que e' o
     * participante reabrindo uma ja respondida.
     */
    public function retomar($id)
    {
        $duvida = $this->duvidas->buscarPorId((int) $id);

        if ($duvida === null) {
            http_response_code(404);
            exit('Dúvida não encontrada.');
        }

        if ($duvida['status'] !== 'escalada' || !$this->podeGerenciar($duvida)) {
            http_response_code(403);
            exit('Só é possível retomar (Administrador) uma dúvida escalada, dentro do seu escopo.');
        }

        $this->duvidas->retomar((int) $id);
        $this->notificarAdministradoresFilaGeral($duvida);

        $_SESSION['flash'] = 'Dúvida retomada — de volta à fila geral.';
        $this->redirecionar('home/administrativo');
    }

    /**
     * Fase 35: aproveita uma duvida ja respondida como pergunta/resposta
     * GENERICA no banco de FAQ. A duvida original e a resposta original
     * ficam intactas e exclusivas da equipe que perguntou - o que nasce aqui
     * e' um item NOVO, editavel, sem vinculo de exibicao com a equipe de
     * origem (perguntas_frequentes.duvida_id e' rastro interno de auditoria,
     * ver migration 114, e nunca vai pra home).
     *
     * NUNCA grava direto: sempre abre o formulario pre-preenchido, com
     * edicao obrigatoria antes de salvar. O texto de uma duvida foi escrito
     * por um participante e pode conter nome de equipe, nome de projeto,
     * dado pessoal e detalhe de submissao sob sigilo - e o destino dele aqui
     * e' uma pagina publica.
     *
     * A duvida nao muda de status nem de conteudo, e a equipe autora NAO e'
     * notificada (decisao da fase): o item publicado e' generico e reescrito,
     * avisar so' chamaria atencao pra um vinculo que o desenho apaga.
     */
    public function promoverFaq($id)
    {
        $duvida = $this->duvidas->buscarPorId((int) $id);

        if ($duvida === null) {
            http_response_code(404);
            exit('Dúvida não encontrada.');
        }

        if (!$this->podeAgir($duvida)) {
            http_response_code(403);
            exit('Acesso negado: esta dúvida está com outro responsável.');
        }

        if (!$this->podePromoverFaq()) {
            http_response_code(403);
            exit('Acesso negado: publicar no banco de perguntas frequentes exige perfil global.');
        }

        $respostas = $this->respostas->listarPorDuvida((int) $id);

        // Nao "status === respondida": uma duvida REABERTA volta pra
        // 'recebida' mantendo o historico de respostas, e continua sendo
        // material legitimo pra FAQ. O que importa e' existir resposta.
        if (empty($respostas)) {
            flashAlerta('Só é possível transformar em pergunta frequente uma dúvida que já tenha resposta.');
            $this->redirecionar('duvidaAdmin/ver/' . (int) $id);
            return;
        }

        $concursos = $this->concursos->listar();
        $erro = null;
        $entrada = $_SERVER['REQUEST_METHOD'] === 'POST'
            ? $this->entradaFaqDoPost()
            : $this->sementeFaq($duvida, $respostas);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $erro = $this->validarPromocao($entrada, $concursos);

            if ($erro === null) {
                $faqId = $this->faqs->criar(
                    $entrada['pergunta'],
                    $entrada['resposta'],
                    $entrada['categoria'] !== '' ? $entrada['categoria'] : null,
                    (int) $id
                );

                if ($entrada['ativar']) {
                    $this->faqConcurso->ativar($faqId, $entrada['concurso_id']);
                    flashSucesso('Pergunta criada no banco geral e ativada na edição escolhida — já aparece na home.');
                } else {
                    flashAlerta('Pergunta criada no banco geral. Ela ainda NÃO aparece em nenhuma home: ative-a em "FAQ desta edição" quando quiser publicar.');
                }

                $this->redirecionar('duvidaAdmin/ver/' . (int) $id);
                return;
            }
        }

        $this->renderizar('admin/duvidas/promover_faq', [
            'erro' => $erro,
            'duvida' => $duvida,
            'respostas' => $respostas,
            'entrada' => $entrada,
            'concursos' => $concursos,
            'faqsGerados' => $this->faqs->listarPorDuvida((int) $id),
            'limitePergunta' => self::LIMITE_PERGUNTA,
        ], 'Transformar dúvida em pergunta frequente');
    }

    /**
     * Fase 35: promover ESCREVE no banco global de perguntas
     * (perguntas_frequentes), que e' acumulativo entre TODAS as edicoes - por
     * isso o criterio e' o mesmo do construtor de FaqAdminController: perfil
     * GLOBAL. temPerfil() sem $concursoId so' aceita vinculo com concurso_id
     * NULL. Quem e' escopado a um concurso atende a duvida normalmente
     * (responder/escalar), mas nao publica no banco geral - o FAQ que ele
     * administra e' o da edicao dele, em FaqConcursoAdminController.
     *
     * Colaborador fica de fora por consequencia: nao tem nem um nem outro.
     */
    private function podePromoverFaq()
    {
        return Auth::temPerfil('administrador') || Auth::temPerfil('suporte');
    }

    /**
     * Fase 35: semente do formulario de promocao. A resposta usada e' a MAIS
     * RECENTE - listarPorDuvida() vem ORDER BY criado_em ASC, entao e' a
     * ULTIMA do array, nao a primeira (duvida reaberta acumula respostas). As
     * anteriores vao pra tela em somente-leitura, pra copiar trecho.
     *
     * perguntas_frequentes.pergunta e' VARCHAR(255) e duvidas.pergunta e'
     * TEXT: a semente e' cortada pra caber e a tela avisa quando isso
     * acontece. O admin reescreve de qualquer jeito - o desabafo de uma
     * duvida raramente e' uma boa pergunta generica.
     */
    private function sementeFaq(array $duvida, array $respostas)
    {
        $ultima = end($respostas);

        return [
            'pergunta' => mb_substr(trim($duvida['pergunta']), 0, self::LIMITE_PERGUNTA),
            'resposta' => trim($ultima['resposta']),
            'categoria' => '',
            'ativar' => true,
            'concurso_id' => (int) $duvida['concurso_id'],
        ];
    }

    private function entradaFaqDoPost()
    {
        return [
            'pergunta' => trim(isset($_POST['pergunta']) ? $_POST['pergunta'] : ''),
            'resposta' => trim(isset($_POST['resposta']) ? $_POST['resposta'] : ''),
            'categoria' => trim(isset($_POST['categoria']) ? $_POST['categoria'] : ''),
            'ativar' => isset($_POST['destino']) && $_POST['destino'] === 'ativar',
            'concurso_id' => (int) (isset($_POST['concurso_id']) ? $_POST['concurso_id'] : 0),
        ];
    }

    private function validarPromocao(array $entrada, array $concursos)
    {
        if ($entrada['pergunta'] === '') {
            return 'Escreva a pergunta.';
        }

        // Espelha perguntas_frequentes.pergunta VARCHAR(255) (migration 070):
        // sem esta checagem o MySQL trunca em silencio - ou erra, em strict
        // mode - uma pergunta colada inteira da duvida, que e' TEXT.
        if (mb_strlen($entrada['pergunta']) > self::LIMITE_PERGUNTA) {
            return 'A pergunta deve ter no máximo ' . self::LIMITE_PERGUNTA . ' caracteres.';
        }

        if ($entrada['resposta'] === '') {
            return 'Escreva a resposta.';
        }

        if ($entrada['ativar'] && !in_array($entrada['concurso_id'], array_map('intval', array_column($concursos, 'id')), true)) {
            return 'Selecione a edição em que a pergunta deve ficar ativa.';
        }

        return null;
    }

    /**
     * Administrador com escopo sobre o concurso da duvida - poder de
     * supervisao (responder/escalar/retomar QUALQUER duvida nao respondida
     * do(s) concurso(s) dele, nao so' as que estao com ele).
     */
    private function podeGerenciar(array $duvida)
    {
        if (!Auth::possuiPerfil('administrador')) {
            return false;
        }

        $concursosPermitidos = $this->perfis->concursosDoUsuario(Auth::usuarioId(), 'administrador');

        return $concursosPermitidos === null || in_array((int) $duvida['concurso_id'], $concursosPermitidos, true);
    }

    /**
     * Administrador no escopo (podeGerenciar) OU sou eu mesmo o responsavel
     * atual (Suporte, ou Administrador que recebeu escalonamento pessoal).
     */
    private function podeAgir(array $duvida)
    {
        if ($this->podeGerenciar($duvida)) {
            return true;
        }

        return $duvida['responsavel_atual_usuario_id'] !== null
            && (int) $duvida['responsavel_atual_usuario_id'] === (int) Auth::usuarioId();
    }

    private function emAtraso(array $duvida)
    {
        if ($duvida['status'] === 'respondida') {
            return false;
        }

        $referencia = $duvida['reaberta_em'] !== null ? $duvida['reaberta_em'] : $duvida['criado_em'];

        // Fase 30: calculo extraido pra App\Services\SlaService (usado
        // tambem por RequerimentoAdminController) - so' mudou de lugar,
        // mesma logica de antes.
        return \App\Services\SlaService::emAtraso($referencia);
    }

    /**
     * Administrador/suporte do concurso da duvida, exceto eu mesmo (nao faz
     * sentido "escalar pra mim mesmo") - mesmo padrao de
     * MentoriaAdminController::mentoresDisponiveis().
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

    private function notificarEquipe(array $duvida, $titulo, $mensagem)
    {
        foreach ($this->equipes->listarParticipantes((int) $duvida['equipe_id']) as $participante) {
            foreach ($this->usuarioParticipante->usuariosDoParticipante($participante['id']) as $usuarioId) {
                $this->notificacoes->criar($usuarioId, 'duvida_respondida', $titulo, $mensagem, ['url' => url('duvida/ver/' . (int) $duvida['id'])]);
            }
        }
    }

    private function notificarResponsavel($usuarioId, array $duvida)
    {
        $this->notificacoes->criar(
            (int) $usuarioId,
            'duvida_escalada',
            'Dúvida escalada para você',
            '"' . $duvida['participante_nome'] . '" (equipe "' . $duvida['nome_equipe'] . '") - dúvida escalada pra você.',
            ['url' => url('duvidaAdmin/ver/' . (int) $duvida['id'])]
        );
    }

    private function notificarAdministradoresFilaGeral(array $duvida)
    {
        foreach ($this->perfis->listarUsuariosPorPerfilConcurso('administrador', (int) $duvida['concurso_id']) as $admin) {
            $this->notificacoes->criar(
                (int) $admin['id'],
                'duvida_nova',
                'Dúvida de volta à fila geral',
                '"' . $duvida['participante_nome'] . '" (equipe "' . $duvida['nome_equipe'] . '") - dúvida retomada, aguardando novo atendimento.',
                ['url' => url('duvidaAdmin/ver/' . (int) $duvida['id'])]
            );
        }
    }

    private function processarAnexoOpcional()
    {
        if (empty($_FILES['anexo']) || $_FILES['anexo']['error'] === UPLOAD_ERR_NO_FILE) {
            return [null, null, null];
        }

        if ($_FILES['anexo']['error'] !== UPLOAD_ERR_OK) {
            return [null, null, 'Falha no envio do anexo.'];
        }

        try {
            $caminho = $this->arquivos->salvar($_FILES['anexo'], 'duvidas');
        } catch (\RuntimeException $e) {
            return [null, null, $e->getMessage()];
        }

        return [$caminho, $_FILES['anexo']['name'], null];
    }
}
