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
use App\Repositories\BonusRepository;
use App\Repositories\CertificadoConfigRepository;
use App\Repositories\CertificadoRepository;
use App\Repositories\CompeticaoParticipacaoRepository;
use App\Repositories\CompeticaoRepository;
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
use App\Repositories\EventoAtividadeTipoRepository;
use App\Repositories\EventoCampoInscricaoRepository;
use App\Repositories\EventoCheckinRepository;
use App\Repositories\EventoComunicacaoRepository;
use App\Repositories\EventoInscricaoRepository;
use App\Repositories\EstandeLeituraFalhaRepository;
use App\Repositories\EstandeRepository;
use App\Repositories\EstandeRepresentanteRepository;
use App\Repositories\EstandeVisitaRepository;
use App\Repositories\CredenciamentoLocalRepository;
use App\Repositories\GamificacaoConfigRepository;
use App\Repositories\GamificacaoDesempateRepository;
use App\Repositories\LeituraCodigoFalhaRepository;
use App\Repositories\NotificacaoPainelRepository;
use App\Repositories\PerfilRepository;
use App\Repositories\PesquisaConfigRepository;
use App\Repositories\PesquisaPerguntaRepository;
use App\Repositories\PesquisaRespondenteRepository;
use App\Repositories\PresencaCreditoRepository;
use App\Repositories\SemanaInovacaoRepository;
use App\Repositories\TemaVisualRepository;
use App\Repositories\TrabalhoApresentacaoRepository;
use App\Repositories\TrabalhoAutorRepository;
use App\Repositories\TrabalhoAvaliadorRepository;
use App\Repositories\UsuarioPerfilRepository;
use App\Services\ArquivoPrivadoService;
use App\Services\BonusApuracaoService;
use App\Services\CertificadoElegibilidadeService;
use App\Services\CertificadoEmissaoService;
use App\Services\ConexaoService;
use App\Services\DivulgacaoService;
use App\Services\GamificacaoService;
use App\Services\ImagemComprovacaoService;
use App\Services\NotificacaoService;
use App\Services\PerfilVisibilidadeService;
use App\Services\PesquisaService;
use App\Services\PresencaPontuacaoService;

/**
 * Aplicativo do Evento, instalavel no celular: decide entre mandar a pessoa
 * se inscrever ou mostrar o painel de quem ja esta inscrita.
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

            // Quem tem o perfil de inscrito so' por ser autor de trabalho vai para
            // Meus trabalhos.
            if ((new TrabalhoAutorRepository())->possuiTrabalhoEmQualquerEvento(Auth::usuarioId())) {
                $this->redirecionar('trabalho/meusTrabalhos');
                return;
            }

            // Quem tem o perfil de inscrito so' por avaliar trabalhos vai para a
            // avaliacao, que fica fora do aplicativo.
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

        // Avaliador de Trabalhos deste evento ve "Avaliar trabalhos" no lugar de
        // submeter: a exclusao mutua impede a mesma pessoa de ser as duas coisas.
        $ehAvaliadorDoEvento = (new TrabalhoAvaliadorRepository())->estaAtivo($evento['id'], Auth::usuarioId());

        // Fase 57: com o modulo desligado nao ha' o que consultar, entao o
        // painel nem paga o custo das consultas de progresso.
        $bonusAtivo = (new BonusConfigRepository())->estaAtivo($evento['id']);
        $pesquisaAberta = $this->pesquisaAberta($evento);
        $progressoBonus = $bonusAtivo ? (new BonusApuracaoService())->progressoDe($evento, $inscricao['id'], Auth::usuarioId()) : [];

        // Fase 58: a abertura do painel e' o que alcanca a acao feita fora do
        // aplicativo (perfil completado pelo "Meu Perfil" do Concurso, que
        // esta fase nao toca). So' grava quando ha' bonus ativo de acao do
        // participante que a pessoa ainda nao tem, nunca em "visualizar como"
        // (que e' so' leitura) e nunca depois do encerramento. Bloco de
        // protecao proprio: o painel abre mesmo que a apuracao falhe.
        if ($bonusAtivo && !Auth::estaVisualizandoComoOutro()) {
            try {
                $creditadosAgora = (new BonusApuracaoService())->apurarAcoesPendentes($evento, $inscricao['id'], Auth::usuarioId(), $progressoBonus);

                if ($creditadosAgora !== []) {
                    $progressoBonus = (new BonusApuracaoService())->progressoDe($evento, $inscricao['id'], Auth::usuarioId());
                }
            } catch (\Throwable $e) {
                error_log('[Bonus] Falha ao apurar na abertura do painel da inscricao ' . (int) $inscricao['id'] . ': ' . $e->getMessage());
            }
        }

        // Fase 58: cartao de pontos. So' o total da propria pessoa, numa
        // consulta por inscricao: a classificacao do evento inteiro fica em
        // "Minha pontuacao", fora da tela mais aberta do aplicativo.
        $gamificacao = (new GamificacaoConfigRepository())->vigente($evento['id']);
        $totalPontos = null;

        if ($gamificacao['ativo'] === 1) {
            try {
                $totalPontos = (new GamificacaoService())->somaDaInscricao($evento['id'], $inscricao['id'])['total'];
            } catch (\Throwable $e) {
                error_log('[Gamificacao] Falha ao somar os pontos da inscricao ' . (int) $inscricao['id'] . ': ' . $e->getMessage());
            }
        }

        $this->renderizar('eventoApp/painel', [
            'evento' => $evento,
            'inscricao' => $inscricao,
            'ehAvaliadorDoEvento' => $ehAvaliadorDoEvento,
            // Fase 53: botao "Anais" (so' quando ha versao publicada).
            'anais' => (new EventoAnaisRepository())->buscarPublicadoParaParticipante($evento['id']),
            // As consultas dos modulos opcionais abaixo capturam falha de banco:
            // tabela ainda nao criada esconde o botao em vez de derrubar o painel.
            'temEstandes' => (new EstandeRepository())->existeAtivoNoEvento($evento['id']),
            'resumoEstandes' => (new EstandeVisitaRepository())->resumoParticipante($inscricao['id']),
            'conexoesAtivas' => (new ConexaoConfigRepository())->estaAtivo($evento['id']),
            'resumoConexoes' => (new ConexaoRepository())->resumoParticipante($inscricao['id']),
            'divulgacaoAtiva' => (new DivulgacaoConfigRepository())->estaAtivo($evento['id']),
            'resumoDivulgacao' => (new DivulgacaoComprovacaoRepository())->resumoParticipante($inscricao['id']),
            'bonusAtivo' => $bonusAtivo,
            'progressoBonus' => $progressoBonus,
            'gamificacaoAtiva' => $gamificacao['ativo'] === 1,
            'totalPontos' => $totalPontos,
            'gincanaEncerrada' => GamificacaoService::encerrada($evento['id']),
            'pesquisaAberta' => $pesquisaAberta,
            'pesquisaRespondida' => $pesquisaAberta ? (new PesquisaRespondenteRepository())->jaRespondeu($evento['id'], Auth::usuarioId()) : false,
            // So' a conferencia das duas travas da emissao; a apuracao do dossie fica
            // na tela de certificados, porque custa varias consultas.
            'certificadosAbertos' => (new CertificadoElegibilidadeService())->emissaoAbertaAoParticipante(
                $evento,
                (new CertificadoConfigRepository())->vigente($evento['id'])
            ),
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
     * Conteudo de um aviso em massa da Comunicacao, destino do sino.
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
     * Cracha de credenciamento pronto para impressao, em pagina solta, sem o
     * layout do sistema.
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

        // Fase 58: evento sem cracha impresso (Dados Gerais) nao oferece a
        // impressao; o codigo continua na tela "Minha inscricao".
        if (isset($evento['oferece_cracha']) && (int) $evento['oferece_cracha'] === 0) {
            $this->redirecionar('eventoApp/inscricao/' . (int) $id);
            return;
        }

        echo View::renderizarString('eventoApp/cracha', [
            'evento' => $evento,
            'inscricao' => $inscricao,
            'nomeParticipante' => Auth::nome(),
        ]);
    }

    /**
     * "Conectar com participante": a leitura grava a conexao e credita os
     * dois lados. Com o modulo desligado no evento, a tela mostra um aviso no
     * lugar do leitor.
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

        // Fase 58: a tela passa a mostrar tambem o codigo da propria pessoa,
        // para quem vai ser lida nao precisar sair dela (dinamica de pontos
        // v2: "QR fornecido diretamente na tela do aplicativo").
        $this->renderizar('eventoApp/ler', [
            'evento' => $evento,
            'inscricao' => $inscricao,
            'gincanaEncerrada' => GamificacaoService::encerrada($evento['id']),
            'config' => $config,
            'resumo' => (new ConexaoRepository())->resumoParticipante($inscricao['id']),
            'dentroDaJanela' => (new ConexaoService())->dentroDaJanela($evento),
        ], 'Conectar com participante: ' . $evento['nome']);
    }

    /**
     * Conexoes da pessoa. O que aparece de cada pessoa conectada vem de
     * PerfilVisibilidadeService, lido agora e nunca congelado na conexao.
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
     * Divulgacao: envio das comprovacoes e acompanhamento de cada uma.
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
            'rede_informada' => isset($_POST['rede_informada']) ? trim((string) $_POST['rede_informada']) : '',
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
                'As comprovações de divulgação valem apenas ' . $janela . '.',
                $valores
            );
            return;
        }

        // Fase 58: depois do encerramento da gincana, comprovacao de
        // divulgacao nao tem mais para que existir (ela so' serve para
        // pontuar), entao e' recusada antes de qualquer arquivo ir ao disco.
        if (GamificacaoService::encerrada($evento['id'])) {
            $this->renderizarDivulgacao($evento, $inscricao, 'A gincana deste evento foi encerrada: comprovações de divulgação não pontuam mais.', $valores);
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

        $rotuloNo = $configRede['rotulo_no'];

        // A publicacao precisa ser da conta cadastrada em "Meu Perfil" (a
        // dinamica de pontos fala em "conta ja' cadastrada"). Fase 58: o que
        // conta como conta depende da rede (DivulgacaoConfigRepository::
        // REDES): a rede do perfil, o telefone marcado como WhatsApp, ou
        // nada, no TikTok e em "qualquer rede". Seguir um canal nunca exige
        // conta. Nao
        // ha' conferencia previa da prova: a organizacao audita depois.
        if ($valores['tipo_acao'] === 'publicacao' && $configRede['conta'] !== null) {
            $perfilRepo = new UsuarioPerfilRepository();
            $perfilPessoa = $perfilRepo->buscarPorUsuarioId(Auth::usuarioId());

            if ($configRede['conta'] === 'whatsapp') {
                if ($perfilPessoa === null || empty($perfilPessoa['telefone']) || empty($perfilPessoa['telefone_whatsapp'])) {
                    $this->renderizarDivulgacao(
                        $evento,
                        $inscricao,
                        'Informe primeiro, em "Meu Perfil", o telefone que você usa no WhatsApp e marque que ele recebe mensagens no WhatsApp: a publicação precisa ser dessa conta.',
                        $valores
                    );
                    return;
                }
            } elseif (!isset($perfilRepo->redesSociais($perfilPessoa)[$valores['rede']])) {
                $this->renderizarDivulgacao(
                    $evento,
                    $inscricao,
                    'Informe primeiro a sua conta ' . $rotuloNo . ' em "Meu Perfil": a publicação precisa ser dessa conta.',
                    $valores
                );
                return;
            }
        }

        // Fase 58: nome da rede digitado em "qualquer rede", opcional, ate' o
        // tamanho da coluna. Nas demais redes o campo e' ignorado.
        $redeInformada = null;

        if ($valores['rede'] === 'qualquer' && $valores['rede_informada'] !== '') {
            if (mb_strlen($valores['rede_informada']) > 60) {
                $this->renderizarDivulgacao($evento, $inscricao, 'O nome da rede pode ter até 60 caracteres.', $valores);
                return;
            }

            $redeInformada = $valores['rede_informada'];
        }

        $prova = $valores['tipo_acao'] === 'publicacao' ? $configRede['publicacao_prova'] : $configRede['acompanhar_prova'];
        $aceitaEndereco = $prova === 'endereco' || $prova === 'ambos';
        $aceitaImagem = $prova === 'imagem' || $prova === 'ambos';
        $enviouEndereco = $valores['endereco'] !== '';
        $enviouImagem = isset($_FILES['imagem']) && isset($_FILES['imagem']['error']) && $_FILES['imagem']['error'] !== UPLOAD_ERR_NO_FILE;

        if (!$enviouEndereco && !$enviouImagem) {
            $this->renderizarDivulgacao($evento, $inscricao, $this->mensagemProvaEsperada($prova, $rotuloNo), $valores);
            return;
        }

        if ($enviouEndereco && !$aceitaEndereco) {
            $this->renderizarDivulgacao($evento, $inscricao, 'Para comprovar ' . $rotuloNo . ', envie a imagem da tela.', $valores);
            return;
        }

        if ($enviouImagem && !$aceitaImagem) {
            $this->renderizarDivulgacao($evento, $inscricao, 'Para comprovar ' . $rotuloNo . ', informe o endereço da publicação.', $valores);
            return;
        }

        $dados = ['rede' => $valores['rede'], 'tipo_acao' => $valores['tipo_acao'], 'rede_informada' => $redeInformada];

        if ($enviouEndereco) {
            $endereco = $servico->normalizarEndereco($valores['rede'], $valores['endereco']);

            if ($endereco === null) {
                $this->renderizarDivulgacao(
                    $evento,
                    $inscricao,
                    'Esse endereço não parece ser de uma publicação ' . $rotuloNo . '. Copie o endereço da publicação e cole aqui.',
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

            if ($resultado['motivo_sem_pontos'] === 'acompanhar_repetido') {
                $mensagem = 'Você já registrou que segue o canal ' . $rotuloNo . '.';
            } elseif ($resultado['motivo_sem_pontos'] === 'gincana_encerrada') {
                $mensagem = 'A gincana deste evento foi encerrada: comprovações de divulgação não pontuam mais.';
            } else {
                $mensagem = 'Esta comprovação já foi registrada neste evento.';
            }
            $this->renderizarDivulgacao($evento, $inscricao, $mensagem, $valores);
            return;
        }

        if ($resultado['pontos'] > 0) {
            flashSucesso('Comprovação registrada. Você recebeu ' . $resultado['pontos'] . ' ponto(s).');
        } elseif ($resultado['motivo_sem_pontos'] === 'teto_dia') {
            flashAlerta('Comprovação registrada, sem pontos: você já atingiu o limite diário de publicações que pontuam ' . $rotuloNo . '.');
        } elseif ($resultado['motivo_sem_pontos'] === 'teto_evento') {
            flashAlerta('Comprovação registrada, sem pontos: você já atingiu o limite de publicações que pontuam ' . $rotuloNo . ' neste evento.');
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
     * Evento e inscricao de quem pede, para as acoes de Divulgacao. Devolve
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

    /**
     * Fase 58: recebe a frase com a preposicao ("no Instagram", "na rede em
     * que voce publicou"), da lista de redes da Divulgacao.
     */
    private function mensagemProvaEsperada($prova, $rotuloNo)
    {
        if ($prova === 'endereco') {
            return 'Informe o endereço da sua publicação ' . $rotuloNo . '.';
        }

        if ($prova === 'imagem') {
            return 'Envie a imagem da tela mostrando a sua publicação ' . $rotuloNo . '.';
        }

        return 'Informe o endereço da publicação ou envie a imagem da tela ' . $rotuloNo . '.';
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
        $perfilPessoa = $perfilRepo->buscarPorUsuarioId(Auth::usuarioId());

        $this->renderizar('eventoApp/divulgacao', [
            'evento' => $evento,
            'inscricao' => $inscricao,
            'config' => $config,
            'redes' => $configRepo->redesAtivas($evento['id']),
            'redesDaPessoa' => $perfilRepo->redesSociais($perfilPessoa),
            // Fase 58: a conta do WhatsApp e' o telefone marcado no perfil.
            'temWhatsapp' => $perfilPessoa !== null && !empty($perfilPessoa['telefone']) && !empty($perfilPessoa['telefone_whatsapp']),
            'gincanaEncerrada' => GamificacaoService::encerrada($evento['id']),
            'dentroDaJanela' => $servico->dentroDaJanela($evento, $config),
            'janelaTexto' => $servico->janelaTexto($evento, $config),
            'resumo' => (new DivulgacaoComprovacaoRepository())->resumoParticipante($inscricao['id']),
            'comprovacoes' => (new DivulgacaoComprovacaoRepository())->listarDaInscricao($inscricao['id']),
            'valores' => $valores,
        ], 'Divulgação: ' . $evento['nome']);
    }

    /**
     * Pesquisa de satisfacao do evento, dentro do aplicativo. Facilitador,
     * avaliador e representante de estande tambem respondem, sem pontuar.
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
     * Inscrito no evento, facilitador de alguma atividade dele, avaliador de
     * Trabalhos dele ou representante de estande dele. Devolve a inscricao
     * quando existe (e' ela que habilita o credito de pontos) e null quando a
     * pessoa tem vinculo sem ser inscrita.
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
            $ehRepresentante = (new EstandeRepresentanteRepository())->buscarPorEventoEUsuario($id, $usuarioId) !== null;

            if (!$ehFacilitador && !$ehAvaliador && !$ehRepresentante) {
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
            $this->renderizarPesquisa($evento, $inscricao, 'A pesquisa fica aberta' . ($janela !== '' ? ' ' . $janela : '') . '.');
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
            'gincanaEncerrada' => GamificacaoService::encerrada($evento['id']),
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
     * Leitura do codigo de outra pessoa em "Conectar com participante":
     * grava a conexao e credita os dois lados numa transacao so'
     * (ConexaoService). Resposta em JSON para o componente de leitura.
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

        if (GamificacaoService::encerrada($evento['id'])) {
            echo json_encode(['valido' => true, 'mensagem' => $mensagem . '. A gincana foi encerrada, então a conexão não pontua mais.']);
            return;
        }

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
     * Agenda de atividades do evento, com a situacao de inscricao da propria
     * pessoa em cada uma.
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
     * Sem promocao automatica da lista de espera: a organizacao confirma em
     * AtividadeAdminController::confirmarEspera().
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
     * Leitor para confirmar presenca. A atividade e' identificada pelo
     * proprio codigo lido, dentro do evento de quem le.
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
     * Atividades em que a pessoa e' facilitadora, com o codigo de presenca
     * online de cada uma em destaque. O acesso vem da facilitacao, e nao da
     * inscricao: um facilitador pode nunca ter se inscrito como participante.
     */
    public function facilitacoes($id = null)
    {
        if (!Auth::autenticado()) {
            $this->redirecionar('eventoInscricao/index' . ($id !== null ? '/' . (int) $id : ''));
            return;
        }

        // Sem o evento no endereco, index() decide para onde mandar a pessoa.
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
        // Fase 58: competicoes ligadas as atividades que a pessoa facilita,
        // com o codigo de participacao para mostrar em tela cheia a quem
        // canta ou compete ("QR na mao do responsavel").
        $this->renderizar('eventoApp/facilitacoes', [
            'evento' => $evento,
            'facilitacoes' => $facilitacoes,
            'competicoes' => (new CompeticaoRepository())->listarDoFacilitadorNoEvento(Auth::usuarioId(), $evento['id']),
            'gincanaEncerrada' => GamificacaoService::encerrada($evento['id']),
            'pesquisaAberta' => $this->pesquisaAberta($evento),
            'pesquisaRespondida' => (new PesquisaRespondenteRepository())->jaRespondeu($evento['id'], Auth::usuarioId()),
            'temInscricao' => (new EventoInscricaoRepository())->buscarPorEventoEUsuario($id, Auth::usuarioId()) !== null,
            // Fase 59: quem conduziu atividade tem direito ao certificado do
            // evento e ao de cada atividade conduzida, e sem inscricao nao
            // passa pelo painel - este e' o caminho dele.
            'certificadosAbertos' => (new CertificadoElegibilidadeService())->emissaoAbertaAoParticipante(
                $evento,
                (new CertificadoConfigRepository())->vigente($evento['id'])
            ),
        ], 'Minhas facilitações: ' . $evento['nome']);
    }

    /**
     * Leitura dos codigos fixos do evento (atividade, competicao e
     * credenciamento no local), em JSON, no contrato de validarCodigo().
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

        // O tamanho do codigo decide a busca: 6 caracteres e' codigo fixo
        // (atividade, competicao ou credenciamento, nesta ordem), 5 e' o codigo de
        // presenca online. Os codigos fixos sao unicos entre si
        // (CodigoUnicoService::gerarCodigoFixoDoEvento()), entao a ordem nunca
        // decide entre dois donos do mesmo codigo.
        $atividadesRepo = new EventoAtividadeRepository();
        $modalidadeAcesso = 'presencial';
        $atividade = null;
        $competicao = null;
        $credenciamento = null;

        if (strlen($codigo) === 6) {
            $atividade = $atividadesRepo->buscarPorCodigo($id, $codigo);
            $modalidadeAcesso = 'presencial';

            if ($atividade === null) {
                $competicao = (new CompeticaoRepository())->buscarPorCodigo($id, $codigo);
            }

            if ($atividade === null && $competicao === null) {
                $credenciamento = (new CredenciamentoLocalRepository())->buscarConfigPorCodigo($id, $codigo);
            }
        } elseif (strlen($codigo) === 5) {
            $atividade = $atividadesRepo->buscarPorCodigoPresencaOnline($id, $codigo);
            $modalidadeAcesso = 'online';
        }

        // Contagem de falhas nos codigos fixos: ver Implantar.md, secao 13.17.
        if ($competicao !== null) {
            echo json_encode($this->participarDaCompeticao($evento, $inscricaoLeitor, $competicao));
            return;
        }

        if ($credenciamento !== null) {
            echo json_encode($this->credenciarNoLocal($evento, $inscricaoLeitor, $credenciamento));
            return;
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

        // Leitura aceita so' a partir da antecedencia configurada antes do inicio.
        $aberturaTimestamp = strtotime($atividade['data_inicio']) - ((int) $atividade['antecedencia_abertura_presenca'] * 60);
        if (time() < $aberturaTimestamp) {
            echo json_encode([
                'valido' => false,
                'mensagem' => 'A confirmação de presença para esta atividade abre a partir de ' . formatarDataHora(date('Y-m-d H:i:s', $aberturaTimestamp)) . '.',
            ]);
            return;
        }

        // A leitura fecha no fim da atividade.
        if (time() > strtotime($atividade['data_fim'])) {
            echo json_encode([
                'valido' => false,
                'mensagem' => 'A confirmação de presença desta atividade encerrou em ' . formatarDataHora($atividade['data_fim']) . '.',
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

        // A apuracao dos bonus roda depois da gravacao e em bloco proprio: falha
        // nela nunca transforma uma presenca confirmada em erro.
        $mensagem = 'Presença confirmada em "' . $atividade['nome'] . '".';

        // Pontos da propria presenca, no mesmo tipo de bloco: falha no credito
        // nunca vira erro na tela de quem esta' na porta da sala.
        try {
            $checkinGravado = $checkins->buscarPorAtividadeEInscricao($atividadeId, $inscricaoLeitor['id']);

            if ($checkinGravado !== null) {
                $pontuacao = (new PresencaPontuacaoService())->creditar($evento, $atividade, $checkinGravado, Auth::usuarioId());
                $mensagem .= $this->mensagemDePontosDePresenca($pontuacao);
            }
        } catch (\Throwable $e) {
            error_log('[Presenca] Falha ao creditar a presenca da inscricao ' . (int) $inscricaoLeitor['id'] . ': ' . $e->getMessage());
        }

        try {
            $creditados = (new BonusApuracaoService())->apurarInscricao($evento, $inscricaoLeitor['id'], Auth::usuarioId());
            $mensagem .= $this->mensagemDeBonus($creditados);
        } catch (\Throwable $e) {
            error_log('[Bonus] Falha ao apurar apos a presenca da inscricao ' . (int) $inscricaoLeitor['id'] . ': ' . $e->getMessage());
        }

        echo json_encode(['valido' => true, 'mensagem' => $mensagem]);
    }

    /**
     * Fase 58: complemento da mensagem da leitura com os pontos da propria
     * presenca (PresencaPontuacaoService::creditar()).
     */
    private function mensagemDePontosDePresenca($pontuacao)
    {
        if ($pontuacao === null) {
            return '';
        }

        if (!empty($pontuacao['encerrada'])) {
            return ' A gincana foi encerrada, então esta presença não pontua mais.';
        }

        if (!empty($pontuacao['facilitador'])) {
            return ' Você facilita esta atividade, então a presença fica registrada sem pontos.';
        }

        $presenca = (int) $pontuacao['pontos_presenca'];
        $pontualidade = (int) $pontuacao['pontos_pontualidade'];
        $texto = ' Você recebeu ' . $presenca . ($presenca === 1 ? ' ponto' : ' pontos');

        if ($pontualidade > 0) {
            $texto .= ', mais ' . $pontualidade . ' de pontualidade';
        }

        return $texto . '.';
    }

    /**
     * Fase 58: leitura do codigo de uma competicao (Karaoke, Batalha de
     * Prompts). A dinamica de pontos v2 pontua a participacao, lida num
     * codigo "na mao do responsavel", sem inscricao previa; uma
     * participacao pontuada por pessoa por competicao.
     *
     * Janela: a da atividade ligada (abertura da leitura ate' o fim); sem
     * atividade ligada, os dias do evento. Devolve a resposta JSON pronta.
     */
    private function participarDaCompeticao(array $evento, array $inscricao, array $competicao)
    {
        if (empty($competicao['ativo'])) {
            return ['valido' => false, 'mensagem' => 'Esta competição não está recebendo participações no momento.'];
        }

        if (!empty($competicao['atividade_id']) && $competicao['atividade_inicio'] !== null) {
            $abertura = strtotime($competicao['atividade_inicio']) - ((int) $competicao['atividade_antecedencia'] * 60);
            $fim = strtotime($competicao['atividade_fim']);

            if (time() < $abertura || time() > $fim) {
                return [
                    'valido' => false,
                    'mensagem' => 'A participação em "' . $competicao['nome'] . '" vale de '
                        . formatarDataHora(date('Y-m-d H:i:s', $abertura)) . ' a ' . formatarDataHora($competicao['atividade_fim']) . '.',
                ];
            }
        } else {
            $hoje = date('Y-m-d');

            if ($hoje < substr((string) $evento['data_inicio'], 0, 10) || $hoje > substr((string) $evento['data_fim'], 0, 10)) {
                return [
                    'valido' => false,
                    'mensagem' => 'A participação em "' . $competicao['nome'] . '" vale só durante o evento, de '
                        . formatarData($evento['data_inicio']) . ' a ' . formatarData($evento['data_fim']) . '.',
                ];
            }
        }

        if (!empty($competicao['atividade_id'])) {
            $facilitacao = (new EventoAtividadeFacilitadorRepository())->buscarPorAtividadeEUsuario((int) $competicao['atividade_id'], Auth::usuarioId());

            if ($facilitacao !== null && $facilitacao['removido_em'] === null) {
                return ['valido' => false, 'mensagem' => 'Você conduz esta competição, então a participação nela não pontua para você.'];
            }
        }

        if (GamificacaoService::encerrada($evento['id'])) {
            return ['valido' => false, 'mensagem' => 'A gincana deste evento foi encerrada: a participação em competições não pontua mais.'];
        }

        $pontos = (int) $competicao['pontos_participacao'];
        $gravou = (new CompeticaoParticipacaoRepository())->registrar((int) $evento['id'], (int) $competicao['id'], (int) $inscricao['id'], $pontos);

        if (!$gravou) {
            return ['valido' => true, 'mensagem' => 'Sua participação em "' . $competicao['nome'] . '" já estava registrada.'];
        }

        return [
            'valido' => true,
            'mensagem' => 'Participação em "' . $competicao['nome'] . '" registrada'
                . ($pontos > 0 ? ': ' . $pontos . ($pontos === 1 ? ' ponto.' : ' pontos.') : '.'),
        ];
    }

    /**
     * Fase 58: leitura do codigo do credenciamento no local. Grava o fato e
     * apura os bonus: quem credita e' um bonus do tipo credenciamento_local
     * do catalogo, e nao este metodo. Depois do encerramento, o fato
     * continua sendo gravado, sem pontos. Devolve a resposta JSON pronta.
     */
    private function credenciarNoLocal(array $evento, array $inscricao, array $config)
    {
        if ((int) $config['ativo'] !== 1) {
            return ['valido' => false, 'mensagem' => 'O credenciamento no local não está aberto neste evento.'];
        }

        $inicio = $config['leitura_inicio'] !== null ? strtotime($config['leitura_inicio']) : strtotime(substr((string) $evento['data_inicio'], 0, 10) . ' 00:00:00');
        $fim = $config['leitura_fim'] !== null ? strtotime($config['leitura_fim']) : strtotime(substr((string) $evento['data_fim'], 0, 10) . ' 23:59:59');

        if (time() < $inicio || time() > $fim) {
            return [
                'valido' => false,
                'mensagem' => 'O credenciamento no local vale de ' . formatarDataHora(date('Y-m-d H:i:s', $inicio))
                    . ' a ' . formatarDataHora(date('Y-m-d H:i:s', $fim)) . '.',
            ];
        }

        if (!(new CredenciamentoLocalRepository())->registrar((int) $evento['id'], (int) $inscricao['id'])) {
            return ['valido' => true, 'mensagem' => 'Credenciamento já confirmado.'];
        }

        $mensagem = 'Credenciamento confirmado. Boas-vindas ao evento!';

        if (GamificacaoService::encerrada($evento['id'])) {
            return ['valido' => true, 'mensagem' => $mensagem . ' A gincana foi encerrada, então o credenciamento não pontua mais.'];
        }

        try {
            $creditados = (new BonusApuracaoService())->apurarInscricao($evento, (int) $inscricao['id'], Auth::usuarioId());
            $mensagem .= $this->mensagemDeBonus($creditados);
        } catch (\Throwable $e) {
            error_log('[Bonus] Falha ao apurar apos o credenciamento da inscricao ' . (int) $inscricao['id'] . ': ' . $e->getMessage());
        }

        return ['valido' => true, 'mensagem' => $mensagem];
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
     * Fase 58: "Minha pontuacao" - total, posicao e extrato da pessoa, e os
     * N primeiros da classificacao geral (com ou sem nomes, conforme a
     * configuracao). A classificacao e' calculada na hora, numa consulta so'
     * (GamificacaoService::classificacao()); falha de banco mostra
     * "classificacao indisponivel" em vez de numeros errados.
     */
    public function pontuacao($id)
    {
        $contexto = $this->contextoGamificacaoOuVolta($id);

        if ($contexto === null) {
            return;
        }

        $evento = $contexto['evento'];
        $inscricao = $contexto['inscricao'];
        $config = $contexto['config'];
        $classificacao = [];
        $indisponivel = false;

        try {
            $classificacao = (new GamificacaoService())->classificacao($evento['id']);
        } catch (\Throwable $e) {
            error_log('[Gamificacao] Falha ao montar a classificacao do evento ' . (int) $evento['id'] . ': ' . $e->getMessage());
            $indisponivel = true;
        }

        $primeiros = [];

        if ($config['classificacao_visivel'] === 1 && $config['classificacao_quantidade'] > 0) {
            foreach ($classificacao as $linha) {
                if ($linha['posicao'] > $config['classificacao_quantidade']) {
                    break;
                }

                $primeiros[] = $linha;
            }
        }

        try {
            $presencas = (new PresencaCreditoRepository())->listarDaInscricao($inscricao['id']);
            $participacoes = (new CompeticaoParticipacaoRepository())->listarDaInscricao($inscricao['id']);
        } catch (\PDOException $e) {
            error_log('[Gamificacao] Falha ao ler o extrato da inscricao ' . (int) $inscricao['id'] . ': ' . $e->getMessage());
            $presencas = [];
            $participacoes = [];
        }

        $this->renderizar('eventoApp/pontuacao', [
            'evento' => $evento,
            'inscricao' => $inscricao,
            'config' => $config,
            'indisponivel' => $indisponivel,
            'minhaLinha' => GamificacaoService::linhaDaInscricao($classificacao, $inscricao['id']),
            'primeiros' => $primeiros,
            'presencas' => $presencas,
            'participacoes' => $participacoes,
            'gincanaEncerrada' => GamificacaoService::encerrada($evento['id']),
        ], 'Minha pontuação: ' . $evento['nome']);
    }

    /**
     * Fase 58: "Regras do jogo" - montada a partir do cadastro de cada
     * modulo (tipos de atividade, bonus, estandes, conexoes, redes,
     * competicoes, credenciamento, desempate), nunca de texto fixo: o que a
     * tela mostra e' o que o sistema vai de fato creditar. Todo texto de
     * explicacao mora na view.
     */
    public function regras($id)
    {
        $contexto = $this->contextoGamificacaoOuVolta($id);

        if ($contexto === null) {
            return;
        }

        $evento = $contexto['evento'];
        $eventoId = (int) $evento['id'];

        $tipos = (new EventoAtividadeTipoRepository())->listar($eventoId);
        $tiposPorId = [];

        foreach ($tipos as $tipo) {
            $tiposPorId[(int) $tipo['id']] = $tipo;
        }

        // Atividades com valor proprio, diferente do tipo: a tela lista cada
        // uma, porque a regra do tipo nao vale para elas.
        $atividadesComExcecao = [];

        foreach ((new EventoAtividadeRepository())->listarPorEvento($eventoId) as $atividade) {
            if ($atividade['pontos_presenca'] === null && $atividade['pontos_pontualidade'] === null) {
                continue;
            }

            $tipo = !empty($atividade['tipo_id']) && isset($tiposPorId[(int) $atividade['tipo_id']]) ? $tiposPorId[(int) $atividade['tipo_id']] : null;
            $atividadesComExcecao[] = [
                'nome' => $atividade['nome'],
                'data_inicio' => $atividade['data_inicio'],
                'valores' => PresencaPontuacaoService::valoresVigentes($atividade, $tipo),
            ];
        }

        $desempate = [];

        foreach ((new GamificacaoDesempateRepository())->listarCriterios($eventoId) as $linha) {
            $desempate[] = GamificacaoService::rotuloDoCriterio($linha['criterio']);
        }

        $divulgacaoConfig = new DivulgacaoConfigRepository();

        $this->renderizar('eventoApp/regras', [
            'evento' => $evento,
            'config' => $contexto['config'],
            'tipos' => $tipos,
            'atividadesComExcecao' => $atividadesComExcecao,
            'bonus' => (new BonusConfigRepository())->estaAtivo($eventoId) ? (new BonusRepository())->listarAtivos($eventoId) : [],
            'estandes' => (new EstandeRepository())->listarAtivosPublico($eventoId),
            'conexoes' => (new ConexaoConfigRepository())->vigente($eventoId),
            'divulgacao' => $divulgacaoConfig->vigente($eventoId),
            'redes' => $divulgacaoConfig->redesAtivas($eventoId),
            'competicoes' => (new CompeticaoRepository())->listarAtivasDoEvento($eventoId),
            'credenciamento' => (new CredenciamentoLocalRepository())->configVigente($eventoId),
            'desempate' => $desempate,
            'gincanaEncerrada' => GamificacaoService::encerrada($eventoId),
        ], 'Regras do jogo: ' . $evento['nome']);
    }

    /**
     * Evento, inscricao de quem pede e modulo Gamificacao ligado; sem eles, a
     * pessoa volta ao painel. Devolve null depois de redirecionar.
     */
    private function contextoGamificacaoOuVolta($id)
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

        $config = (new GamificacaoConfigRepository())->vigente($evento['id']);

        if ($config['ativo'] !== 1) {
            flashAlerta('A pontuação da gincana não está disponível neste evento.');
            $this->redirecionar('eventoApp/index/' . (int) $evento['id']);
            return null;
        }

        return ['evento' => $evento, 'inscricao' => $inscricao, 'config' => $config];
    }

    /**
     * Estandes ativos do evento e o progresso da pessoa. O codigo do estande
     * nunca sai nesta tela.
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
            'gincanaEncerrada' => GamificacaoService::encerrada($evento['id']),
            'estandes' => (new EstandeRepository())->listarAtivosPublico($id),
            'resumo' => $resumo,
            'visitados' => $visitados,
        ], 'Estandes: ' . $evento['nome']);
    }

    /**
     * Leitor do codigo do estande, com o mesmo componente de presenca().
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
     * Leitura do codigo do estande, em JSON, no contrato de validarPresenca().
     * Quem representa o estande nao pontua nele, e a visita nao tem janela de
     * horario (decisao do dono na Fase 54).
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

        // Fase 58: depois do encerramento da gincana a visita continua
        // registrada (ela interessa ao expositor), mas com zero ponto.
        $encerrada = GamificacaoService::encerrada($evento['id']);
        $registro = $visitas->registrar((int) $estande['id'], $inscricaoLeitor['id'], $encerrada ? 0 : (int) $estande['pontos_visita']);
        $tentativas->limparFalhas($usuarioId);
        $pontos = (int) $registro['pontos_creditados'];

        echo json_encode([
            'valido' => true,
            'mensagem' => 'Visita ao estande "' . $estande['nome'] . '" registrada'
                . ($encerrada
                    ? '. A gincana foi encerrada, então a visita não pontua mais.'
                    : ($pontos > 0 ? ': ' . $pontos . ($pontos === 1 ? ' ponto.' : ' pontos.') : '.')),
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

    /**
     * Fase 59: os certificados a que a pessoa tem direito neste evento, com o
     * botao de emitir ou de baixar cada um.
     *
     * Alcanca tambem quem nao tem inscricao (facilitador e avaliador avulso),
     * pelo mesmo criterio aberto na Fase 57 para a pesquisa de satisfacao: a
     * designacao deles e' a propria prova de participacao.
     */
    public function certificados($id)
    {
        $contexto = $this->contextoCertificadoOuVolta($id);

        if ($contexto === null) {
            return;
        }

        $evento = $contexto['evento'];
        $config = (new CertificadoConfigRepository())->vigente((int) $evento['id']);
        $elegibilidade = new CertificadoElegibilidadeService();

        if (!$elegibilidade->emissaoAbertaAoParticipante($evento, $config)) {
            flashAlerta('A organização ainda não abriu a emissão dos certificados deste evento.');
            $this->redirecionar('eventoApp/index/' . (int) $evento['id']);
            return;
        }

        $dossie = $elegibilidade->dossieDoUsuario($evento, $config, $contexto['usuarioId'], $contexto['inscricao']);
        $emitidos = (new CertificadoRepository())->mapaDoUsuarioNoEvento((int) $evento['id'], $contexto['usuarioId']);

        $this->renderizar('eventoApp/certificados', [
            'evento' => $evento,
            'inscricao' => $contexto['inscricao'],
            'dossie' => $dossie,
            'emitidos' => $emitidos,
            'exigencias' => CertificadoElegibilidadeService::exigenciasEmVigor($config),
            'cargaHoraria' => CertificadoElegibilidadeService::formatarCargaHoraria($dossie['minutos_total']),
        ], 'Certificados: ' . $evento['nome']);
    }

    /**
     * Fase 59: o proprio interessado pede o certificado. Gravacao, entao
     * nunca acontece em "visualizar como outro usuario" (o Router ja' recusa
     * POST nesse modo, e a tela tambem nao mostra o botao).
     *
     * O documento e' guardado na primeira emissao: pedir de novo entrega o
     * mesmo arquivo, e a chave unica da migration 199 garante isso mesmo com
     * duplo clique.
     */
    public function emitirCertificado($id)
    {
        $contexto = $this->contextoCertificadoOuVolta($id);

        if ($contexto === null) {
            return;
        }

        $evento = $contexto['evento'];
        $eventoId = (int) $evento['id'];
        $config = (new CertificadoConfigRepository())->vigente($eventoId);
        $elegibilidade = new CertificadoElegibilidadeService();

        if (!$elegibilidade->emissaoAbertaAoParticipante($evento, $config)) {
            flashAlerta('A organização ainda não abriu a emissão dos certificados deste evento.');
            $this->redirecionar('eventoApp/index/' . $eventoId);
            return;
        }

        $chave = isset($_POST['chave']) ? trim((string) $_POST['chave']) : '';
        $dossie = $elegibilidade->dossieDoUsuario($evento, $config, $contexto['usuarioId'], $contexto['inscricao']);
        $escolhido = null;

        // A apuracao e' a fonte de verdade: chave que nao esteja no dossie
        // desta pessoa nao alcanca nada, mesmo que alguem altere o
        // formulario.
        foreach ($dossie['itens'] as $item) {
            if ($item['chave'] === $chave) {
                $escolhido = $item;
                break;
            }
        }

        if ($escolhido === null) {
            flashAlerta('Não foi encontrado certificado a emitir. Recarregue a tela e tente outra vez.');
            $this->redirecionar('eventoApp/certificados/' . $eventoId);
            return;
        }

        try {
            $saida = (new CertificadoEmissaoService())->emitirItem($evento, $config, $dossie, $escolhido);

            if ($saida['novo']) {
                flashSucesso('Certificado emitido. Ele fica guardado e pode ser baixado quantas vezes você precisar.');
            } else {
                flashAlerta('Este certificado já havia sido emitido.');
            }
        } catch (\Throwable $e) {
            error_log('[Certificado] Falha ao emitir ' . $chave . ' pelo participante: ' . $e->getMessage());
            flashErro('Não foi possível emitir o certificado agora. Tente de novo em alguns minutos; se persistir, procure a organização do evento.');
        }

        $this->redirecionar('eventoApp/certificados/' . $eventoId);
    }

    /**
     * Entrega o arquivo guardado do certificado, que precisa ser da propria
     * pessoa e nao estar cancelado.
     */
    public function certificadoArquivo($id, $certificadoId = null)
    {
        $contexto = $this->contextoCertificadoOuVolta($id);

        if ($contexto === null) {
            return;
        }

        $certificado = (new CertificadoRepository())->buscarPorId($certificadoId);

        if ($certificado === null
            || (int) $certificado['evento_id'] !== (int) $contexto['evento']['id']
            || (int) $certificado['usuario_id'] !== (int) $contexto['usuarioId']) {
            http_response_code(404);
            exit('Certificado não encontrado.');
        }

        if ($certificado['cancelado_em'] !== null) {
            http_response_code(404);
            exit('Este certificado foi cancelado pela organização em ' . formatarData($certificado['cancelado_em']) . '.');
        }

        ArquivoPrivadoService::servir(
            $certificado['arquivo_path'],
            'certificado-' . $certificado['codigo_verificacao'] . '.pdf'
        );
    }

    /**
     * Fase 59: mesmo molde de contextoPesquisaOuVolta() - quem nao tem
     * inscricao segue adiante quando conduz atividade, avalia trabalho ou e'
     * autor de trabalho apresentado neste evento.
     */
    private function contextoCertificadoOuVolta($id)
    {
        if (!Auth::autenticado()) {
            $this->redirecionar('eventoInscricao/index/' . (int) $id);
            return null;
        }

        $evento = (new SemanaInovacaoRepository())->buscarPorId($id);

        if ($evento === null) {
            $this->redirecionar('eventoApp/index');
            return null;
        }

        $usuarioId = Auth::usuarioId();
        $inscricao = (new EventoInscricaoRepository())->buscarPorEventoEUsuario($id, $usuarioId);

        if ($inscricao === null) {
            $ehFacilitador = (new EventoAtividadeFacilitadorRepository())->listarPorUsuarioNoEvento($usuarioId, $id) !== [];
            $ehAvaliador = (new TrabalhoAvaliadorRepository())->estaAtivo($id, $usuarioId);
            $ehAutor = (new TrabalhoApresentacaoRepository())->listarAutoriasApresentadasDoUsuario($id, $usuarioId) !== [];

            if (!$ehFacilitador && !$ehAvaliador && !$ehAutor) {
                $this->redirecionar('eventoInscricao/index/' . (int) $id);
                return null;
            }
        }

        return ['evento' => $evento, 'inscricao' => $inscricao, 'usuarioId' => $usuarioId];
    }
}
