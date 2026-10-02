<?php

namespace App\Controllers;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Core\Controller;
use App\Core\View;
use App\Middleware\RoleMiddleware;
use App\Repositories\EstandeConfigRepository;
use App\Repositories\EstandeRepository;
use App\Repositories\EstandeRepresentanteRepository;
use App\Repositories\SemanaInovacaoRepository;
use App\Services\EstandeRepresentanteConviteService;
use App\Services\ImagemService;

/**
 * Fase 54: Estandes do Evento (no da arvore irmao de Atividades e
 * Trabalhos, sem no por estande). Suporte le tudo, inclusive o cartaz, como
 * em Atividades e Trabalhos; toda gravacao exige Administrador.
 */
class EstandeAdminController extends Controller
{
    private $eventos;
    private $estandes;
    private $imagens;

    public function __construct()
    {
        RoleMiddleware::exigir(['administrador', 'suporte']);
        $this->eventos = new SemanaInovacaoRepository();
        $this->estandes = new EstandeRepository();
        $this->imagens = new ImagemService();
    }

    private function eventoOu404($eventoId)
    {
        $evento = $this->eventos->buscarPorId($eventoId);

        if ($evento === null) {
            http_response_code(404);
            exit('Evento não encontrado.');
        }

        return $evento;
    }

    private function estandeOu404($id)
    {
        $estande = $this->estandes->buscarPorId($id);

        if ($estande === null) {
            http_response_code(404);
            exit('Estande não encontrado.');
        }

        return $estande;
    }

    public function index($eventoId)
    {
        $evento = $this->eventoOu404($eventoId);

        $filtros = [
            'busca' => isset($_GET['busca']) ? trim((string) $_GET['busca']) : '',
            'categoria' => isset($_GET['categoria']) ? trim((string) $_GET['categoria']) : '',
            'situacao' => isset($_GET['situacao']) ? trim((string) $_GET['situacao']) : '',
        ];

        $this->renderizar('admin/estandes/index', [
            'evento' => $evento,
            'estandes' => $this->estandes->listarPorEvento($eventoId, $filtros),
            'filtros' => $filtros,
            'podeEditar' => \App\Core\Auth::possuiPerfil('administrador'),
        ], 'Estandes: ' . $evento['nome'], ['tipo' => 'estandes', 'id' => (int) $eventoId]);
    }

    public function novo($eventoId)
    {
        RoleMiddleware::exigir(['administrador']);
        $evento = $this->eventoOu404($eventoId);
        $erro = null;
        $estande = null;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $dados = $this->dadosDoFormulario(null);

            if (is_string($dados)) {
                $erro = $dados;
                $estande = $this->valoresEnviados();
            } else {
                $id = $this->estandes->criar((int) $evento['id'], $dados);
                flashSucesso('Estande criado. O código de visita já foi gerado: imprima o cartaz e, se quiser, convide o representante.');
                $this->redirecionar('estandes/editar/' . $id);
                return;
            }
        }

        $this->renderizar('admin/estandes/form', [
            'erro' => $erro,
            'evento' => $evento,
            'estande' => $estande,
            'representante' => null,
            'podeEditar' => true,
        ], 'Novo estande: ' . $evento['nome'], ['tipo' => 'estandes', 'id' => (int) $eventoId]);
    }

    public function editar($id)
    {
        $estande = $this->estandeOu404($id);
        $evento = $this->eventoOu404($estande['evento_id']);
        $erro = null;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            RoleMiddleware::exigir(['administrador']);
            $dados = $this->dadosDoFormulario($estande);

            if (is_string($dados)) {
                $erro = $dados;
            } else {
                $this->estandes->atualizar((int) $estande['id'], $dados);
                flashSucesso('Estande salvo.');
                $this->redirecionar('estandes/editar/' . (int) $estande['id']);
                return;
            }
        }

        $this->renderizar('admin/estandes/form', [
            'erro' => $erro,
            'evento' => $evento,
            'estande' => $estande,
            'representante' => (new EstandeRepresentanteRepository())->buscarPorEstande((int) $estande['id']),
            'podeEditar' => \App\Core\Auth::possuiPerfil('administrador'),
        ], 'Estande: ' . $estande['nome'], ['tipo' => 'estandes', 'id' => (int) $evento['id']]);
    }

    public function remover()
    {
        RoleMiddleware::exigir(['administrador']);
        $id = (int) (isset($_POST['id']) ? $_POST['id'] : 0);
        $estande = $this->estandeOu404($id);
        $eventoId = (int) $estande['evento_id'];

        try {
            $resultado = $this->estandes->removerSemVisitas($eventoId, $id);

            if ($resultado['ok']) {
                $this->imagens->remover($resultado['logotipo_path']);
                flashSucesso($resultado['mensagem']);
            } else {
                flashErro($resultado['mensagem']);
            }
        } catch (\PDOException $e) {
            flashErro($e->getCode() === '23000'
                ? 'Não é possível remover: este estande acabou de receber uma visita. Para tirá-lo do aplicativo e da página, desmarque "Ativo".'
                : 'Não foi possível remover o estande.');
        }

        $this->redirecionar('estandes/index/' . $eventoId);
    }

    public function reordenar($eventoId)
    {
        RoleMiddleware::exigir(['administrador']);
        header('Content-Type: application/json; charset=utf-8');
        $corpo = json_decode((string) file_get_contents('php://input'), true);
        $ids = isset($corpo['ids']) && is_array($corpo['ids']) ? array_map('intval', $corpo['ids']) : [];

        $this->estandes->reordenar((int) $eventoId, $ids);

        echo json_encode(['ok' => true]);
    }

    /**
     * Cartaz A4 do estande (QR mais o codigo em texto), pagina solta sem
     * layout.php, no mesmo molde do cartaz de Atividade.
     */
    public function codigo($id)
    {
        $estande = $this->estandeOu404($id);
        $evento = $this->eventoOu404($estande['evento_id']);

        echo View::renderizarString('admin/estandes/cartaz_impressao', [
            'evento' => $evento,
            'estande' => $estande,
        ]);
    }

    public function configuracoes($eventoId)
    {
        $evento = $this->eventoOu404($eventoId);
        $config = new EstandeConfigRepository();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            RoleMiddleware::exigir(['administrador']);
            $mensagem = isset($_POST['mensagem_convite_html']) ? sanitizarHtmlRico($_POST['mensagem_convite_html']) : '';
            $config->salvar((int) $evento['id'], $mensagem);
            flashSucesso('Configurações de Estandes salvas.');
            $this->redirecionar('estandes/configuracoes/' . (int) $evento['id']);
            return;
        }

        $this->renderizar('admin/estandes/configuracoes', [
            'evento' => $evento,
            'config' => $config->buscarPorEvento((int) $evento['id']),
            'podeEditar' => \App\Core\Auth::possuiPerfil('administrador'),
        ], 'Configurações de Estandes: ' . $evento['nome'], ['tipo' => 'estandesConfiguracoes', 'id' => (int) $eventoId]);
    }

    public function convidarRepresentante()
    {
        $this->acaoRepresentante('convidar');
    }

    public function substituirRepresentante()
    {
        $this->acaoRepresentante('substituir');
    }

    public function removerRepresentante()
    {
        $this->acaoRepresentante('remover');
    }

    /**
     * Convite, troca e remocao passam pelo servico isolado
     * (EstandeRepresentanteConviteService), nunca pelo servico de convite
     * do Concurso. O estande sai do banco pelo id enviado, e o evento do
     * proprio estande.
     */
    private function acaoRepresentante($acao)
    {
        RoleMiddleware::exigir(['administrador']);
        $estande = $this->estandeOu404((int) (isset($_POST['estande_id']) ? $_POST['estande_id'] : 0));
        $evento = $this->eventoOu404($estande['evento_id']);
        $servico = new EstandeRepresentanteConviteService();
        $nome = isset($_POST['nome']) ? $_POST['nome'] : '';
        $email = isset($_POST['email']) ? $_POST['email'] : '';

        try {
            if ($acao === 'remover') {
                $servico->remover($estande);
                flashSucesso('Representante removido. O acesso dele a este estande foi encerrado.');
            } else {
                $resultado = $acao === 'convidar'
                    ? $servico->convidar($nome, $email, $estande, $evento)
                    : $servico->substituir($nome, $email, $estande, $evento);

                $mensagem = $acao === 'convidar' ? 'Representante convidado.' : 'Representante substituído; o anterior perdeu o acesso a este estande.';
                if (!$resultado['ja_existia']) {
                    $mensagem .= ' A conta foi criada e o convite com o endereço para definir a senha foi enviado por e-mail.';
                } elseif ($resultado['enviou_endereco_senha']) {
                    $mensagem .= ' A pessoa já tinha conta, ainda sem senha, e recebeu por e-mail o convite com o endereço para definir a senha.';
                } else {
                    $mensagem .= ' A pessoa já tinha conta e recebeu o aviso por e-mail; ela entra com o acesso de sempre.';
                }

                if ($resultado['tinha_outro_perfil']) {
                    flashAlerta($mensagem . ' Atenção: este e-mail já tem outro perfil no sistema; ao entrar, a pessoa vai primeiro para o painel desse outro perfil e chega ao estande pelo endereço do painel de representante.');
                } else {
                    flashSucesso($mensagem);
                }
            }
        } catch (\RuntimeException $e) {
            flashErro($e->getMessage());
        }

        $this->redirecionar('estandes/editar/' . (int) $estande['id']);
    }

    /**
     * Valores do formulario, conferidos. Devolve o array para gravar ou a
     * mensagem de erro (string).
     */
    private function dadosDoFormulario(array $atual = null)
    {
        $nome = trim(isset($_POST['nome']) ? (string) $_POST['nome'] : '');
        $categoria = isset($_POST['categoria']) ? (string) $_POST['categoria'] : '';
        $pontosBruto = trim(isset($_POST['pontos_visita']) ? (string) $_POST['pontos_visita'] : '0');

        if ($nome === '' || mb_strlen($nome, 'UTF-8') > 150) {
            return 'Informe o nome do estande (até 150 caracteres).';
        }

        if (!isset(EstandeRepository::CATEGORIAS[$categoria])) {
            return 'Escolha a categoria do estande.';
        }

        if ($pontosBruto === '' || !ctype_digit($pontosBruto) || (int) $pontosBruto > 65535) {
            return 'Informe os pontos da visita como número inteiro, de 0 a 65535.';
        }

        $logotipoPath = $atual !== null ? $atual['logotipo_path'] : null;
        $logotipoAlt = trim(isset($_POST['logotipo_alt']) ? (string) $_POST['logotipo_alt'] : '');
        $enviouLogotipo = !empty($_FILES['logotipo']) && $_FILES['logotipo']['error'] !== UPLOAD_ERR_NO_FILE;

        if (($enviouLogotipo || $logotipoPath !== null) && $logotipoAlt === '') {
            return 'Informe o texto alternativo do logotipo (descrição curta da imagem, lida por quem usa leitor de tela).';
        }

        if ($enviouLogotipo) {
            try {
                $novo = $this->imagens->salvar($_FILES['logotipo'], 'evento-estandes', 600, 300);
            } catch (\RuntimeException $e) {
                return $e->getMessage();
            }

            if ($logotipoPath !== null) {
                $this->imagens->remover($logotipoPath);
            }

            $logotipoPath = $novo;
        }

        return [
            'nome' => $nome,
            'categoria' => $categoria,
            'descricao_html' => isset($_POST['descricao_html']) ? sanitizarHtmlRico($_POST['descricao_html']) : '',
            'logotipo_path' => $logotipoPath,
            'logotipo_alt' => $logotipoPath !== null ? $logotipoAlt : null,
            'pontos_visita' => (int) $pontosBruto,
            'ativo' => isset($_POST['ativo']) ? 1 : 0,
        ];
    }

    /**
     * Valores enviados para reexibir o formulario de criacao depois de um
     * erro, sem perder o que foi digitado.
     */
    private function valoresEnviados()
    {
        return [
            'id' => null,
            'nome' => isset($_POST['nome']) ? (string) $_POST['nome'] : '',
            'categoria' => isset($_POST['categoria']) ? (string) $_POST['categoria'] : 'expositor',
            'descricao_html' => isset($_POST['descricao_html']) ? sanitizarHtmlRico($_POST['descricao_html']) : '',
            'logotipo_path' => null,
            'logotipo_alt' => isset($_POST['logotipo_alt']) ? (string) $_POST['logotipo_alt'] : '',
            'pontos_visita' => isset($_POST['pontos_visita']) ? (string) $_POST['pontos_visita'] : '0',
            'ativo' => isset($_POST['ativo']) ? 1 : 0,
            'codigo_estande' => null,
        ];
    }
}
