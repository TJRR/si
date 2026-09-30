<?php

namespace App\Controllers;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Core\Auth;
use App\Core\Controller;
use App\Core\View;
use App\Repositories\BonusConfigRepository;
use App\Repositories\ConexaoConfigRepository;
use App\Repositories\ConexaoRepository;
use App\Repositories\ConfiguracaoSistemaRepository;
use App\Repositories\DivulgacaoComprovacaoRepository;
use App\Repositories\DivulgacaoConfigRepository;
use App\Repositories\EventoAnaisRepository;
use App\Repositories\EventoAtividadeFacilitadorRepository;
use App\Repositories\EventoAtividadeInscricaoRepository;
use App\Repositories\EventoAtividadeLeituraFalhaRepository;
use App\Repositories\EventoAtividadeRepository;
use App\Repositories\EventoCampoInscricaoRepository;
use App\Repositories\EventoCheckinRepository;
use App\Repositories\EventoComunicacaoRepository;
use App\Repositories\EventoInscricaoRepository;
use App\Repositories\EstandeLeituraFalhaRepository;
use App\Repositories\EstandeRepository;
use App\Repositories\EstandeRepresentanteRepository;
use App\Repositories\EstandeVisitaRepository;
use App\Repositories\LeituraCodigoFalhaRepository;
use App\Repositories\NotificacaoPainelRepository;
use App\Repositories\PerfilRepository;
use App\Repositories\PesquisaConfigRepository;
use App\Repositories\PesquisaPerguntaRepository;
use App\Repositories\PesquisaRespondenteRepository;
use App\Repositories\SemanaInovacaoRepository;
use App\Repositories\TemaVisualRepository;
use App\Repositories\TrabalhoAutorRepository;
use App\Repositories\TrabalhoAvaliadorRepository;
use App\Repositories\UsuarioPerfilRepository;
use App\Services\ArquivoPrivadoService;
use App\Services\BonusApuracaoService;
use App\Services\ConexaoService;
use App\Services\DivulgacaoService;
use App\Services\ImagemComprovacaoService;
use App\Services\NotificacaoService;
use App\Services\PerfilVisibilidadeService;
use App\Services\PesquisaService;

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

        // Fase 57: com o modulo desligado nao ha' o que consultar, entao o
        // painel nem paga o custo das consultas de progresso.
        $bonusAtivo = (new BonusConfigRepository())->estaAtivo($evento['id']);
        $pesquisaAberta = $this->pesquisaAberta($evento);

        $this->renderizar('eventoApp/painel', [
            'evento' => $evento,
            'inscricao' => $inscricao,
            'ehAvaliadorDoEvento' => $ehAvaliadorDoEvento,
            // Fase 53: botao "Anais" (so' quando ha versao publicada).
            'anais' => (new EventoAnaisRepository())->buscarPublicadoParaParticipante($evento['id']),
            // Fase 54: botao "Estandes" (so' com estande ativo) e o resumo
            // de pontos; as duas consultas ja se protegem de falha de banco.
            'temEstandes' => (new EstandeRepository())->existeAtivoNoEvento($evento['id']),
            'resumoEstandes' => (new EstandeVisitaRepository())->resumoParticipante($inscricao['id']),
            // Fase 55: "Conectar com participante" (a antiga "Ler codigo")
            // so' aparece com o modulo Conexoes ligado no evento; o resumo
            // vem junto. As duas consultas tambem se protegem de falha de
            // banco, entao uma tabela ainda nao criada nunca derruba o
            // painel de quem esta inscrito.
            'conexoesAtivas' => (new ConexaoConfigRepository())->estaAtivo($evento['id']),
            'resumoConexoes' => (new ConexaoRepository())->resumoParticipante($inscricao['id']),
            // Fase 56: botao "Divulgacao" so' com o modulo ligado no evento,
            // com o resumo de pontos abaixo. Mesma protecao das duas fases
            // anteriores: as consultas capturam falha de banco dentro do
            // repositorio, entao tabela ainda nao criada faz o botao sumir
            // em vez de derrubar o painel de todo inscrito.
            'divulgacaoAtiva' => (new DivulgacaoConfigRepository())->estaAtivo($evento['id']),
            'resumoDivulgacao' => (new DivulgacaoComprovacaoRepository())->resumoParticipante($inscricao['id']),
            // Fase 57: o bloco de bonus percorre os bonus ATIVOS do evento,
            // que sao cadastro e nao codigo, entao bonus novo aparece aqui
            // sem alteracao nenhuma nesta view. progressoDe() resolve tudo
            // em uma consulta (duas quando ha bonus por tipo de atividade),
            // e o estado da pesquisa sai de mais duas leituras. Mesma
            // protecao das tres fases anteriores: os repositorios capturam
            // falha de banco e devolvem vazio, entao tabela ainda nao criada
            // faz o bloco sumir em vez de derrubar o painel.
            'bonusAtivo' => $bonusAtivo,
            'progressoBonus' => $bonusAtivo ? (new BonusApuracaoService())->progressoDe($evento, $inscricao['id']) : [],
            'pesquisaAberta' => $pesquisaAberta,
            'pesquisaRespondida' => $pesquisaAberta ? (new PesquisaRespondenteRepository())->jaRespondeu($evento['id'], Auth::usuarioId()) : false,
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
     *
     * Fase 55: a tela deixou de ser so' conferencia e virou "Conectar com
     * participante" - a leitura grava a conexao e credita pontos aos dois
     * lados (decisao do dono). Com o modulo desligado no evento, o leitor
     * da' lugar a um aviso, para ninguem ler um cracha achando que vai
     * pontuar.
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

        $config = (new ConexaoConfigRepository())->vigente($id);

        $this->renderizar('eventoApp/ler', [
            'evento' => $evento,
            'config' => $config,
            'resumo' => (new ConexaoRepository())->resumoParticipante($inscricao['id']),
            'dentroDaJanela' => (new ConexaoService())->dentroDaJanela($evento),
        ], 'Conectar com participante: ' . $evento['nome']);
    }

    /**
     * Fase 55: lista das conexoes da pessoa, no molde de estandes() -
     * mesma conferencia de posse e mesmo redirect neutro. O que aparece de
     * cada pessoa conectada e' decidido por PerfilVisibilidadeService, a
     * partir do que ela mesma liberou em "Meu Perfil", lido agora e nunca
     * congelado na conexao.
     */
    public function conexoes($id)
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

        $visibilidade = new PerfilVisibilidadeService();
        $conexoes = [];

        foreach ((new ConexaoRepository())->listarDaInscricao($inscricao['id']) as $linha) {
            $conexoes[] = [
                'conectado_em' => $linha['conectado_em'],
                'pontos_creditados' => (int) $linha['pontos_creditados'],
                'pessoa' => $visibilidade->paraExibicao($linha),
            ];
        }

        $this->renderizar('eventoApp/conexoes', [
            'evento' => $evento,
            'config' => (new ConexaoConfigRepository())->vigente($id),
            'resumo' => (new ConexaoRepository())->resumoParticipante($inscricao['id']),
            'conexoes' => $conexoes,
        ], 'Minhas conexões: ' . $evento['nome']);
    }

    /**
     * Fase 56: tela "Divulgacao" - onde a pessoa envia a comprovacao de que
     * publicou sobre o evento numa rede social, ou de que passou a
     * acompanhar os canais do Tribunal, e acompanha a situacao de cada uma.
     *
     * Mesma conferencia de posse e mesmo redirect neutro das telas de
     * Estandes e Conexoes.
     */
    public function divulgacao($id)
    {
        $contexto = $this->contextoDivulgacaoOuVolta($id);

        if ($contexto === null) {
            return;
        }

        $this->renderizarDivulgacao($contexto['evento'], $contexto['inscricao']);
    }

    /**
     * Fase 56: envio da comprovacao, com credito automatico dos pontos.
     *
     * A ordem das recusas segue o molde de validarEstande()/validarCodigo():
     * primeiro o que nao depende do que foi enviado (modulo ligado, janela,
     * rede ativa), depois o que depende (rede cadastrada em "Meu Perfil",
     * prova compativel, endereco do dominio certo, imagem legivel) e, por
     * ultimo, a gravacao com os tetos recontados dentro da transacao.
     *
     * O arquivo so' vai para a area privada DEPOIS da conferencia de
     * duplicidade, e qualquer falha posterior o remove: gravacao em disco
     * nao participa da transacao do banco.
     */
    public function divulgacaoEnviar($id)
    {
        $contexto = $this->contextoDivulgacaoOuVolta($id);

        if ($contexto === null) {
            return;
        }

        $evento = $contexto['evento'];
        $inscricao = $contexto['inscricao'];

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirecionar('eventoApp/divulgacao/' . (int) $evento['id']);
            return;
        }

        $valores = [
            'rede' => isset($_POST['rede']) ? (string) $_POST['rede'] : '',
            'tipo_acao' => isset($_POST['tipo_acao']) ? (string) $_POST['tipo_acao'] : '',
            'endereco' => isset($_POST['endereco']) ? trim((string) $_POST['endereco']) : '',
        ];

        $configRepo = new DivulgacaoConfigRepository();
        $config = $configRepo->vigente($evento['id']);
        $servico = new DivulgacaoService();

        if ($config['ativo'] !== 1) {
            $this->renderizarDivulgacao($evento, $inscricao, 'A divulgação não está ativada neste evento.', $valores);
            return;
        }

        if (!$servico->dentroDaJanela($evento, $config)) {
            $janela = $servico->janelaTexto($evento, $config);
            $this->renderizarDivulgacao(
                $evento,
                $inscricao,
                'As comprovações de divulgação valem apenas de ' . $janela . '.',
                $valores
            );
            return;
        }

        if (!in_array($valores['tipo_acao'], DivulgacaoService::TIPOS_ACAO, true)) {
            $this->renderizarDivulgacao($evento, $inscricao, 'Escolha o que você quer comprovar.', $valores);
            return;
        }

        $configRede = $configRepo->rede($evento['id'], $valores['rede']);
        $ativaNoTipo = $configRede !== null
            && (($valores['tipo_acao'] === 'publicacao' && $configRede['publicacao_ativa'] === 1)
                || ($valores['tipo_acao'] === 'acompanhar' && $configRede['acompanhar_ativa'] === 1));

        if (!$ativaNoTipo) {
            $this->renderizarDivulgacao($evento, $inscricao, 'Essa rede social não vale para essa comprovação neste evento.', $valores);
            return;
        }

        $rotulo = $configRede['rotulo'];

        // A rede precisa estar cadastrada em "Meu Perfil": o documento da
        // dinamica de pontos fala em "mesmo Instagram informado na
        // plataforma", e e' o nome de usuario cadastrado que permite
        // comparar a prova com a conta da pessoa.
        $perfilRepo = new UsuarioPerfilRepository();
        $redesDaPessoa = $perfilRepo->redesSociais($perfilRepo->buscarPorUsuarioId(Auth::usuarioId()));

        if (!isset($redesDaPessoa[$valores['rede']])) {
            $this->renderizarDivulgacao(
                $evento,
                $inscricao,
                'Informe primeiro o seu perfil no ' . $rotulo . ' em "Meu Perfil". A comprovação é conferida contra a conta que você cadastrou.',
                $valores
            );
            return;
        }

        $prova = $valores['tipo_acao'] === 'publicacao' ? $configRede['publicacao_prova'] : $configRede['acompanhar_prova'];
        $aceitaEndereco = $prova === 'endereco' || $prova === 'ambos';
        $aceitaImagem = $prova === 'imagem' || $prova === 'ambos';
        $enviouEndereco = $valores['endereco'] !== '';
        $enviouImagem = isset($_FILES['imagem']) && isset($_FILES['imagem']['error']) && $_FILES['imagem']['error'] !== UPLOAD_ERR_NO_FILE;

        if (!$enviouEndereco && !$enviouImagem) {
            $this->renderizarDivulgacao($evento, $inscricao, $this->mensagemProvaEsperada($prova, $rotulo), $valores);
            return;
        }

        if ($enviouEndereco && !$aceitaEndereco) {
            $this->renderizarDivulgacao($evento, $inscricao, 'No ' . $rotulo . ', a comprovação é a imagem da tela.', $valores);
            return;
        }

        if ($enviouImagem && !$aceitaImagem) {
            $this->renderizarDivulgacao($evento, $inscricao, 'No ' . $rotulo . ', a comprovação é o endereço da publicação.', $valores);
            return;
        }

        $dados = ['rede' => $valores['rede'], 'tipo_acao' => $valores['tipo_acao']];

        if ($enviouEndereco) {
            $endereco = $servico->normalizarEndereco($valores['rede'], $valores['endereco']);

            if ($endereco === null) {
                $this->renderizarDivulgacao(
                    $evento,
                    $inscricao,
                    'Esse endereço não parece ser uma publicação do ' . $rotulo . '. Copie o endereço da publicação e cole aqui.',
                    $valores
                );
                return;
            }

            $dados['endereco'] = $endereco;
            $dados['endereco_hash'] = $servico->resumoDoEndereco($endereco);
        }

        $imagem = new ImagemComprovacaoService();
        $preparado = null;

        if ($enviouImagem) {
            try {
                $preparado = $imagem->prepararEnvio($_FILES['imagem']);
            } catch (\RuntimeException $e) {
                $this->renderizarDivulgacao($evento, $inscricao, $e->getMessage(), $valores);
                return;
            }

            $dados['arquivo_sha256'] = $preparado['sha256'];
            $dados['arquivo_nome'] = $preparado['nome_original'];
            $dados['arquivo_bytes'] = $preparado['bytes'];
        }

        $comprovacoes = new DivulgacaoComprovacaoRepository();

        // Conferencia de duplicidade ANTES de o arquivo ir para a area
        // privada: o caso mais comum de recusa nem chega a criar arquivo em
        // disco. A chave unica do banco continua sendo a garantia final.
        $repetida = $comprovacoes->provaJaUsada(
            (int) $evento['id'],
            (int) $inscricao['id'],
            isset($dados['endereco_hash']) ? $dados['endereco_hash'] : null,
            isset($dados['arquivo_sha256']) ? $dados['arquivo_sha256'] : null
        );

        if ($repetida !== null) {
            if ($preparado !== null) {
                $imagem->descartar($preparado);
            }

            // Duas situacoes diferentes para quem esta na tela: o endereco e'
            // unico no evento (pode ter sido registrado por outra pessoa), e a
            // imagem e' unica por pessoa.
            $mensagem = $repetida === 'endereco'
                ? 'Esta publicação já foi registrada neste evento.'
                : 'Você já enviou esta imagem como comprovação.';
            $this->renderizarDivulgacao($evento, $inscricao, $mensagem, $valores);
            return;
        }

        if ($preparado !== null) {
            try {
                $dados['arquivo_path'] = $imagem->gravar($preparado, $evento['id']);
            } catch (\RuntimeException $e) {
                $imagem->descartar($preparado);
                $this->renderizarDivulgacao($evento, $inscricao, $e->getMessage(), $valores);
                return;
            }
        }

        try {
            $resultado = $servico->registrar($evento, $inscricao, $configRede, $dados);
        } catch (\Throwable $e) {
            // Arquivo gravado e linha nao gravada: remove o arquivo, para a
            // area privada nao acumular resto (mesma compensacao de
            // EventoAnaisPdfFinalService::enviarPeloAutor()).
            if (isset($dados['arquivo_path'])) {
                ArquivoPrivadoService::remover($dados['arquivo_path']);
            }

            error_log('[Divulgacao] Falha ao gravar comprovacao do evento ' . (int) $evento['id'] . ': ' . $e->getMessage());
            $this->renderizarDivulgacao($evento, $inscricao, 'Não foi possível registrar a comprovação agora. Tente de novo em alguns instantes.', $valores);
            return;
        }

        if ($resultado['id'] === null) {
            if (isset($dados['arquivo_path'])) {
                ArquivoPrivadoService::remover($dados['arquivo_path']);
            }

            $mensagem = $resultado['motivo_sem_pontos'] === 'acompanhar_repetido'
                ? 'Você já registrou que acompanha o ' . $rotulo . '.'
                : 'Esta comprovação já foi registrada neste evento.';
            $this->renderizarDivulgacao($evento, $inscricao, $mensagem, $valores);
            return;
        }

        if ($resultado['pontos'] > 0) {
            flashSucesso('Comprovação registrada. Você recebeu ' . $resultado['pontos'] . ' ponto(s).');
        } elseif ($resultado['motivo_sem_pontos'] === 'teto_dia') {
            flashAlerta('Comprovação registrada, sem pontos: você já atingiu o limite diário de publicações que pontuam no ' . $rotulo . '.');
        } elseif ($resultado['motivo_sem_pontos'] === 'teto_evento') {
            flashAlerta('Comprovação registrada, sem pontos: você já atingiu o limite de publicações que pontuam no ' . $rotulo . ' neste evento.');
        } else {
            flashAlerta('Comprovação registrada. Esta ação não credita pontos neste evento.');
        }

        $this->redirecionar('eventoApp/divulgacao/' . (int) $evento['id']);
    }

    /**
     * Fase 56: entrega a imagem de comprovacao para a PROPRIA pessoa. A
     * imagem fica na area privada (storage/uploads), fora do alcance do
     * servidor web, porque uma captura de tela traz nome, foto e publicacao
     * de terceiros - so' este ponto e a tela do Administrador a servem.
     */
    public function divulgacaoImagem($id, $comprovacaoId = null)
    {
        $contexto = $this->contextoDivulgacaoOuVolta($id);

        if ($contexto === null) {
            return;
        }

        $comprovacao = (new DivulgacaoComprovacaoRepository())->buscarPorId($comprovacaoId);

        if ($comprovacao === null
            || (int) $comprovacao['evento_inscricao_id'] !== (int) $contexto['inscricao']['id']) {
            http_response_code(404);
            exit('Comprovação não encontrada.');
        }

        if (empty($comprovacao['arquivo_path'])) {
            http_response_code(404);
            exit('Esta imagem foi apagada conforme a política de retenção de dados do evento.');
        }

        ArquivoPrivadoService::servirImagem($comprovacao['arquivo_path'], $comprovacao['arquivo_nome']);
    }

    /**
     * Posse do aplicativo, repetida nas tres acoes de Divulgacao como nas
     * demais telas: evento existe e quem pede tem inscricao nele. Devolve
     * null depois de redirecionar, e quem chama so' precisa voltar.
     */
    private function contextoDivulgacaoOuVolta($id)
    {
        if (!Auth::autenticado()) {
            $this->redirecionar('eventoInscricao/index/' . (int) $id);
            return null;
        }

        $evento = (new SemanaInovacaoRepository())->buscarPorId($id);
        $inscricao = $evento !== null
            ? (new EventoInscricaoRepository())->buscarPorEventoEUsuario($id, Auth::usuarioId())
            : null;

        if ($evento === null || $inscricao === null) {
            $this->redirecionar('eventoInscricao/index/' . (int) $id);
            return null;
        }

        return ['evento' => $evento, 'inscricao' => $inscricao];
    }

    private function mensagemProvaEsperada($prova, $rotulo)
    {
        if ($prova === 'endereco') {
            return 'Informe o endereço da sua publicação no ' . $rotulo . '.';
        }

        if ($prova === 'imagem') {
            return 'Envie a imagem da tela mostrando a sua publicação no ' . $rotulo . '.';
        }

        return 'Informe o endereço da publicação ou envie a imagem da tela do ' . $rotulo . '.';
    }

    private function renderizarDivulgacao(array $evento, array $inscricao, $erro = null, array $valores = [])
    {
        if ($erro !== null) {
            flashErro($erro);
        }

        $configRepo = new DivulgacaoConfigRepository();
        $config = $configRepo->vigente($evento['id']);
        $servico = new DivulgacaoService();
        $perfilRepo = new UsuarioPerfilRepository();

        $this->renderizar('eventoApp/divulgacao', [
            'evento' => $evento,
            'inscricao' => $inscricao,
            'config' => $config,
            'redes' => $configRepo->redesAtivas($evento['id']),
            'redesDaPessoa' => $perfilRepo->redesSociais($perfilRepo->buscarPorUsuarioId(Auth::usuarioId())),
            'dentroDaJanela' => $servico->dentroDaJanela($evento, $config),
            'janelaTexto' => $servico->janelaTexto($evento, $config),
            'resumo' => (new DivulgacaoComprovacaoRepository())->resumoParticipante($inscricao['id']),
            'comprovacoes' => (new DivulgacaoComprovacaoRepository())->listarDaInscricao($inscricao['id']),
            'valores' => $valores,
        ], 'Divulgação: ' . $evento['nome']);
    }

    /**
     * Fase 57: pesquisa de satisfacao do evento, dentro do aplicativo.
     *
     * A pesquisa e' ANONIMA: o sistema guarda, em tabelas separadas e sem
     * elo nenhum, que a pessoa respondeu (nominal, e' o que habilita o
     * credito e o que impede responder duas vezes) e o que foi respondido.
     *
     * Conferencia de posse PROPRIA, e nao a de inscricao usada pelas demais
     * acoes deste controller (bloco E, pendencia 34): facilitador e
     * avaliador avulso tambem respondem, e nenhum dos dois tem inscricao.
     * Nenhuma das outras conferencias do controller e' tocada.
     */
    public function pesquisa($id)
    {
        $contexto = $this->contextoPesquisaOuVolta($id);

        if ($contexto === null) {
            return;
        }

        $this->renderizarPesquisa($contexto['evento'], $contexto['inscricao']);
    }

    /**
     * Posse da pesquisa: inscrito no evento, facilitador ativo de alguma
     * atividade dele, ou avaliador ativo de Trabalhos dele. Devolve a
     * inscricao quando existe (e' ela que habilita o credito de pontos) e
     * null quando a pessoa tem vinculo sem ser inscrita.
     */
    private function contextoPesquisaOuVolta($id)
    {
        if (!Auth::autenticado()) {
            $this->redirecionar('eventoInscricao/index/' . (int) $id);
            return null;
        }

        $evento = (new SemanaInovacaoRepository())->buscarPorId($id);

        if ($evento === null) {
            $this->redirecionar('eventoInscricao/index/' . (int) $id);
            return null;
        }

        $usuarioId = Auth::usuarioId();
        $inscricao = (new EventoInscricaoRepository())->buscarPorEventoEUsuario($id, $usuarioId);

        if ($inscricao === null) {
            $ehFacilitador = (new EventoAtividadeFacilitadorRepository())->listarPorUsuarioNoEvento($usuarioId, $id) !== [];
            $ehAvaliador = (new TrabalhoAvaliadorRepository())->estaAtivo($id, $usuarioId);

            if (!$ehFacilitador && !$ehAvaliador) {
                $this->redirecionar('eventoInscricao/index/' . (int) $id);
                return null;
            }
        }

        return ['evento' => $evento, 'inscricao' => $inscricao, 'usuarioId' => $usuarioId];
    }

    /**
     * Envio das respostas. A ordem das recusas segue o molde das telas
     * anteriores: primeiro o que nao depende do que foi enviado (modulo
     * ligado, pergunta cadastrada, janela, ja' respondeu), depois a
     * validacao das respostas e, por ultimo, a gravacao.
     */
    public function pesquisaEnviar($id)
    {
        $contexto = $this->contextoPesquisaOuVolta($id);

        if ($contexto === null) {
            return;
        }

        $evento = $contexto['evento'];
        $inscricao = $contexto['inscricao'];
        $usuarioId = $contexto['usuarioId'];

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirecionar('eventoApp/pesquisa/' . (int) $evento['id']);
            return;
        }

        $configRepo = new PesquisaConfigRepository();
        $config = $configRepo->vigente($evento['id']);
        $servico = new PesquisaService();
        $perguntas = (new PesquisaPerguntaRepository())->listarAtivas($evento['id']);

        if ((int) $config['ativo'] !== 1 || $perguntas === []) {
            $this->renderizarPesquisa($evento, $inscricao, 'A pesquisa não está disponível neste evento.');
            return;
        }

        if (!$servico->dentroDaJanela($evento, $config)) {
            $janela = $servico->janelaTexto($evento, $config);
            $this->renderizarPesquisa($evento, $inscricao, 'A pesquisa fica aberta' . ($janela !== '' ? ' de ' . $janela : '') . '.');
            return;
        }

        if ((new PesquisaRespondenteRepository())->jaRespondeu($evento['id'], $usuarioId)) {
            $this->renderizarPesquisa($evento, $inscricao, 'Você já respondeu a esta pesquisa.');
            return;
        }

        $enviado = isset($_POST['resposta']) && is_array($_POST['resposta']) ? $_POST['resposta'] : [];
        $conferencia = $servico->validarRespostas($perguntas, $enviado);

        if ($conferencia['erros'] !== []) {
            $this->renderizarPesquisa($evento, $inscricao, 'Confira as perguntas marcadas abaixo.', $conferencia['erros'], $enviado);
            return;
        }

        if (!$servico->registrar($evento, $usuarioId, $conferencia['linhas'])) {
            $this->renderizarPesquisa($evento, $inscricao, 'Você já respondeu a esta pesquisa.');
            return;
        }

        // Quem responde sem ser inscrito (facilitador, avaliador avulso)
        // responde e nao pontua: o credito exige inscricao no evento.
        $creditados = $inscricao !== null
            ? (new BonusApuracaoService())->creditarPorPesquisa($evento, $inscricao['id'])
            : [];
        $mensagem = 'Obrigado por responder.' . $this->mensagemDeBonus($creditados);

        flashSucesso($mensagem);
        $this->redirecionar('eventoApp/pesquisa/' . (int) $evento['id']);
    }

    /**
     * A pesquisa so' aparece no painel quando esta' ligada, tem pergunta
     * ativa e esta' dentro da janela. Usada pelo painel e pela propria tela.
     */
    private function pesquisaAberta(array $evento)
    {
        $config = (new PesquisaConfigRepository())->vigente($evento['id']);

        if ((int) $config['ativo'] !== 1) {
            return false;
        }

        if ((new PesquisaPerguntaRepository())->listarAtivas($evento['id']) === []) {
            return false;
        }

        return (new PesquisaService())->dentroDaJanela($evento, $config);
    }

    private function renderizarPesquisa(array $evento, $inscricao, $erro = null, array $erros = [], array $valores = [])
    {
        if ($erro !== null) {
            flashErro($erro);
        }

        $configRepo = new PesquisaConfigRepository();
        $config = $configRepo->vigente($evento['id']);
        $servico = new PesquisaService();
        $respondente = (new PesquisaRespondenteRepository())->buscarPorUsuario($evento['id'], Auth::usuarioId());

        $this->renderizar('eventoApp/pesquisa', [
            'evento' => $evento,
            'inscricao' => $inscricao,
            // Sem inscricao no evento (facilitador, avaliador avulso), a
            // pessoa responde e nao pontua, e a tela precisa dizer isso
            // antes, nunca depois do envio.
            'pontua' => $inscricao !== null,
            'config' => $config,
            'titulo' => $configRepo->tituloDe($config),
            'perguntas' => (new PesquisaPerguntaRepository())->listarAtivas($evento['id']),
            'dentroDaJanela' => $servico->dentroDaJanela($evento, $config),
            'janelaTexto' => $servico->janelaTexto($evento, $config),
            'respondente' => $respondente,
            'servico' => $servico,
            'erros' => $erros,
            'valores' => $valores,
            'escalaMinima' => PesquisaService::ESCALA_MINIMA,
            'escalaMaxima' => PesquisaService::ESCALA_MAXIMA,
        ], $configRepo->tituloDe($config) . ': ' . $evento['nome']);
    }

    /**
     * Fase 43: endpoint AJAX do componente de leitura - recebe o codigo
     * decodificado do QR (BarcodeDetector nativo) OU digitado manualmente
     * (fallback usado sempre em Safari/Firefox, que nao suportam a API), e
     * confirma se pertence a uma inscricao do MESMO evento do leitor.
     *
     * Fase 55: a leitura passou a GRAVAR a conexao entre as duas pessoas e a
     * creditar pontos aos dois lados, numa transacao so' (ConexaoService).
     * A ordem das conferencias segue validarEstande() (Fase 54), e a mesma
     * distincao vale aqui: so' codigo inexistente conta no limite de
     * tentativas. Modulo desligado, fora da janela do evento, autoleitura,
     * inscricao ainda nao homologada e dupla ja conectada sao restricoes
     * legitimas, nao engano de quem esta lendo.
     *
     * A autoleitura fecha o achado registrado na Fase 43 (Implantar.md,
     * entrada da Fase 44): ate aqui, ler o proprio codigo era aceito.
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

        $config = (new ConexaoConfigRepository())->vigente($id);
        $servico = new ConexaoService();

        // Modulo desligado e fora da janela vem antes de olhar o codigo:
        // nao dependem dele, e a resposta seria a mesma de qualquer jeito.
        if ($config['ativo'] !== 1) {
            echo json_encode(['valido' => false, 'mensagem' => 'As conexões não estão ativadas neste evento.']);
            return;
        }

        if (!$servico->dentroDaJanela($evento)) {
            echo json_encode([
                'valido' => false,
                'mensagem' => 'As conexões só valem durante o evento, de ' . formatarData($evento['data_inicio'])
                    . ' a ' . formatarData($evento['data_fim']) . '.',
            ]);
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

        if ((int) $inscricaoLida['id'] === (int) $inscricaoLeitor['id']) {
            echo json_encode([
                'valido' => false,
                'mensagem' => 'Este é o seu próprio código. Peça o código de outra pessoa para se conectar.',
            ]);
            return;
        }

        // Em evento com credenciamento automatico toda inscricao ja nasce
        // homologada, entao nao ha nada a conferir aqui.
        if ($evento['modo_credenciamento'] === 'assistido') {
            if (empty($inscricaoLeitor['homologado_em'])) {
                echo json_encode([
                    'valido' => false,
                    'mensagem' => 'Sua inscrição ainda não foi homologada. Procure o credenciamento do evento antes de se conectar.',
                ]);
                return;
            }

            if (empty($inscricaoLida['homologado_em'])) {
                echo json_encode([
                    'valido' => false,
                    'mensagem' => 'A inscrição de ' . $inscricaoLida['usuario_nome'] . ' ainda não foi homologada. '
                        . 'Peça a ela para passar no credenciamento do evento.',
                ]);
                return;
            }
        }

        $resultado = $servico->conectar($evento, $config, $inscricaoLeitor, $inscricaoLida);
        $tentativas->limparFalhas($usuarioId);

        if ($resultado['ja_existia']) {
            echo json_encode([
                'valido' => true,
                'mensagem' => 'Você e ' . $inscricaoLida['usuario_nome'] . ' já estavam conectados.',
            ]);
            return;
        }

        $pontos = (int) $resultado['pontos_leitor'];
        $mensagem = 'Conexão com ' . $inscricaoLida['usuario_nome'] . ' registrada';

        if ($pontos > 0) {
            $mensagem .= ': ' . $pontos . ($pontos === 1 ? ' ponto.' : ' pontos.');
        } elseif ((int) $config['pontos_por_conexao'] > 0) {
            $mensagem .= '. Você já atingiu o limite de conexões que pontuam neste evento.';
        } else {
            $mensagem .= '.';
        }

        echo json_encode(['valido' => true, 'mensagem' => $mensagem]);
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

        // Fase 57 (bloco E, pendencia 34): quem conduziu uma atividade
        // tambem responde a pesquisa de satisfacao, e ate' aqui nao tinha
        // por onde. Nao pontua, porque nao tem inscricao.
        $this->renderizar('eventoApp/facilitacoes', [
            'evento' => $evento,
            'facilitacoes' => $facilitacoes,
            'pesquisaAberta' => $this->pesquisaAberta($evento),
            'pesquisaRespondida' => (new PesquisaRespondenteRepository())->jaRespondeu($evento['id'], Auth::usuarioId()),
            'temInscricao' => (new EventoInscricaoRepository())->buscarPorEventoEUsuario($id, Auth::usuarioId()) !== null,
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

        // Fase 57: a presenca nova pode fechar um bonus. A apuracao roda
        // DEPOIS da gravacao da presenca e dentro de um bloco de protecao
        // proprio: a presenca e' o fato principal, o cabecalho da resposta
        // ja foi escrito, e falha na apuracao nunca pode transformar uma
        // confirmacao bem sucedida em erro na tela de quem esta na porta da
        // sala. Nao ha' chamada dentro de EventoCheckinRepository de
        // proposito: repositorio nao depende de servico neste projeto, e
        // aqui o metodo so' chega quando a presenca e' nova de verdade (a
        // repetida ja' retornou acima).
        $mensagem = 'Presença confirmada em "' . $atividade['nome'] . '".';

        try {
            $creditados = (new BonusApuracaoService())->apurarInscricao($evento, $inscricaoLeitor['id'], Auth::usuarioId());
            $mensagem .= $this->mensagemDeBonus($creditados);
        } catch (\Throwable $e) {
            error_log('[Bonus] Falha ao apurar apos a presenca da inscricao ' . (int) $inscricaoLeitor['id'] . ': ' . $e->getMessage());
        }

        echo json_encode(['valido' => true, 'mensagem' => $mensagem]);
    }

    /**
     * Fase 57: complemento da mensagem da leitura quando a presenca fecha um
     * ou mais bonus. Sai com o nome cadastrado pelo Administrador, nunca com
     * um nome fixo em codigo.
     */
    private function mensagemDeBonus(array $creditados)
    {
        if ($creditados === []) {
            return '';
        }

        $nomes = [];
        $pontos = 0;

        foreach ($creditados as $credito) {
            $nomes[] = $credito['nome'];
            $pontos += (int) $credito['pontos'];
        }

        $lista = count($nomes) === 1
            ? $nomes[0]
            : implode(', ', array_slice($nomes, 0, -1)) . ' e ' . $nomes[count($nomes) - 1];

        if ($pontos === 0) {
            return ' Você fechou ' . (count($nomes) === 1 ? 'o bônus' : 'os bônus') . ' ' . $lista . '.';
        }

        return ' Você fechou ' . (count($nomes) === 1 ? 'o bônus' : 'os bônus') . ' ' . $lista
            . ': ' . $pontos . ($pontos === 1 ? ' ponto.' : ' pontos.');
    }

    /**
     * Fase 54: estandes ativos do evento e o progresso do participante
     * (estandes ja visitados e total de pontos). Mesma conferencia de posse
     * de atividades(). O codigo do estande nunca sai nesta tela.
     */
    public function estandes($id)
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

        $resumo = (new EstandeVisitaRepository())->resumoParticipante($inscricao['id']);
        $visitados = [];

        foreach ($resumo['visitas'] as $visita) {
            $visitados[(int) $visita['estande_id']] = $visita;
        }

        $this->renderizar('eventoApp/estandes', [
            'evento' => $evento,
            'estandes' => (new EstandeRepository())->listarAtivosPublico($id),
            'resumo' => $resumo,
            'visitados' => $visitados,
        ], 'Estandes: ' . $evento['nome']);
    }

    /**
     * Fase 54: tela do leitor do codigo do estande - mesma conferencia de
     * posse e mesmo componente de leitura de presenca().
     */
    public function lerEstande($id)
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

        $this->renderizar('eventoApp/ler_estande', [
            'evento' => $evento,
        ], $evento['nome']);
    }

    /**
     * Fase 54: resposta JSON da leitura do codigo do estande, no contrato de
     * validarPresenca(). Ordem: posse, limite de tentativas, codigo de 6
     * caracteres do evento de quem le, estande ativo, autovisita (quem
     * representa o estande nao pontua nele), visita ja registrada, gravacao
     * com os pontos vigentes. Estande inativo e autovisita sao restricoes
     * legitimas, nao erro de leitura: nao contam no limite de tentativas.
     * Sem janela de horario (decisao do dono na Fase 54).
     */
    public function validarEstande($id)
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

        $tentativas = new EstandeLeituraFalhaRepository();
        $usuarioId = Auth::usuarioId();

        if ($tentativas->contarFalhasRecentes($usuarioId, 30) >= 10) {
            http_response_code(429);
            echo json_encode(['valido' => false, 'mensagem' => 'Muitas tentativas. Aguarde alguns minutos e tente novamente.']);
            return;
        }

        $corpo = json_decode(file_get_contents('php://input'), true);
        $codigo = isset($corpo['codigo']) ? strtoupper(trim((string) $corpo['codigo'])) : '';
        $estande = strlen($codigo) === 6 ? (new EstandeRepository())->buscarPorCodigo($id, $codigo) : null;

        if ($estande === null) {
            $tentativas->registrarFalha($usuarioId);
            echo json_encode(['valido' => false, 'mensagem' => 'Código não encontrado.']);
            return;
        }

        if (empty($estande['ativo'])) {
            echo json_encode(['valido' => false, 'mensagem' => 'Este estande não está recebendo visitas no momento.']);
            return;
        }

        if ((new EstandeRepresentanteRepository())->buscarVinculo((int) $estande['id'], $usuarioId) !== null) {
            echo json_encode(['valido' => false, 'mensagem' => 'Você representa este estande, então a visita a ele não conta pontos para você.']);
            return;
        }

        $visitas = new EstandeVisitaRepository();

        if ($visitas->buscarPorEstandeEInscricao((int) $estande['id'], $inscricaoLeitor['id']) !== null) {
            echo json_encode(['valido' => true, 'mensagem' => 'Sua visita ao estande "' . $estande['nome'] . '" já estava registrada.']);
            return;
        }

        $registro = $visitas->registrar((int) $estande['id'], $inscricaoLeitor['id'], (int) $estande['pontos_visita']);
        $tentativas->limparFalhas($usuarioId);
        $pontos = (int) $registro['pontos_creditados'];

        echo json_encode([
            'valido' => true,
            'mensagem' => 'Visita ao estande "' . $estande['nome'] . '" registrada'
                . ($pontos > 0 ? ': ' . $pontos . ($pontos === 1 ? ' ponto.' : ' pontos.') : '.'),
        ]);
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
