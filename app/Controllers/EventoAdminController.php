<?php

namespace App\Controllers;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Core\Controller;
use App\Middleware\RoleMiddleware;
use App\Repositories\BlocoConteudoRepository;
use App\Repositories\EventoCampoInscricaoRepository;
use App\Repositories\EventoCheckinRepository;
use App\Repositories\EventoComunicacaoRepository;
use App\Repositories\EventoDivulgacaoRepository;
use App\Repositories\EventoInscricaoRepository;
use App\Repositories\EventoPerfilOrganizacaoRepository;
use App\Repositories\NotificacaoPainelRepository;
use App\Repositories\SemanaInovacaoRepository;
use App\Services\EjurrExportService;
use App\Services\ImagemService;

/**
 * Fase 39 (revisada): CRUD da entidade Evento (aba de 1o nivel "Eventos",
 * mesmo status estrutural de Concurso - ver ConcursoAdminController). Sem
 * perfil escopado por evento: administrador/suporte GLOBAL (concurso_id
 * NULL), mesmo criterio de ContatoConcursoAdminController/
 * ConfiguracaoAdminController - Evento nao pertence a nenhum concurso.
 */
class EventoAdminController extends Controller
{
    private $eventos;
    private $inscricoes;
    private $camposInscricao;
    private $comunicacoes;

    public function __construct()
    {
        RoleMiddleware::exigir(['administrador', 'suporte']);
        $this->eventos = new SemanaInovacaoRepository();
        $this->inscricoes = new EventoInscricaoRepository();
        $this->camposInscricao = new EventoCampoInscricaoRepository();
        $this->comunicacoes = new EventoComunicacaoRepository();
    }

    public function index()
    {
        $this->renderizar('admin/eventos/index', [
            'eventos' => $this->eventos->listar(),
        ], 'Eventos');
    }

    public function novo()
    {
        RoleMiddleware::exigir(['administrador']);
        $erro = null;
        $evento = null;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $dados = $this->dadosDoFormulario();
            $erro = $this->validar($dados);

            if ($erro === null) {
                $id = $this->eventos->criar($dados);
                $this->redirecionar('eventos/editar/' . $id);
                return;
            }

            $evento = $dados;
        }

        $this->renderizar('admin/eventos/form', [
            'erro' => $erro,
            'evento' => $evento,
        ], 'Novo evento');
    }

    public function editar($id)
    {
        $evento = $this->eventos->buscarPorId($id);

        if ($evento === null) {
            http_response_code(404);
            exit('Evento não encontrado.');
        }

        $erro = null;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            RoleMiddleware::exigir(['administrador']);
            $dados = $this->dadosDoFormulario();
            $erro = $this->validar($dados);

            if ($erro === null) {
                $this->eventos->atualizar($id, $dados);
                $evento = $this->eventos->buscarPorId($id);
            } else {
                $evento = $dados + ['id' => $evento['id']];
            }
        }

        $this->renderizar('admin/eventos/form', [
            'erro' => $erro,
            'evento' => $evento,
        ], 'Editar evento', ['tipo' => 'evento', 'id' => (int) $id]);
    }

    public function remover()
    {
        RoleMiddleware::exigir(['administrador']);
        $id = (int) (isset($_POST['id']) ? $_POST['id'] : 0);
        $evento = $this->eventos->buscarPorId($id);

        if ($evento === null) {
            http_response_code(404);
            exit('Evento não encontrado.');
        }

        try {
            $this->eventos->remover($id);
            flashSucesso('Evento removido.');
        } catch (\PDOException $e) {
            flashErro($e->getCode() === '23000'
                ? 'Não é possível remover: este evento já tem inscrições.'
                : 'Não foi possível remover o evento.');
        }

        $this->redirecionar('eventos/index');
    }

    /**
     * Fase 40: bloco de chamada do evento na home publica (sub-aba
     * "Divulgacao na home"). Sempre upsert - evento_divulgacao tem no maximo
     * 1 linha por evento (ver EventoDivulgacaoRepository::salvar()), entao
     * nao ha' distincao "novo vs editar" como em editar(): $atual pode ser
     * null na primeira gravacao.
     */
    public function divulgacao($id)
    {
        $evento = $this->eventos->buscarPorId($id);

        if ($evento === null) {
            http_response_code(404);
            exit('Evento não encontrado.');
        }

        $repositorioDivulgacao = new EventoDivulgacaoRepository();
        $atual = $repositorioDivulgacao->buscarPorEvento($id);
        $erro = null;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            RoleMiddleware::exigir(['administrador']);

            $dados = [
                'titulo' => trim(isset($_POST['titulo']) ? $_POST['titulo'] : ''),
                'conteudo_html' => isset($_POST['conteudo_html']) ? sanitizarHtmlRico($_POST['conteudo_html']) : null,
                'imagem_posicao' => in_array(isset($_POST['imagem_posicao']) ? $_POST['imagem_posicao'] : null, BlocoConteudoRepository::IMAGEM_POSICOES, true)
                    ? $_POST['imagem_posicao']
                    : 'esquerda',
                'cta_titulo' => $this->campoOuNulo('cta_titulo'),
                'cta_alinhamento' => in_array(isset($_POST['cta_alinhamento']) ? $_POST['cta_alinhamento'] : null, BlocoConteudoRepository::CTA_ALINHAMENTOS, true)
                    ? $_POST['cta_alinhamento']
                    : 'esquerda',
                'ativo' => isset($_POST['ativo']) ? 1 : 0,
            ];

            if ($dados['titulo'] === '') {
                $dados['titulo'] = null;
            }

            if ($dados['ativo'] === 1 && $dados['titulo'] === null) {
                $erro = 'Informe o título antes de ativar. Sem título, o bloco não aparece na home.';
            }

            $dados['imagem_path'] = $atual !== null ? $atual['imagem_path'] : null;
            $dados['imagem_alt'] = $atual !== null ? $atual['imagem_alt'] : null;

            if ($erro === null) {
                try {
                    if (!empty($_FILES['imagem']) && $_FILES['imagem']['error'] === UPLOAD_ERR_OK) {
                        $alt = trim(isset($_POST['imagem_alt']) ? $_POST['imagem_alt'] : '');

                        if ($alt === '') {
                            $erro = 'Informe o texto alternativo (alt) da imagem.';
                        } else {
                            $dados['imagem_path'] = (new ImagemService())->salvar($_FILES['imagem'], 'eventos', 900, 900);
                            $dados['imagem_alt'] = $alt;

                            if ($atual !== null && !empty($atual['imagem_path'])) {
                                (new ImagemService())->remover($atual['imagem_path']);
                            }
                        }
                    } elseif ($atual !== null && isset($_POST['imagem_alt'])) {
                        $dados['imagem_alt'] = trim($_POST['imagem_alt']);
                    }
                } catch (\RuntimeException $e) {
                    $erro = $e->getMessage();
                }
            }

            if ($erro === null) {
                $repositorioDivulgacao->salvar($id, $dados);
                flashSucesso('Divulgação atualizada.');
                $this->redirecionar('eventos/divulgacao/' . (int) $id);
                return;
            }

            $atual = $dados + ['id' => $atual !== null ? $atual['id'] : null];
        }

        $this->renderizar('admin/eventos/divulgacao', [
            'evento' => $evento,
            'divulgacao' => $atual,
            'erro' => $erro,
        ], 'Divulgação na home: ' . $evento['nome'], ['tipo' => 'eventoDivulgacao', 'id' => (int) $id]);
    }

    public function inscritos($id)
    {
        $evento = $this->eventos->buscarPorId($id);

        if ($evento === null) {
            http_response_code(404);
            exit('Evento não encontrado.');
        }

        $this->renderizar('admin/eventos/inscritos', [
            'evento' => $evento,
            'campos' => $this->camposInscricao->listarPorEvento($id),
            'inscricoes' => $this->inscricoes->listarPorEvento($id),
        ], 'Inscritos: ' . $evento['nome'], ['tipo' => 'eventoInscritos', 'id' => (int) $id]);
    }

    /**
     * Fase 48 (correcao pos-teste de fumaca): sub-aba "Perfis" do Evento -
     * cadastro dos perfis da equipe de organizacao (Instrutor, Professor,
     * Palestrante, ...), usados por AtividadeAdminController::vincularFacilitador().
     * Nunca semeado direto no banco: o Admin cadastra pela tela, por evento
     * (mesmo espirito de EventoCampoInscricaoRepository).
     */
    public function perfis($id)
    {
        $evento = $this->eventos->buscarPorId($id);

        if ($evento === null) {
            http_response_code(404);
            exit('Evento não encontrado.');
        }

        $this->renderizar('admin/eventos/perfis', [
            'evento' => $evento,
            'perfis' => (new EventoPerfilOrganizacaoRepository())->listarPorEvento($id),
        ], 'Perfis: ' . $evento['nome'], ['tipo' => 'eventoPerfis', 'id' => (int) $id]);
    }

    public function perfilCriar($id)
    {
        $evento = $this->eventos->buscarPorId($id);

        if ($evento === null) {
            http_response_code(404);
            exit('Evento não encontrado.');
        }

        $nome = trim(isset($_POST['nome']) ? $_POST['nome'] : '');

        if ($nome === '') {
            flashErro('Informe o nome do perfil.');
            $this->redirecionar('eventos/perfis/' . (int) $id);
            return;
        }

        (new EventoPerfilOrganizacaoRepository())->criar($id, $nome);
        flashSucesso('Perfil cadastrado.');
        $this->redirecionar('eventos/perfis/' . (int) $id);
    }

    public function perfilRemover()
    {
        $perfilId = (int) (isset($_POST['id']) ? $_POST['id'] : 0);
        $perfil = (new EventoPerfilOrganizacaoRepository())->buscarPorId($perfilId);

        if ($perfil === null) {
            http_response_code(404);
            exit('Perfil não encontrado.');
        }

        try {
            (new EventoPerfilOrganizacaoRepository())->remover($perfilId);
            flashSucesso('Perfil removido.');
        } catch (\PDOException $e) {
            flashErro($e->getCode() === '23000'
                ? 'Não é possível remover: este perfil já está em uso por algum facilitador.'
                : 'Não foi possível remover o perfil.');
        }

        $this->redirecionar('eventos/perfis/' . (int) $perfil['evento_id']);
    }

    /**
     * Fase 48: exportacao EJURR da lista geral de inscritos do Evento -
     * "Categoria" usa a modalidade do check-in MAIS RECENTE da pessoa em
     * qualquer atividade (vazia se ela nunca confirmou presenca em
     * nenhuma). "Categoria Profissional" fica vazia (campo removido do
     * cadastro de participante na Fase 39, fica para fase futura).
     */
    public function exportarEjurr($id)
    {
        $evento = $this->eventos->buscarPorId($id);

        if ($evento === null) {
            http_response_code(404);
            exit('Evento não encontrado.');
        }

        $checkins = new EventoCheckinRepository();
        $linhas = [];

        foreach ($this->inscricoes->listarPorEvento($id) as $inscricao) {
            $modalidadeAcesso = $checkins->modalidadeMaisRecenteNoEvento($inscricao['id']);
            $modalidade = $modalidadeAcesso !== null ? ($modalidadeAcesso === 'online' ? 'Online' : 'Presencial') : null;
            // Fase 49B: documento/cargo/órgão de origem vêm de
            // usuarios_perfil (fonte única da pessoa), trazidos por JOIN
            // em EventoInscricaoRepository::listarPorEvento() - não mais
            // extraídos de respostas_json.
            $tipoDocumento = (string) $inscricao['perfil_tipo_documento'];

            $linhas[] = [
                'id' => (string) $inscricao['id'],
                'nome' => $inscricao['usuario_nome'],
                'email' => $inscricao['usuario_email'],
                'data_inscricao' => formatarDataHora($inscricao['inscrito_em']),
                'numero_inscricao' => (string) $inscricao['id'],
                'categoria' => $modalidade !== null ? 'Participante - ' . $modalidade : 'Participante',
                'inscricao' => $inscricao['homologado_em'] !== null ? 'Aprovado' : 'Pendente',
                'complementos' => '',
                'documento' => $tipoDocumento === 'CPF' ? \App\Validation\CpfValidador::formatar((string) $inscricao['perfil_documento']) : (string) $inscricao['perfil_documento'],
                'tipo_documento' => $tipoDocumento,
                'cargo' => (string) $inscricao['perfil_cargo'],
                'categoria_profissional' => '',
                'orgao_origem' => (string) $inscricao['perfil_orgao_origem'],
            ];
        }

        $servico = new EjurrExportService();
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $servico->nomeArquivo() . '"');
        $servico->escrever(fopen('php://output', 'w'), $linhas);
    }

    public function homologar()
    {
        $inscricaoId = (int) (isset($_POST['inscricao_id']) ? $_POST['inscricao_id'] : 0);
        $eventoId = (int) (isset($_POST['evento_id']) ? $_POST['evento_id'] : 0);

        $this->inscricoes->homologar($inscricaoId);
        flashSucesso('Inscrição homologada.');
        $this->redirecionar('eventos/inscritos/' . $eventoId);
    }

    /**
     * Fase 39 (correcao pos-teste): Admin/Suporte corrige ou preenche a
     * resposta de um campo especifico direto na tela de Inscritos.
     */
    public function atualizarResposta()
    {
        $inscricaoId = (int) (isset($_POST['inscricao_id']) ? $_POST['inscricao_id'] : 0);
        $campoId = (int) (isset($_POST['campo_id']) ? $_POST['campo_id'] : 0);
        $eventoId = (int) (isset($_POST['evento_id']) ? $_POST['evento_id'] : 0);
        $valor = trim(isset($_POST['valor']) ? $_POST['valor'] : '');

        $this->inscricoes->atualizarResposta($inscricaoId, $campoId, $valor);
        flashSucesso('Resposta atualizada.');
        $this->redirecionar('eventos/inscritos/' . $eventoId);
    }

    /**
     * Fase 45: sub-aba "Comunicacao" - aviso em massa aos inscritos,
     * processado em lotes pelo cron (database/processar_comunicacao_evento.php).
     * Historico traz tambem quem falhou em cada campanha, pra o Admin saber
     * quem desmarcar num reenvio manual.
     */
    public function comunicacao($id)
    {
        $evento = $this->eventos->buscarPorId($id);

        if ($evento === null) {
            http_response_code(404);
            exit('Evento não encontrado.');
        }

        $campanhas = $this->comunicacoes->listarPorEvento($id);
        $falhasPorCampanha = [];

        foreach ($campanhas as $campanha) {
            if ((int) $campanha['total_falhas'] > 0) {
                $falhasPorCampanha[$campanha['id']] = $this->comunicacoes->listarFalhasPorComunicacao($campanha['id']);
            }
        }

        $this->renderizar('admin/eventos/comunicacao', [
            'evento' => $evento,
            'inscricoes' => $this->inscricoes->listarPorEvento($id),
            'campanhas' => $campanhas,
            'falhasPorCampanha' => $falhasPorCampanha,
        ], 'Comunicação: ' . $evento['nome'], ['tipo' => 'eventoComunicacao', 'id' => (int) $id]);
    }

    /**
     * So' Administrador (nao Suporte) - disparo em massa pra centenas de
     * pessoas e' mais sensivel que homologar uma inscricao ou corrigir uma
     * resposta, mesmo criterio ja aplicado em novo()/editar() (POST).
     */
    public function comunicacaoEnviar()
    {
        RoleMiddleware::exigir(['administrador']);

        $eventoId = (int) (isset($_POST['evento_id']) ? $_POST['evento_id'] : 0);
        $evento = $this->eventos->buscarPorId($eventoId);

        if ($evento === null) {
            http_response_code(404);
            exit('Evento não encontrado.');
        }

        $assunto = trim(isset($_POST['assunto']) ? $_POST['assunto'] : '');
        $corpoHtml = isset($_POST['corpo_html']) ? sanitizarHtmlRico($_POST['corpo_html']) : '';
        $destinatariosPost = isset($_POST['destinatarios']) && is_array($_POST['destinatarios']) ? $_POST['destinatarios'] : [];

        if ($assunto === '' || $corpoHtml === '') {
            flashErro('Informe o assunto e o corpo da mensagem.');
            $this->redirecionar('eventos/comunicacao/' . $eventoId);
            return;
        }

        // Nunca confia cegamente na lista vinda do formulario - filtra so'
        // os ids que realmente pertencem a este evento (evita que um POST
        // manipulado inclua inscricao de outro evento).
        $inscricoesDoEvento = $this->inscricoes->listarPorEvento($eventoId);
        $inscricoesPorId = [];
        foreach ($inscricoesDoEvento as $inscricao) {
            $inscricoesPorId[(int) $inscricao['id']] = $inscricao;
        }
        $inscricaoIds = array_values(array_intersect(array_map('intval', $destinatariosPost), array_keys($inscricoesPorId)));

        if (empty($inscricaoIds)) {
            flashErro('Selecione ao menos um destinatário.');
            $this->redirecionar('eventos/comunicacao/' . $eventoId);
            return;
        }

        $comunicacaoId = $this->comunicacoes->criarCampanhaComDestinatarios([
            'evento_id' => $eventoId,
            'autor_usuario_id' => \App\Core\Auth::usuarioId(),
            'assunto' => $assunto,
            'corpo_html' => $corpoHtml,
        ], $inscricaoIds);

        $notificacoesPainel = new NotificacaoPainelRepository();

        foreach ($inscricaoIds as $inscricaoId) {
            $notificacoesPainel->criar(
                $inscricoesPorId[$inscricaoId]['usuario_id'],
                'evento_aviso_massa',
                $assunto,
                'Novo aviso sobre ' . $evento['nome'] . '.',
                ['url' => url('eventoApp/aviso/' . $comunicacaoId)]
            );
        }

        flashSucesso('Aviso registrado. Será enviado em lotes ao longo dos próximos minutos.');
        $this->redirecionar('eventos/comunicacao/' . $eventoId);
    }

    private function dadosDoFormulario()
    {
        return [
            'nome' => trim(isset($_POST['nome']) ? $_POST['nome'] : ''),
            'descricao' => $this->campoOuNulo('descricao'),
            'mensagem_confirmacao_inscricao' => isset($_POST['mensagem_confirmacao_inscricao']) ? sanitizarHtmlRico($_POST['mensagem_confirmacao_inscricao']) : null,
            'data_inicio' => trim(isset($_POST['data_inicio']) ? $_POST['data_inicio'] : ''),
            'data_fim' => trim(isset($_POST['data_fim']) ? $_POST['data_fim'] : ''),
            'status' => isset($_POST['status']) && $_POST['status'] === 'encerrado' ? 'encerrado' : 'ativo',
            'modo_credenciamento' => isset($_POST['modo_credenciamento']) && $_POST['modo_credenciamento'] === 'assistido' ? 'assistido' : 'automatico',
        ];
    }

    private function validar(array $dados)
    {
        if ($dados['nome'] === '') {
            return 'Informe o nome do evento.';
        }

        if ($dados['data_inicio'] === '' || $dados['data_fim'] === '') {
            return 'Informe as datas de início e fim.';
        }

        if ($dados['data_fim'] < $dados['data_inicio']) {
            return 'A data de fim não pode ser anterior à data de início.';
        }

        return null;
    }

    private function campoOuNulo($chave)
    {
        $valor = trim(isset($_POST[$chave]) ? $_POST[$chave] : '');

        return $valor !== '' ? $valor : null;
    }
}
