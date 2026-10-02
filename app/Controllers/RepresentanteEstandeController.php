<?php

namespace App\Controllers;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Core\Auth;
use App\Core\Controller;
use App\Core\View;
use App\Middleware\RoleMiddleware;
use App\Repositories\EstandeRepository;
use App\Repositories\EstandeRepresentanteRepository;
use App\Repositories\EstandeVisitaRepository;
use App\Repositories\EventoInscricaoRepository;
use App\Repositories\PesquisaConfigRepository;
use App\Repositories\PesquisaPerguntaRepository;
use App\Repositories\PesquisaRespondenteRepository;
use App\Repositories\SemanaInovacaoRepository;
use App\Services\ImagemService;
use App\Services\PesquisaService;

/**
 * Fase 54: painel de quem representa um estande (perfil
 * representante_estande, criado por convite do Administrador). A pessoa pode
 * representar estandes de eventos diferentes, um por evento, por isso cada
 * acao recebe o numero do estande e so' o aceita se o vinculo com quem esta
 * autenticado existir; senao, volta para a lista sem dizer se o estande
 * existe (mesmo cuidado de EventoAppController::cracha()).
 */
class RepresentanteEstandeController extends Controller
{
    private $representantes;

    public function __construct()
    {
        RoleMiddleware::exigir([EstandeRepresentanteRepository::PERFIL]);
        $this->representantes = new EstandeRepresentanteRepository();
    }

    public function index()
    {
        $estandes = $this->representantes->listarPorUsuario(Auth::usuarioId());

        if (count($estandes) === 1) {
            $this->redirecionar('representanteEstande/estande/' . (int) $estandes[0]['id']);
            return;
        }

        if (empty($estandes)) {
            $this->renderizar('representanteEstande/sem_estande', [
                'temInscricaoEvento' => $this->temInscricaoEmEvento(),
            ], 'Representante de estande');
            return;
        }

        $pesquisaAbertaPorEvento = [];
        $eventos = new SemanaInovacaoRepository();

        foreach ($estandes as $item) {
            $eventoId = (int) $item['evento_id'];

            if (!isset($pesquisaAbertaPorEvento[$eventoId])) {
                $evento = $eventos->buscarPorId($eventoId);
                $pesquisaAbertaPorEvento[$eventoId] = $evento !== null && $this->pesquisaAberta($evento);
            }
        }

        $this->renderizar('representanteEstande/index', [
            'estandes' => $estandes,
            'pesquisaAbertaPorEvento' => $pesquisaAbertaPorEvento,
        ], 'Meus estandes');
    }

    public function estande($id = null)
    {
        $estande = $this->estandeDoUsuario($id);
        $evento = (new SemanaInovacaoRepository())->buscarPorId($estande['evento_id']);
        $pesquisaAberta = $evento !== null && $this->pesquisaAberta($evento);

        $this->renderizar('representanteEstande/estande', [
            'estande' => $estande,
            'evento' => $evento,
            'totalVisitas' => (new EstandeVisitaRepository())->contarVisitas((int) $estande['id']),
            'maisDeUmEstande' => count($this->representantes->listarPorUsuario(Auth::usuarioId())) > 1,
            'inscritoNoEvento' => (new EventoInscricaoRepository())->buscarPorEventoEUsuario((int) $estande['evento_id'], Auth::usuarioId()) !== null,
            'pesquisaAberta' => $pesquisaAberta,
            'pesquisaRespondida' => $pesquisaAberta && (new PesquisaRespondenteRepository())->jaRespondeu((int) $estande['evento_id'], Auth::usuarioId()),
        ], 'Estande: ' . $estande['nome']);
    }

    public function editar($id = null)
    {
        $estande = $this->estandeDoUsuario($id);
        $evento = (new SemanaInovacaoRepository())->buscarPorId($estande['evento_id']);
        $erro = null;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $erro = $this->salvar($estande);

            if ($erro === null) {
                flashSucesso('Dados do estande salvos. Eles já aparecem para os participantes.');
                $this->redirecionar('representanteEstande/estande/' . (int) $estande['id']);
                return;
            }
        }

        $this->renderizar('representanteEstande/form', [
            'estande' => $estande,
            'evento' => $evento,
            'erro' => $erro,
        ], 'Editar estande: ' . $estande['nome']);
    }

    public function cartaz($id = null)
    {
        $estande = $this->estandeDoUsuario($id);
        $evento = (new SemanaInovacaoRepository())->buscarPorId($estande['evento_id']);

        echo View::renderizarString('admin/estandes/cartaz_impressao', [
            'evento' => $evento,
            'estande' => $estande,
        ]);
    }

    private function estandeDoUsuario($id)
    {
        $estande = $id !== null ? $this->representantes->buscarVinculo((int) $id, Auth::usuarioId()) : null;

        if ($estande === null) {
            $this->redirecionar('representanteEstande/index');
        }

        return $estande;
    }

    private function temInscricaoEmEvento()
    {
        return !empty((new EventoInscricaoRepository())->listarPorUsuario(Auth::usuarioId()));
    }

    /**
     * So' nome, descricao e logotipo. Codigo, pontos, categoria e ativo nao
     * sao lidos do formulario, mesmo que venham nele.
     */
    private function salvar(array $estande)
    {
        $nome = trim(isset($_POST['nome']) ? (string) $_POST['nome'] : '');

        if ($nome === '' || mb_strlen($nome, 'UTF-8') > 150) {
            return 'Informe o nome do estande (até 150 caracteres).';
        }

        $imagens = new ImagemService();
        $logotipoPath = $estande['logotipo_path'];
        $logotipoAlt = trim(isset($_POST['logotipo_alt']) ? (string) $_POST['logotipo_alt'] : '');
        $enviouLogotipo = !empty($_FILES['logotipo']) && $_FILES['logotipo']['error'] !== UPLOAD_ERR_NO_FILE;

        if (($enviouLogotipo || $logotipoPath !== null) && $logotipoAlt === '') {
            return 'Informe o texto alternativo do logotipo (descrição curta da imagem, lida por quem usa leitor de tela).';
        }

        if ($enviouLogotipo) {
            try {
                $novo = $imagens->salvar($_FILES['logotipo'], 'evento-estandes', 600, 300);
            } catch (\RuntimeException $e) {
                return $e->getMessage();
            }

            if ($logotipoPath !== null) {
                $imagens->remover($logotipoPath);
            }

            $logotipoPath = $novo;
        }

        (new EstandeRepository())->atualizarPeloRepresentante((int) $estande['id'], [
            'nome' => $nome,
            'descricao_html' => isset($_POST['descricao_html']) ? sanitizarHtmlRico($_POST['descricao_html']) : '',
            'logotipo_path' => $logotipoPath,
            'logotipo_alt' => $logotipoPath !== null ? $logotipoAlt : null,
        ]);

        return null;
    }

    /**
     * Mesma regra de EventoAppController::pesquisaAberta(): modulo ligado,
     * pergunta ativa e dentro da janela.
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
}
