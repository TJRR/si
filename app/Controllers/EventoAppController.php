<?php

namespace App\Controllers;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Core\Auth;
use App\Core\Controller;
use App\Core\View;
use App\Repositories\ConfiguracaoSistemaRepository;
use App\Repositories\EventoAtividadeFacilitadorRepository;
use App\Repositories\EventoAtividadeInscricaoRepository;
use App\Repositories\EventoAtividadeLeituraFalhaRepository;
use App\Repositories\EventoAtividadeRepository;
use App\Repositories\EventoCampoInscricaoRepository;
use App\Repositories\EventoCheckinRepository;
use App\Repositories\EventoComunicacaoRepository;
use App\Repositories\EventoInscricaoRepository;
use App\Repositories\LeituraCodigoFalhaRepository;
use App\Repositories\NotificacaoPainelRepository;
use App\Repositories\PerfilRepository;
use App\Repositories\SemanaInovacaoRepository;
use App\Repositories\TemaVisualRepository;
use App\Repositories\TrabalhoAutorRepository;
use App\Repositories\TrabalhoAvaliadorRepository;
use App\Services\NotificacaoService;

/**
 * Fase 41: ponto de entrada ("shell") do aplicativo web instalavel (PWA) do
 * Evento - start_url do manifesto (ver manifesto()). Nome do modulo evita
 * "Evento" generico sozinho (mesma convencao de EventoInscricaoPublicaController)
 * e nao colide com o modulo 'participante' ja usado pelo fluxo do Concurso
 * (ParticipanteController).
 *
 * Sem conteudo funcional ainda (codigo de credenciamento e leitura de QR sao
 * Fases 42/43) - so' a casca navegavel: decide entre mandar a pessoa se
 * inscrever ou mostrar o painel de quem ja esta inscrita.
 */
class EventoAppController extends Controller
{
    public function index($id = null)
    {
        if (!Auth::autenticado()) {
            // Propaga o $id (quando presente) para nao perder o evento de
            // destino num link direto tipo eventoApp/index/3 - sem isso, a
            // pessoa voltaria do login para o evento mais recente em vez do
            // que ela pretendia abrir. AuthController::redirecionarPosLogin()
            // aceita este padrao com /<id> opcional.
            $_SESSION['retorno_apos_login'] = [
                'destino' => 'eventoApp/index' . ($id !== null ? '/' . (int) $id : ''),
                'expira_em' => time() + 1800,
            ];
            $this->redirecionar('eventoInscricao/index' . ($id !== null ? '/' . (int) $id : ''));
            return;
        }

        $inscricoes = (new EventoInscricaoRepository())->listarPorUsuario(Auth::usuarioId());

        if (empty($inscricoes)) {
            // Fase 48: quem nunca se inscreveu como participante, mas foi
            // designado facilitador de alguma atividade (ex.: magistrado
            // convidado so' para instruir, sem inscricao propria no evento),
            // vai para a tela de facilitacoes em vez do formulario de
            // inscricao - sem este desvio, essa pessoa nunca conseguiria
            // acessar a propria tela de codigo de presenca online.
            $facilitacoes = (new EventoAtividadeFacilitadorRepository())->listarPorUsuarioEmQualquerEvento(Auth::usuarioId());

            if (!empty($facilitacoes)) {
                $this->redirecionar('eventoApp/facilitacoes/' . (int) $facilitacoes[0]['evento_id']);
                return;
            }

            // Fase 49 (achado da revisao do plano), atualizado na Fase 49B:
            // mesmo desvio do Facilitador acima, agora para quem ganhou o
            // perfil `inscrito` so' por ser autor principal de algum
            // Trabalho, sem nunca ter se inscrito no evento. A rota de
            // destino (trabalho/meusTrabalhos) passou a fazer parte do
            // aplicativo instalavel na Fase 49B (decisao revista do usuario -
            // ja nao e' "fora do app" como o plano original registrava),
            // mas continua sendo a mesma rota, so' com aparencia/instalacao
            // diferentes agora (ver $ehAppEvento em layout.php).
            if ((new TrabalhoAutorRepository())->possuiTrabalhoEmQualquerEvento(Auth::usuarioId())) {
                $this->redirecionar('trabalho/meusTrabalhos');
                return;
            }

            // Fase 49B, achado do teste de fumaça (item 7): mesmo desvio,
            // agora para quem ganhou o perfil `inscrito` so' por ser
            // avaliador avulso de Trabalhos em algum evento. Diferente do
            // autor, a avaliacao continua deliberadamente fora do
            // aplicativo instalavel (decisao confirmada na Fase 49B) -
            // destino e' avaliacaoTrabalhos/index, navegador comum.
            if ((new TrabalhoAvaliadorRepository())->ehAvaliadorEmQualquerEvento(Auth::usuarioId())) {
                $this->redirecionar('avaliacaoTrabalhos/index');
                return;
            }

            $this->redirecionar('eventoInscricao/index' . ($id !== null ? '/' . (int) $id : ''));
            return;
        }

        if (count($inscricoes) === 1) {
            $this->exibirPainel($inscricoes[0]);
            return;
        }

        if ($id !== null) {
            foreach ($inscricoes as $inscricao) {
                if ((int) $inscricao['evento_id'] === (int) $id) {
                    $this->exibirPainel($inscricao);
                    return;
                }
            }
        }

        $this->renderizar('eventoApp/selecionar', [
            'inscricoes' => $inscricoes,
        ], 'Meus eventos');
    }

    private function exibirPainel(array $inscricao)
    {
        $evento = (new SemanaInovacaoRepository())->buscarPorId($inscricao['evento_id']);

        // Fase 49B, achado do usuário: quem é avaliador avulso de
        // Trabalhos DESTE evento vê "Avaliar trabalhos" no lugar de
        // "Submeter trabalho"/"Meus trabalhos" - a exclusão mútua já
        // impede a mesma pessoa de ser as duas coisas no mesmo evento
        // (TrabalhoSubmissaoService/TrabalhoAvaliadorConviteService), a
        // interface agora reflete isso em vez de oferecer um botão que
        // sempre daria erro.
        $ehAvaliadorDoEvento = (new TrabalhoAvaliadorRepository())->estaAtivo($evento['id'], Auth::usuarioId());

        $this->renderizar('eventoApp/painel', [
            'evento' => $evento,
            'inscricao' => $inscricao,
            'ehAvaliadorDoEvento' => $ehAvaliadorDoEvento,
        ], $evento['nome']);
    }

    /**
     * Fase 41: dados da inscricao (documento + respostas dos campos
     * configuraveis) - desvinculado da tela publica de inscricao
     * (EventoInscricaoPublicaController::index(), que agora e' so' o
     * formulario para quem ainda nao se inscreveu, com aparencia de site
     * comum). Aqui e' dentro do app, com a mesma app-bar/menu do painel.
     */
    public function inscricao($id)
    {
        if (!Auth::autenticado()) {
            $this->redirecionar('eventoInscricao/index/' . (int) $id);
            return;
        }

        $evento = (new SemanaInovacaoRepository())->buscarPorId($id);
        $inscricao = $evento !== null
            ? (new EventoInscricaoRepository())->buscarPorEventoEUsuario($id, Auth::usuarioId())
            : null;

        if ($evento === null || $inscricao === null) {
            $this->redirecionar('eventoInscricao/index/' . (int) $id);
            return;
        }

        $campos = (new EventoCampoInscricaoRepository())->listarPorEvento($id);
        $respostas = $inscricao['respostas_json'] !== null ? json_decode($inscricao['respostas_json'], true) : [];

        $this->renderizar('eventoApp/inscricao', [
            'evento' => $evento,
            'inscricao' => $inscricao,
            'campos' => $campos,
            'respostas' => $respostas,
            'nomeParticipante' => Auth::nome(),
        ], $evento['nome']);
    }

    /**
     * Fase 45: conteudo de um aviso em massa (sub-aba "Comunicacao" do
     * Admin) - destino da notificacao do sino ('url' em NotificacaoPainelRepository::criar(),
     * gravada por EventoAdminController::comunicacaoEnviar()). Mesma
     * checagem de posse de inscricao()/cracha() (evento + inscricao do
     * PROPRIO usuario) - nao basta ter a comunicacao, precisa pertencer ao
     * evento em que a pessoa esta inscrita, senao' um id de comunicacao
     * alheio na URL vazaria o aviso de outro evento.
     */
    public function aviso($id)
    {
        if (!Auth::autenticado()) {
            $this->redirecionar('auth/loginEvento');
            return;
        }

        $comunicacao = (new EventoComunicacaoRepository())->buscarPorId($id);
        $evento = $comunicacao !== null ? (new SemanaInovacaoRepository())->buscarPorId($comunicacao['evento_id']) : null;
        $inscricao = $evento !== null
            ? (new EventoInscricaoRepository())->buscarPorEventoEUsuario($evento['id'], Auth::usuarioId())
            : null;

        if ($comunicacao === null || $evento === null || $inscricao === null) {
            $this->redirecionar('eventoApp/index');
            return;
        }

        $this->renderizar('eventoApp/aviso', [
            'evento' => $evento,
            'comunicacao' => $comunicacao,
        ], $comunicacao['assunto']);
    }

    /**
     * Fase 42: crachá de credenciamento pronto para impressão - QR +
     * código em texto (App\Services\QrCodeService). Página solta, sem
     * layout.php (mesmo espírito de politica.php/termos.php: sem app-bar,
     * sem menu), renderizada via View::renderizarString() (mesmo mecanismo
     * já usado por PdfService/relatórios em PDF desde a Fase 23 - aqui o
     * HTML vai direto pro navegador via echo, nunca pro Dompdf).
     *
     * Mesma checagem de posse de inscricao() - e MESMO redirect nos dois
     * casos de falha (evento inexistente OU inscrição de outra conta), de
     * propósito: não dar pista de enumeração de id a quem tentar outro
     * $id na URL.
     */
    public function cracha($id)
    {
        if (!Auth::autenticado()) {
            $this->redirecionar('eventoInscricao/index/' . (int) $id);
            return;
        }

        $evento = (new SemanaInovacaoRepository())->buscarPorId($id);
        $inscricao = $evento !== null
            ? (new EventoInscricaoRepository())->buscarPorEventoEUsuario($id, Auth::usuarioId())
            : null;

        if ($evento === null || $inscricao === null) {
            $this->redirecionar('eventoInscricao/index/' . (int) $id);
            return;
        }

        echo View::renderizarString('eventoApp/cracha', [
            'evento' => $evento,
            'inscricao' => $inscricao,
            'nomeParticipante' => Auth::nome(),
        ]);
    }

    /**
     * Fase 43: tela do componente de leitura de codigo - mesma checagem de
     * posse de inscricao()/cracha() (evento existe + o LEITOR tem inscricao
     * nesse evento), com o MESMO redirect neutro nos dois casos de falha.
     * $id aqui e' sempre o evento do proprio leitor, nunca do codigo lido.
     */
    public function ler($id)
    {
        if (!Auth::autenticado()) {
            $this->redirecionar('eventoInscricao/index/' . (int) $id);
            return;
        }

        $evento = (new SemanaInovacaoRepository())->buscarPorId($id);
        $inscricao = $evento !== null
            ? (new EventoInscricaoRepository())->buscarPorEventoEUsuario($id, Auth::usuarioId())
            : null;

        if ($evento === null || $inscricao === null) {
            $this->redirecionar('eventoInscricao/index/' . (int) $id);
            return;
        }

        $this->renderizar('eventoApp/ler', [
            'evento' => $evento,
        ], $evento['nome']);
    }

    /**
     * Fase 43: endpoint AJAX do componente de leitura - recebe o codigo
     * decodificado do QR (BarcodeDetector nativo) OU digitado manualmente
     * (fallback usado sempre em Safari/Firefox, que nao suportam a API), e
     * confirma se pertence a uma inscricao do MESMO evento do leitor. Nao
     * grava nada no banco alem do rate limiting abaixo - so' valida e
     * exibe (pontuacao e' Bloco E, fases futuras).
     *
     * Rate limiting: LeituraCodigoFalhaRepository, tabela dedicada (nunca a
     * mesma de login) por usuario_id do LEITOR - 10 falhas em 30 minutos
     * bloqueia com 429 generico, resetado a cada acerto.
     */
    public function validarCodigo($id)
    {
        header('Content-Type: application/json');

        if (!Auth::autenticado()) {
            http_response_code(403);
            echo json_encode(['valido' => false, 'mensagem' => 'Acesso negado.']);
            return;
        }

        $evento = (new SemanaInovacaoRepository())->buscarPorId($id);
        $inscricaoLeitor = $evento !== null
            ? (new EventoInscricaoRepository())->buscarPorEventoEUsuario($id, Auth::usuarioId())
            : null;

        if ($evento === null || $inscricaoLeitor === null) {
            http_response_code(403);
            echo json_encode(['valido' => false, 'mensagem' => 'Acesso negado.']);
            return;
        }

        $tentativas = new LeituraCodigoFalhaRepository();
        $usuarioId = Auth::usuarioId();

        if ($tentativas->contarFalhasRecentes($usuarioId, 30) >= 10) {
            http_response_code(429);
            echo json_encode(['valido' => false, 'mensagem' => 'Muitas tentativas. Aguarde alguns minutos e tente novamente.']);
            return;
        }

        $corpo = json_decode(file_get_contents('php://input'), true);
        $codigo = isset($corpo['codigo']) ? strtoupper(trim((string) $corpo['codigo'])) : '';

        $inscricaoLida = $codigo !== '' ? (new EventoInscricaoRepository())->buscarPorCodigo($id, $codigo) : null;

        if ($inscricaoLida === null) {
            $tentativas->registrarFalha($usuarioId);
            echo json_encode(['valido' => false, 'mensagem' => 'Código não encontrado.']);
            return;
        }

        $tentativas->limparFalhas($usuarioId);
        echo json_encode([
            'valido' => true,
            'mensagem' => 'Código válido: ' . $inscricaoLida['usuario_nome'] . ', inscrito(a) em ' . $evento['nome'] . '.',
        ]);
    }

    /**
     * Fase 46: agenda de atividades do evento, com o status de inscricao do
     * proprio usuario em cada uma - mesma checagem de posse de inscricao()/
     * aviso()/cracha()/ler() (evento existe + o usuario tem inscricao nesse
     * evento), mesmo redirect neutro nos dois casos de falha.
     */
    public function atividades($id)
    {
        if (!Auth::autenticado()) {
            $this->redirecionar('eventoInscricao/index/' . (int) $id);
            return;
        }

        $evento = (new SemanaInovacaoRepository())->buscarPorId($id);
        $inscricao = $evento !== null
            ? (new EventoInscricaoRepository())->buscarPorEventoEUsuario($id, Auth::usuarioId())
            : null;

        if ($evento === null || $inscricao === null) {
            $this->redirecionar('eventoInscricao/index/' . (int) $id);
            return;
        }

        $atividades = (new EventoAtividadeRepository())->listarPorEvento($id);
        $minhasInscricoes = (new EventoAtividadeInscricaoRepository())->listarPorInscricao($inscricao['id']);
        $statusPorAtividade = [];
        foreach ($minhasInscricoes as $minha) {
            $statusPorAtividade[(int) $minha['atividade_id']] = $minha['status'];
        }

        $this->renderizar('eventoApp/atividades', [
            'evento' => $evento,
            'atividades' => $atividades,
            'statusPorAtividade' => $statusPorAtividade,
        ], 'Atividades: ' . $evento['nome']);
    }

    /**
     * Fase 46: recebe so' atividade_id no POST - o evento_id e' SEMPRE
     * derivado da propria atividade no banco, nunca aceito do formulario, e
     * so' entao a posse e' conferida (evita repetir, em codigo novo, o
     * padrao de falta de comparacao de posse ja identificado - nao corrigido
     * por ser fora de escopo - em validarCodigo()).
     */
    public function inscreverAtividade()
    {
        if (!Auth::autenticado()) {
            $this->redirecionar('auth/loginEvento');
            return;
        }

        $atividadeId = (int) (isset($_POST['atividade_id']) ? $_POST['atividade_id'] : 0);
        $atividade = (new EventoAtividadeRepository())->buscarPorId($atividadeId);

        if ($atividade === null) {
            $this->redirecionar('eventoApp/index');
            return;
        }

        $eventoId = (int) $atividade['evento_id'];
        $evento = (new SemanaInovacaoRepository())->buscarPorId($eventoId);
        $inscricao = $evento !== null
            ? (new EventoInscricaoRepository())->buscarPorEventoEUsuario($eventoId, Auth::usuarioId())
            : null;

        if ($evento === null || $inscricao === null) {
            $this->redirecionar('eventoApp/index');
            return;
        }

        if (empty($atividade['exige_inscricao'])) {
            flashErro('Esta atividade não exige inscrição.');
            $this->redirecionar('eventoApp/atividades/' . $eventoId);
            return;
        }

        $resultado = (new EventoAtividadeInscricaoRepository())->inscrever($atividadeId, $inscricao['id']);

        if ($resultado === 'confirmada') {
            flashSucesso('Inscrição confirmada em "' . $atividade['nome'] . '".');
            $this->notificarInscricaoAtividade($evento, $atividade, 'confirmada');
        } elseif ($resultado === 'espera') {
            flashSucesso('Atividade lotada. Você entrou na lista de espera de "' . $atividade['nome'] . '".');
            $this->notificarInscricaoAtividade($evento, $atividade, 'espera');
            $this->notificarAdministradoresListaEspera($atividade);
        } elseif ($resultado === 'lotada') {
            flashErro('Esta atividade está lotada.');
        }

        $this->redirecionar('eventoApp/atividades/' . $eventoId);
    }

    /**
     * Fase 46: mesma checagem de posse de inscreverAtividade() - evita
     * prender alguem numa atividade por clique errado. Sem promocao
     * automatica da lista de espera (o Admin confirma manualmente, ver
     * AtividadeAdminController::confirmarEspera()).
     */
    public function cancelarInscricaoAtividade()
    {
        if (!Auth::autenticado()) {
            $this->redirecionar('auth/loginEvento');
            return;
        }

        $atividadeId = (int) (isset($_POST['atividade_id']) ? $_POST['atividade_id'] : 0);
        $atividade = (new EventoAtividadeRepository())->buscarPorId($atividadeId);

        if ($atividade === null) {
            $this->redirecionar('eventoApp/index');
            return;
        }

        $eventoId = (int) $atividade['evento_id'];
        $evento = (new SemanaInovacaoRepository())->buscarPorId($eventoId);
        $inscricao = $evento !== null
            ? (new EventoInscricaoRepository())->buscarPorEventoEUsuario($eventoId, Auth::usuarioId())
            : null;

        if ($evento === null || $inscricao === null) {
            $this->redirecionar('eventoApp/index');
            return;
        }

        (new EventoAtividadeInscricaoRepository())->cancelar($atividadeId, $inscricao['id']);
        flashSucesso('Inscrição cancelada em "' . $atividade['nome'] . '".');
        $this->redirecionar('eventoApp/atividades/' . $eventoId);
    }

    /**
     * Fase 47: tela do leitor de codigo para confirmar presenca - mesma
     * checagem de posse de ler()/validarCodigo() (evento existe + o LEITOR
     * tem inscricao nesse evento), mesmo redirect neutro nos dois casos de
     * falha. Um unico icone "Confirmar presenca" em eventoApp/atividades
     * abre esta tela (nao ha uma por atividade) - a atividade e'
     * identificada pelo proprio codigo lido, dentro do evento do leitor.
     */
    public function presenca($id)
    {
        if (!Auth::autenticado()) {
            $this->redirecionar('eventoInscricao/index/' . (int) $id);
            return;
        }

        $evento = (new SemanaInovacaoRepository())->buscarPorId($id);
        $inscricao = $evento !== null
            ? (new EventoInscricaoRepository())->buscarPorEventoEUsuario($id, Auth::usuarioId())
            : null;

        if ($evento === null || $inscricao === null) {
            $this->redirecionar('eventoInscricao/index/' . (int) $id);
            return;
        }

        $this->renderizar('eventoApp/presenca', [
            'evento' => $evento,
        ], $evento['nome']);
    }

    /**
     * Fase 48: atividades do evento em que a pessoa logada e' facilitadora,
     * com o codigo de presenca online (5 caracteres) de cada uma em
     * destaque, para comunicar verbalmente durante a atividade. Checagem de
     * posse PROPRIA (EventoAtividadeFacilitadorRepository::listarPorUsuarioNoEvento()),
     * nunca a checagem de inscricao usada pelas demais acoes deste
     * controller - um facilitador pode nunca ter se inscrito como
     * participante.
     */
    public function facilitacoes($id = null)
    {
        if (!Auth::autenticado()) {
            $this->redirecionar('eventoInscricao/index' . ($id !== null ? '/' . (int) $id : ''));
            return;
        }

        // $id ausente (achado real: acesso direto/historico do navegador
        // sem o parametro) - eventoApp/index() ja resolve pra onde mandar
        // a pessoa (facilitacoes de outro evento, inscricao existente, ou
        // formulario), mesmo criterio de robustez que index($id = null) ja
        // tem.
        if ($id === null) {
            $this->redirecionar('eventoApp/index');
            return;
        }

        $evento = (new SemanaInovacaoRepository())->buscarPorId($id);
        $facilitacoes = $evento !== null
            ? (new EventoAtividadeFacilitadorRepository())->listarPorUsuarioNoEvento(Auth::usuarioId(), $id)
            : [];

        if ($evento === null || empty($facilitacoes)) {
            $this->redirecionar('eventoApp/index');
            return;
        }

        $this->renderizar('eventoApp/facilitacoes', [
            'evento' => $evento,
            'facilitacoes' => $facilitacoes,
        ], 'Minhas facilitações: ' . $evento['nome']);
    }

    /**
     * Fase 47: endpoint AJAX do leitor de codigo - mesmo contrato JSON de
     * validarCodigo(), mas busca em evento_atividades (codigo fixo,
     * impessoal, da atividade), nunca em evento_inscricoes (codigo pessoal
     * de outro participante). A ordem foi pensada para que a chave do
     * limite de tentativas so' use atividade_id quando o codigo de fato
     * corresponde a uma atividade real (ver EventoAtividadeLeituraFalhaRepository) -
     * busca a atividade ANTES de checar o limite, para nao atribuir falhas
     * de codigo invalido a uma atividade que o leitor nunca de fato tentou.
     */
    public function validarPresenca($id)
    {
        header('Content-Type: application/json');

        if (!Auth::autenticado()) {
            http_response_code(403);
            echo json_encode(['valido' => false, 'mensagem' => 'Acesso negado.']);
            return;
        }

        $evento = (new SemanaInovacaoRepository())->buscarPorId($id);
        $inscricaoLeitor = $evento !== null
            ? (new EventoInscricaoRepository())->buscarPorEventoEUsuario($id, Auth::usuarioId())
            : null;

        if ($evento === null || $inscricaoLeitor === null) {
            http_response_code(403);
            echo json_encode(['valido' => false, 'mensagem' => 'Acesso negado.']);
            return;
        }

        $corpo = json_decode(file_get_contents('php://input'), true);
        $codigo = isset($corpo['codigo']) ? strtoupper(trim((string) $corpo['codigo'])) : '';

        // Fase 48: o tamanho do codigo decide qual coluna buscar - 6
        // caracteres e' o QR fixo (sempre 'presencial'), 5 e' o codigo de
        // presenca online (sempre 'online'). Os dois tamanhos nunca colidem
        // entre si (CodigoUnicoService::gerar() checa unicidade so' dentro
        // da propria coluna), e um comprimento fora desses dois e' sempre
        // codigo invalido, sem tentar nenhuma das duas buscas.
        $atividadesRepo = new EventoAtividadeRepository();
        $modalidadeAcesso = 'presencial';
        $atividade = null;

        if (strlen($codigo) === 6) {
            $atividade = $atividadesRepo->buscarPorCodigo($id, $codigo);
            $modalidadeAcesso = 'presencial';
        } elseif (strlen($codigo) === 5) {
            $atividade = $atividadesRepo->buscarPorCodigoPresencaOnline($id, $codigo);
            $modalidadeAcesso = 'online';
        }

        $atividadeId = $atividade !== null ? (int) $atividade['id'] : null;

        $tentativas = new EventoAtividadeLeituraFalhaRepository();
        $usuarioId = Auth::usuarioId();

        if ($tentativas->contarFalhasRecentes($usuarioId, $atividadeId, 30) >= 10) {
            http_response_code(429);
            echo json_encode(['valido' => false, 'mensagem' => 'Muitas tentativas. Aguarde alguns minutos e tente novamente.']);
            return;
        }

        if ($atividade === null) {
            $tentativas->registrarFalha($usuarioId, null);
            echo json_encode(['valido' => false, 'mensagem' => 'Código não encontrado.']);
            return;
        }

        // Fase 47 (correcao pos-teste de fumaca): achado real - sem esta
        // checagem, a leitura era aceita a qualquer momento ate' data_fim,
        // inclusive MESES antes de data_inicio. Nao conta como falha de
        // rate limit (nao e' erro de leitura/digitacao, e' uma restricao de
        // horario legitima).
        $aberturaTimestamp = strtotime($atividade['data_inicio']) - ((int) $atividade['antecedencia_abertura_presenca'] * 60);
        if (time() < $aberturaTimestamp) {
            echo json_encode([
                'valido' => false,
                'mensagem' => 'A confirmação de presença para esta atividade abre a partir de ' . formatarDataHora(date('Y-m-d H:i:s', $aberturaTimestamp)) . '.',
            ]);
            return;
        }

        if (!empty($atividade['exige_inscricao'])) {
            $inscricaoAtividade = (new EventoAtividadeInscricaoRepository())->buscarPorAtividadeEInscricao($atividadeId, $inscricaoLeitor['id']);

            if ($inscricaoAtividade === null || $inscricaoAtividade['status'] !== 'confirmada') {
                $tentativas->registrarFalha($usuarioId, $atividadeId);
                echo json_encode(['valido' => false, 'mensagem' => 'Você precisa estar inscrito e confirmado nesta atividade para confirmar presença.']);
                return;
            }
        }

        $checkins = new EventoCheckinRepository();
        $existente = $checkins->buscarPorAtividadeEInscricao($atividadeId, $inscricaoLeitor['id']);

        if ($existente !== null) {
            echo json_encode(['valido' => true, 'mensagem' => 'Presença já confirmada em "' . $atividade['nome'] . '".']);
            return;
        }

        $checkins->registrar($atividadeId, $inscricaoLeitor['id'], $modalidadeAcesso);
        $tentativas->limparFalhas($usuarioId, $atividadeId);
        echo json_encode(['valido' => true, 'mensagem' => 'Presença confirmada em "' . $atividade['nome'] . '".']);
    }

    /**
     * Fase 46: e-mail (NotificacaoService::avisoIndividualEvento(), Fase 44,
     * sem chamador real ate' agora) + painel (NotificacaoPainelRepository) -
     * mesmo par de canais usado em EventoAdminController::comunicacaoEnviar().
     */
    private function notificarInscricaoAtividade(array $evento, array $atividade, $status)
    {
        $usuario = (new \App\Repositories\UsuarioRepository())->buscarPorId(Auth::usuarioId());
        $assunto = $status === 'confirmada' ? 'Inscrição confirmada: ' . $atividade['nome'] : 'Lista de espera: ' . $atividade['nome'];
        $corpo = $status === 'confirmada'
            ? '<p>Sua inscrição em "' . htmlspecialchars($atividade['nome'], ENT_QUOTES, 'UTF-8') . '" foi confirmada.</p>'
            : '<p>A atividade "' . htmlspecialchars($atividade['nome'], ENT_QUOTES, 'UTF-8') . '" está lotada. Você entrou na lista de espera.</p>';

        (new NotificacaoService())->avisoIndividualEvento($usuario['email'], $evento, $assunto, $corpo);
        (new NotificacaoPainelRepository())->criar(
            Auth::usuarioId(),
            $status === 'confirmada' ? 'atividade_inscricao_confirmada' : 'atividade_lista_espera',
            $status === 'confirmada' ? 'Inscrição confirmada' : 'Lista de espera',
            $status === 'confirmada'
                ? 'Sua inscrição em "' . $atividade['nome'] . '" foi confirmada.'
                : 'Você entrou na lista de espera de "' . $atividade['nome'] . '".',
            ['url' => url('eventoApp/atividades/' . (int) $evento['id'])]
        );
    }

    /**
     * Fase 46: mesmo padrao de DuvidaController::notificarAdministradoresNovaDuvida()
     * - PerfilRepository::listarUsuariosPorPerfilConcurso('administrador', null)
     * ja' filtra so' administradores GLOBAIS (o caso do Evento). So' painel,
     * sem e-mail, mesmo criterio usado para "nova duvida".
     */
    private function notificarAdministradoresListaEspera(array $atividade)
    {
        $notificacoes = new NotificacaoPainelRepository();
        foreach ((new PerfilRepository())->listarUsuariosPorPerfilConcurso('administrador', null) as $admin) {
            $notificacoes->criar(
                (int) $admin['id'],
                'atividade_lista_espera_admin',
                'Lista de espera em atividade',
                'A atividade "' . $atividade['nome'] . '" recebeu uma inscrição na lista de espera.',
                ['url' => url('atividades/inscritos/' . (int) $atividade['id'])]
            );
        }
    }

    /**
     * Manifesto do aplicativo web instalavel - nome/icone vem de
     * Configuracoes Gerais (editaveis pelo Administrador, nunca fixos no
     * codigo). Sem cache do service worker (ver service-worker.js): e'
     * conteudo dinamico, sempre buscado da rede.
     */
    public function manifesto()
    {
        $configuracao = (new ConfiguracaoSistemaRepository())->buscar();
        // Fase 48B: cor deixou de vir da configuracao unica e passa a vir do
        // tema ativo (do usuario autenticado, se houver, senao o padrao do
        // sistema), mesma logica de layout.php.
        $temaAtivo = (new TemaVisualRepository())->resolverAtivo(Auth::autenticado() ? Auth::usuarioId() : null);

        $nome = $configuracao !== false && !empty($configuracao['nome_app'])
            ? $configuracao['nome_app']
            : 'Eventos ' . nomeInstituicao() . ' ' . nomeUnidadeResponsavel();
        $nomeCurto = $configuracao !== false && !empty($configuracao['nome_app_curto'])
            ? $configuracao['nome_app_curto']
            : 'Eventos ' . nomeInstituicao();
        $corPrimaria = $temaAtivo['cor_primaria_inicio'];
        $corTerciaria = $temaAtivo['cor_terciaria'];

        $manifesto = [
            'name' => $nome,
            'short_name' => $nomeCurto,
            // Fase 41 (correcao pos-teste de fumaca): '&modo=app' marca toda
            // navegacao que comeca pelo icone instalado - ehContextoApp()
            // (app/helpers.php) grava isso na sessao, para reconhecer o
            // contexto de app mesmo em telas sem esse parametro (ex.: login,
            // apos a sessao expirar) e mesmo em quem instalou pelo
            // computador (User-Agent de desktop comum). '&', nunca '?' -
            // url() ja' devolve 'index.php?r=...' (com '?'); um segundo '?'
            // vira parte LITERAL do valor de 'r' em vez de novo parametro,
            // quebrando o parsing da rota inteira (mesmo erro ja documentado
            // na Fase 40, repetido aqui - ver feedback na memoria do projeto).
            'start_url' => url('eventoApp/index') . '&modo=app',
            'scope' => config('base_path') . '/',
            'display' => 'standalone',
            'background_color' => $corTerciaria,
            'theme_color' => $corPrimaria,
            'icons' => [
                ['src' => iconeAppUrl('icon-192.png'), 'sizes' => '192x192', 'type' => 'image/png', 'purpose' => 'any'],
                ['src' => iconeAppUrl('icon-512.png'), 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'any'],
                ['src' => iconeAppUrl('icon-512-maskable.png'), 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'maskable'],
            ],
        ];

        header('Content-Type: application/manifest+json');
        echo json_encode($manifesto, JSON_UNESCAPED_SLASHES);
    }
}
