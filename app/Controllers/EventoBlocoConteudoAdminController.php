<?php

namespace App\Controllers;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Core\Controller;
use App\Core\Texto;
use App\Middleware\RoleMiddleware;
use App\Repositories\EventoBlocoConteudoRepository;
use App\Repositories\EventoSecaoOrdemRepository;
use App\Repositories\SemanaInovacaoRepository;
use App\Services\ImagemService;

/**
 * Fase 50: blocos de conteudo proprios de cada Evento (sub-aba "Blocos de
 * conteúdo" da ficha do Evento) - mesmo desenho de BlocoConteudoAdminController
 * (Concurso), mas toda acao e escopada por evento_id, sem blocos padrao fixos
 * (Evento nao tem "Sobre"/"Premiacao") e sem a coluna mostrar_no_rodape (o
 * rodape e unico/compartilhado, nao tem secao propria de Evento).
 */
class EventoBlocoConteudoAdminController extends Controller
{
    private $eventos;
    private $blocos;
    private $imagens;

    public function __construct()
    {
        RoleMiddleware::exigir(['administrador']);
        $this->eventos = new SemanaInovacaoRepository();
        $this->blocos = new EventoBlocoConteudoRepository();
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

    public function index($eventoId)
    {
        $evento = $this->eventoOu404($eventoId);

        // Fase 51: a ordem da pagina saiu daqui. Cada bloco e' uma secao
        // entre outras (quadros, faixas, componentes), e a ordem de todas
        // elas fica numa tela so', "Seções da página" - por isso esta
        // listagem voltou a ser a lista simples dos blocos do evento.
        $this->renderizar('admin/evento_blocos/index', [
            'evento' => $evento,
            'blocos' => $this->blocos->listar($eventoId),
        ], 'Blocos de conteúdo: ' . $evento['nome'], ['tipo' => 'eventoBlocos', 'id' => (int) $eventoId]);
    }

    public function novo($eventoId)
    {
        $evento = $this->eventoOu404($eventoId);
        $erro = null;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $erro = $this->salvarNovo($eventoId);

            if ($erro === null) {
                $this->redirecionar('eventoBlocos/index/' . $eventoId);
                return;
            }
        }

        $this->renderizar('admin/evento_blocos/form', [
            'erro' => $erro,
            'evento' => $evento,
            'bloco' => null,
        ], 'Novo bloco: ' . $evento['nome'], ['tipo' => 'eventoBlocos', 'id' => (int) $eventoId]);
    }

    public function editar($id)
    {
        $bloco = $this->blocos->buscarPorId($id);

        if ($bloco === null) {
            http_response_code(404);
            exit('Bloco não encontrado.');
        }

        $evento = $this->eventoOu404($bloco['evento_id']);
        $erro = null;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $erro = $this->salvarEdicao($bloco);
            $bloco = $this->blocos->buscarPorId($id);
        }

        $this->renderizar('admin/evento_blocos/form', [
            'erro' => $erro,
            'evento' => $evento,
            'bloco' => $bloco,
        ], 'Editar bloco: ' . $evento['nome'], ['tipo' => 'eventoBlocos', 'id' => (int) $evento['id']]);
    }

    public function remover()
    {
        $id = (int) (isset($_POST['id']) ? $_POST['id'] : 0);
        $eventoId = (int) (isset($_POST['evento_id']) ? $_POST['evento_id'] : 0);
        $bloco = $this->blocos->buscarPorId($id);

        try {
            // Fase 51: a linha de ordem da pagina aponta para o bloco por
            // referencia polimorfica, sem chave estrangeira - se ela nao
            // sair junto, sobra uma secao apontando para bloco que nao
            // existe mais.
            (new EventoSecaoOrdemRepository())->removerSecao($eventoId, 'bloco', $id);
            $this->blocos->remover($id);

            if ($bloco !== null) {
                $this->imagens->remover($bloco['imagem_path']);
            }

            $_SESSION['flash'] = 'Bloco removido.';
        } catch (\PDOException $e) {
            flashErro('Não foi possível remover o bloco.');
        }

        $this->redirecionar('eventoBlocos/index/' . $eventoId);
    }

    private function dadosComuns()
    {
        $ancora = Texto::slugify(trim(isset($_POST['secao_ancora']) ? $_POST['secao_ancora'] : ''));

        return [
            // Reabertura da Fase 51: etiqueta colorida acima do titulo, cores
            // dos botoes e a opcao de herdar a cor do rodape (chamada final
            // de inscricao encostada nele).
            'etiqueta' => $this->campoOuNulo('etiqueta'),
            'etiqueta_cor' => $this->campoOuNulo('etiqueta_cor'),
            'usar_cor_rodape' => isset($_POST['usar_cor_rodape']) ? 1 : 0,
            'cta_cor_fundo' => $this->campoOuNulo('cta_cor_fundo'),
            'cta_cor_texto' => $this->campoOuNulo('cta_cor_texto'),
            'cta2_cor_fundo' => $this->campoOuNulo('cta2_cor_fundo'),
            'cta2_cor_texto' => $this->campoOuNulo('cta2_cor_texto'),
            'titulo' => trim(isset($_POST['titulo']) ? $_POST['titulo'] : ''),
            'conteudo_html' => isset($_POST['conteudo_html']) ? sanitizarHtmlRico($_POST['conteudo_html']) : '',
            'imagem_posicao' => $this->valorPermitido('imagem_posicao', EventoBlocoConteudoRepository::IMAGEM_POSICOES, 'esquerda'),
            // Fase 51: cor propria por bloco e segundo botao. O liga/desliga
            // no menu saiu daqui: mora em "Seções da página".
            'cor_fundo' => $this->campoOuNulo('cor_fundo'),
            'cor_texto' => $this->campoOuNulo('cor_texto'),
            'cta_titulo' => $this->campoOuNulo('cta_titulo'),
            'cta_link' => $this->campoOuNulo('cta_link'),
            'cta_alinhamento' => $this->valorPermitido('cta_alinhamento', EventoBlocoConteudoRepository::CTA_ALINHAMENTOS, 'esquerda'),
            'cta2_titulo' => $this->campoOuNulo('cta2_titulo'),
            'cta2_link' => $this->campoOuNulo('cta2_link'),
            'secao_ancora' => $ancora !== '' ? $ancora : 'bloco',
            'ativo' => isset($_POST['ativo']) ? 1 : 0,
        ];
    }

    private function campoOuNulo($chave)
    {
        $valor = trim(isset($_POST[$chave]) ? $_POST[$chave] : '');

        return $valor !== '' ? $valor : null;
    }

    private function valorPermitido($chave, array $permitidos, $padrao)
    {
        $valor = isset($_POST[$chave]) ? $_POST[$chave] : $padrao;

        return in_array($valor, $permitidos, true) ? $valor : $padrao;
    }

    private function salvarNovo($eventoId)
    {
        $dados = $this->dadosComuns();

        if ($dados['titulo'] === '') {
            return 'Informe o título do bloco.';
        }

        if (!empty($dados['cta_titulo']) && empty($dados['cta_link'])) {
            return 'Informe o link do botão (ou remova o título do botão): o sistema não permite salvar um botão sem destino.';
        }

        try {
            $dados['imagem_path'] = null;
            $dados['imagem_alt'] = null;

            if (!empty($_FILES['imagem']) && $_FILES['imagem']['error'] === UPLOAD_ERR_OK) {
                $alt = trim(isset($_POST['imagem_alt']) ? $_POST['imagem_alt'] : '');

                if ($alt === '') {
                    return 'Informe o texto alternativo (alt) da imagem.';
                }

                $dados['imagem_path'] = $this->imagens->salvar($_FILES['imagem'], 'evento-blocos', 900, 900);
                $dados['imagem_alt'] = $alt;
            }
        } catch (\RuntimeException $e) {
            return $e->getMessage();
        }

        $novoId = $this->blocos->criar($eventoId, $dados);
        (new EventoSecaoOrdemRepository())->registrarSecao($eventoId, 'bloco', $novoId, $dados['titulo']);

        return null;
    }

    private function salvarEdicao(array $blocoAtual)
    {
        $dados = $this->dadosComuns();

        if ($dados['titulo'] === '') {
            return 'Informe o título do bloco.';
        }

        if (!empty($dados['cta_titulo']) && empty($dados['cta_link'])) {
            return 'Informe o link do botão (ou remova o título do botão): o sistema não permite salvar um botão sem destino.';
        }

        $dados['imagem_path'] = $blocoAtual['imagem_path'];
        $dados['imagem_alt'] = $blocoAtual['imagem_alt'];

        try {
            if (!empty($_FILES['imagem']) && $_FILES['imagem']['error'] === UPLOAD_ERR_OK) {
                $alt = trim(isset($_POST['imagem_alt']) ? $_POST['imagem_alt'] : '');

                if ($alt === '') {
                    return 'Informe o texto alternativo (alt) da imagem.';
                }

                $dados['imagem_path'] = $this->imagens->salvar($_FILES['imagem'], 'evento-blocos', 900, 900);
                $dados['imagem_alt'] = $alt;
                $this->imagens->remover($blocoAtual['imagem_path']);
            } elseif (isset($_POST['imagem_alt'])) {
                $dados['imagem_alt'] = trim($_POST['imagem_alt']);
            }
        } catch (\RuntimeException $e) {
            return $e->getMessage();
        }

        $this->blocos->atualizar($blocoAtual['id'], $dados);

        return null;
    }
}
