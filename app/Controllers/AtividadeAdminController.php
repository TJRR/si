<?php

namespace App\Controllers;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Core\Controller;
use App\Core\View;
use App\Middleware\RoleMiddleware;
use App\Repositories\EventoAtividadeFacilitadorRepository;
use App\Repositories\EventoAtividadeInscricaoRepository;
use App\Repositories\EventoAtividadeRepository;
use App\Repositories\EventoCheckinRepository;
use App\Repositories\EventoInscricaoRepository;
use App\Repositories\EventoPerfilOrganizacaoRepository;
use App\Repositories\SemanaInovacaoRepository;
use App\Repositories\UsuarioRepository;
use App\Services\EjurrExportService;
use App\Services\NotificacaoService;

/**
 * Fase 46: CRUD de Atividade (cursos/palestras/seminarios do Evento) -
 * primeiro filho de arvore do Evento (NavegacaoService::noAtividades()/
 * noAtividade()). Sem escopo de concurso (Evento e' sempre global) - mesmo
 * criterio simples de EventoAdminController, nao o exigirEmQualquerConcurso()
 * de EtapaAdminController (que so' existe por causa de concurso_id).
 */
class AtividadeAdminController extends Controller
{
    private $atividades;
    private $eventos;
    private $inscricoesAtividade;

    public function __construct()
    {
        RoleMiddleware::exigir(['administrador', 'suporte']);
        $this->atividades = new EventoAtividadeRepository();
        $this->eventos = new SemanaInovacaoRepository();
        $this->inscricoesAtividade = new EventoAtividadeInscricaoRepository();
    }

    public function index($eventoId)
    {
        $evento = $this->eventos->buscarPorId($eventoId);

        if ($evento === null) {
            http_response_code(404);
            exit('Evento não encontrado.');
        }

        $this->renderizar('admin/atividades/index', [
            'evento' => $evento,
            'atividades' => $this->atividades->listarPorEvento($eventoId),
        ], 'Atividades: ' . $evento['nome'], ['tipo' => 'atividades', 'id' => (int) $eventoId]);
    }

    public function novo($eventoId)
    {
        RoleMiddleware::exigir(['administrador']);
        $evento = $this->eventos->buscarPorId($eventoId);

        if ($evento === null) {
            http_response_code(404);
            exit('Evento não encontrado.');
        }

        $erro = null;
        $atividade = null;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $dados = $this->dadosDoFormulario();
            $erro = $this->validar($dados);

            if ($erro === null) {
                $id = $this->atividades->criar($eventoId, $dados);
                $this->redirecionar('atividades/editar/' . $id);
                return;
            }

            $atividade = $dados;
        }

        $this->renderizar('admin/atividades/form', [
            'erro' => $erro,
            'evento' => $evento,
            'atividade' => $atividade,
        ], 'Nova atividade', ['tipo' => 'atividades', 'id' => (int) $eventoId]);
    }

    public function editar($id)
    {
        $atividade = $this->atividades->buscarPorId($id);

        if ($atividade === null) {
            http_response_code(404);
            exit('Atividade não encontrada.');
        }

        $evento = $this->eventos->buscarPorId($atividade['evento_id']);
        $erro = null;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            RoleMiddleware::exigir(['administrador']);
            $dados = $this->dadosDoFormulario();
            $erro = $this->validar($dados);

            if ($erro === null) {
                $this->atividades->atualizar($id, $dados);
                $atividade = $this->atividades->buscarPorId($id);
            } else {
                $atividade = $dados + ['id' => $atividade['id'], 'codigo_atividade' => $atividade['codigo_atividade']];
            }
        }

        $this->renderizar('admin/atividades/form', [
            'erro' => $erro,
            'evento' => $evento,
            'atividade' => $atividade,
        ], 'Editar atividade', ['tipo' => 'atividade', 'id' => (int) $id]);
    }

    public function remover()
    {
        RoleMiddleware::exigir(['administrador']);
        $id = (int) (isset($_POST['id']) ? $_POST['id'] : 0);
        $atividade = $this->atividades->buscarPorId($id);

        if ($atividade === null) {
            http_response_code(404);
            exit('Atividade não encontrada.');
        }

        $eventoId = (int) $atividade['evento_id'];

        try {
            $this->atividades->remover($id);
            flashSucesso('Atividade removida.');
        } catch (\PDOException $e) {
            flashErro($e->getCode() === '23000'
                ? 'Não é possível remover: esta atividade já tem inscrições.'
                : 'Não foi possível remover a atividade.');
        }

        $this->redirecionar('atividades/index/' . $eventoId);
    }

    /**
     * Fase 47: modelo de impressao do codigo fixo da atividade (QR + texto),
     * para afixar no espaco fisico onde ela ocorre - mesmo mecanismo de
     * EventoAppController::cracha() (View::renderizarString(), pagina solta
     * sem layout.php).
     */
    public function codigo($id)
    {
        $atividade = $this->atividades->buscarPorId($id);

        if ($atividade === null) {
            http_response_code(404);
            exit('Atividade não encontrada.');
        }

        $evento = $this->eventos->buscarPorId($atividade['evento_id']);

        echo View::renderizarString('admin/atividades/codigo_impressao', [
            'evento' => $evento,
            'atividade' => $atividade,
        ]);
    }

    /**
     * Fase 47: sub-aba "Presencas" - quem confirmou presenca nesta
     * atividade, com a presenca efetiva calculada em tempo de leitura (ver
     * EventoCheckinRepository::presencaEfetiva()).
     */
    public function checkins($id)
    {
        $atividade = $this->atividades->buscarPorId($id);

        if ($atividade === null) {
            http_response_code(404);
            exit('Atividade não encontrada.');
        }

        $evento = $this->eventos->buscarPorId($atividade['evento_id']);
        $checkinRepo = new EventoCheckinRepository();
        $checkins = $checkinRepo->listarPorAtividade($id);

        foreach ($checkins as &$checkin) {
            $checkin['presenca_efetiva'] = $checkinRepo->presencaEfetiva($atividade, $checkin['checkin_em']);
        }
        unset($checkin);

        $this->renderizar('admin/atividades/checkins', [
            'evento' => $evento,
            'atividade' => $atividade,
            'checkins' => $checkins,
        ], 'Presenças: ' . $atividade['nome'], ['tipo' => 'atividadeCheckin', 'id' => (int) $id]);
    }

    public function inscritos($id)
    {
        $atividade = $this->atividades->buscarPorId($id);

        if ($atividade === null) {
            http_response_code(404);
            exit('Atividade não encontrada.');
        }

        $evento = $this->eventos->buscarPorId($atividade['evento_id']);
        $inscricoes = $this->inscricoesAtividade->listarPorAtividade($id);

        $confirmadas = array_values(array_filter($inscricoes, function ($inscricao) {
            return $inscricao['status'] === 'confirmada';
        }));
        $espera = array_values(array_filter($inscricoes, function ($inscricao) {
            return $inscricao['status'] === 'espera';
        }));

        $this->renderizar('admin/atividades/inscritos', [
            'evento' => $evento,
            'atividade' => $atividade,
            'confirmadas' => $confirmadas,
            'espera' => $espera,
        ], 'Inscritos: ' . $atividade['nome'], ['tipo' => 'atividadeInscritos', 'id' => (int) $id]);
    }

    /**
     * Fase 48: sub-aba "Facilitadores" - instrutor/professor/palestrante
     * desta atividade, sempre vinculado a um usuario ja existente (ver
     * vincularFacilitador()). So' os ativos (removido_em IS NULL).
     */
    public function facilitadores($id)
    {
        $atividade = $this->atividades->buscarPorId($id);

        if ($atividade === null) {
            http_response_code(404);
            exit('Atividade não encontrada.');
        }

        $evento = $this->eventos->buscarPorId($atividade['evento_id']);

        $this->renderizar('admin/atividades/facilitadores', [
            'evento' => $evento,
            'atividade' => $atividade,
            'facilitadores' => (new EventoAtividadeFacilitadorRepository())->listarPorAtividade($id),
            'perfis' => (new EventoPerfilOrganizacaoRepository())->listarPorEvento($evento['id']),
        ], 'Facilitadores: ' . $atividade['nome'], ['tipo' => 'atividadeFacilitador', 'id' => (int) $id]);
    }

    /**
     * Fase 48 (correcao pos-teste de fumaca): endpoint de busca em tempo
     * real usado pelo componente assets/js/busca-usuario.js na tela de
     * vincular facilitador - devolve so' id/nome/email, nunca dado sensivel.
     */
    public function buscarUsuarios()
    {
        header('Content-Type: application/json');
        $termo = trim(isset($_GET['q']) ? $_GET['q'] : '');

        if (strlen($termo) < 2) {
            echo json_encode([]);
            return;
        }

        echo json_encode((new UsuarioRepository())->buscarPorTermo($termo));
    }

    /**
     * Fase 48 (correcao pos-teste de fumaca): dados de perfil ja existentes
     * do usuario selecionado na busca (documento/cargo/etc. + foto de
     * perfil) - o JS usa isto para pre-preencher o formulario quando a
     * mesma pessoa ja foi cadastrada antes (nesta atividade ou em outra):
     * documento/cargo/categoria/orgao/minicurriculo/foto sao da PESSOA, nao
     * da designacao, entao nunca precisam ser redigitados.
     */
    public function perfilUsuario()
    {
        header('Content-Type: application/json');
        $usuarioId = (int) (isset($_GET['usuario_id']) ? $_GET['usuario_id'] : 0);
        $usuario = $usuarioId > 0 ? (new UsuarioRepository())->buscarPorId($usuarioId) : null;

        if ($usuario === null) {
            echo json_encode(null);
            return;
        }

        $perfil = (new \App\Repositories\UsuarioPerfilRepository())->buscarPorUsuarioId($usuarioId);

        echo json_encode([
            'documento' => $perfil['documento'] ?? '',
            'tipo_documento' => $perfil['tipo_documento'] ?? 'CPF',
            'cargo' => $perfil['cargo'] ?? '',
            'categoria_profissional' => $perfil['categoria_profissional'] ?? '',
            'orgao_origem' => $perfil['orgao_origem'] ?? '',
            'minicurriculo' => $perfil['minicurriculo'] ?? '',
            'foto_path' => $usuario['foto_path'],
        ]);
    }

    /**
     * Fase 48: busca so' usuario JA EXISTENTE por id (resolvido pela busca
     * em tempo real no formulario, ver buscarUsuarios()) - nunca cria conta
     * nova, diferente do fluxo de convite de UsuarioAdminController::convidar().
     * perfil_id precisa pertencer a um perfil cadastrado NESTE evento
     * (EventoPerfilOrganizacaoRepository, sub-aba "Perfis" do Evento) -
     * nunca uma lista fixa no codigo.
     *
     * Correcao pos-teste de fumaca (achado real do usuario): documento/
     * cargo/categoria/orgao/minicurriculo/foto sao atributos da PESSOA, nao
     * da designacao - salvos em usuarios_perfil (UsuarioPerfilRepository)
     * e usuarios.foto_path, nunca duplicados em evento_atividade_facilitadores.
     * O vinculo em si guarda so' (atividade_id, usuario_id, perfil_id).
     */
    public function vincularFacilitador($id)
    {
        $atividade = $this->atividades->buscarPorId($id);

        if ($atividade === null) {
            http_response_code(404);
            exit('Atividade não encontrada.');
        }

        $usuarioId = (int) (isset($_POST['usuario_id']) ? $_POST['usuario_id'] : 0);
        $usuario = $usuarioId > 0 ? (new UsuarioRepository())->buscarPorId($usuarioId) : null;

        if ($usuario === null) {
            flashErro('Selecione um usuário válido na busca antes de vincular.');
            $this->redirecionar('atividades/facilitadores/' . (int) $id);
            return;
        }

        $perfilId = (int) (isset($_POST['perfil_id']) ? $_POST['perfil_id'] : 0);
        $perfil = $perfilId > 0 ? (new EventoPerfilOrganizacaoRepository())->buscarPorId($perfilId) : null;

        if ($perfil === null || (int) $perfil['evento_id'] !== (int) $atividade['evento_id']) {
            flashErro('Selecione um perfil válido. Cadastre perfis em Eventos > Perfis, se ainda não houver nenhum.');
            $this->redirecionar('atividades/facilitadores/' . (int) $id);
            return;
        }

        $dadosPerfil = [
            'documento' => trim(isset($_POST['documento']) ? $_POST['documento'] : ''),
            'tipo_documento' => in_array(isset($_POST['tipo_documento']) ? $_POST['tipo_documento'] : null, \App\Repositories\UsuarioPerfilRepository::TIPOS_DOCUMENTO, true)
                ? $_POST['tipo_documento']
                : 'CPF',
            'cargo' => trim(isset($_POST['cargo']) ? $_POST['cargo'] : ''),
            'categoria_profissional' => in_array(isset($_POST['categoria']) ? $_POST['categoria'] : null, \App\Repositories\UsuarioPerfilRepository::CATEGORIAS_PROFISSIONAIS, true)
                ? $_POST['categoria']
                : '',
            'orgao_origem' => trim(isset($_POST['orgao_origem']) ? $_POST['orgao_origem'] : ''),
            'minicurriculo' => trim(isset($_POST['minicurriculo']) ? $_POST['minicurriculo'] : ''),
        ];

        if ($dadosPerfil['documento'] === '') {
            flashErro('Informe o documento do facilitador.');
            $this->redirecionar('atividades/facilitadores/' . (int) $id);
            return;
        }

        // Foto e' de perfil do proprio usuario (usuarios.foto_path), a
        // mesma que "Meu Perfil" usa - trocar aqui atualiza a pessoa, nao
        // so' esta designacao.
        if (!empty($_FILES['foto']) && $_FILES['foto']['error'] !== UPLOAD_ERR_NO_FILE) {
            if ($_FILES['foto']['error'] !== UPLOAD_ERR_OK) {
                flashErro('Falha ao enviar a foto.');
                $this->redirecionar('atividades/facilitadores/' . (int) $id);
                return;
            }

            try {
                $novoCaminho = (new \App\Services\ImagemService())->salvar($_FILES['foto'], 'usuarios', 400, 400);

                if (!empty($usuario['foto_path'])) {
                    (new \App\Services\ImagemService())->remover($usuario['foto_path']);
                }

                (new UsuarioRepository())->atualizarFoto($usuario['id'], $novoCaminho);
            } catch (\RuntimeException $e) {
                flashErro($e->getMessage());
                $this->redirecionar('atividades/facilitadores/' . (int) $id);
                return;
            }
        }

        (new \App\Repositories\UsuarioPerfilRepository())->salvar($usuario['id'], $dadosPerfil);

        try {
            (new EventoAtividadeFacilitadorRepository())->criar($id, $usuario['id'], $perfilId);
            flashSucesso('Facilitador vinculado.');
        } catch (\RuntimeException $e) {
            flashErro($e->getMessage());
        }

        $this->redirecionar('atividades/facilitadores/' . (int) $id);
    }

    public function removerFacilitador()
    {
        $facilitadorId = (int) (isset($_POST['id']) ? $_POST['id'] : 0);
        $facilitador = (new EventoAtividadeFacilitadorRepository())->buscarPorId($facilitadorId);

        if ($facilitador === null) {
            http_response_code(404);
            exit('Facilitador não encontrado.');
        }

        (new EventoAtividadeFacilitadorRepository())->remover($facilitadorId);
        flashSucesso('Facilitador removido.');
        $this->redirecionar('atividades/facilitadores/' . (int) $facilitador['atividade_id']);
    }

    /**
     * Fase 48: exportacao EJURR desta atividade - confirmados (nunca lista
     * de espera) + facilitadores (ativos e removidos, para preservar quem
     * ja conduziu a atividade mesmo se removido por engano e corrigido
     * depois).
     */
    public function exportarEjurr($id)
    {
        $atividade = $this->atividades->buscarPorId($id);

        if ($atividade === null) {
            http_response_code(404);
            exit('Atividade não encontrada.');
        }

        $evento = $this->eventos->buscarPorId($atividade['evento_id']);
        $inscricoesRepo = new EventoInscricaoRepository();
        $checkins = new EventoCheckinRepository();

        $confirmadas = array_filter(
            $this->inscricoesAtividade->listarPorAtividade($id),
            function ($inscricao) {
                return $inscricao['status'] === 'confirmada';
            }
        );

        $linhas = [];

        foreach ($confirmadas as $inscricaoAtividade) {
            $inscricaoEvento = $inscricoesRepo->buscarPorId($inscricaoAtividade['evento_inscricao_id']);

            if ($inscricaoEvento === null) {
                continue;
            }

            $modalidade = $this->modalidadeParaLinhaDeAtividade($atividade, $checkins, $inscricaoEvento['id']);
            // Fase 49B: documento/cargo/órgão de origem vêm de
            // usuarios_perfil (fonte única da pessoa), trazidos por JOIN
            // em EventoInscricaoRepository::buscarPorId() - não mais
            // extraídos de respostas_json.
            $tipoDocumento = (string) $inscricaoEvento['perfil_tipo_documento'];

            $linhas[] = [
                'id' => (string) $inscricaoEvento['id'],
                'nome' => $inscricaoAtividade['usuario_nome'],
                'email' => $inscricaoAtividade['usuario_email'],
                'data_inscricao' => formatarDataHora($inscricaoEvento['inscrito_em']),
                'numero_inscricao' => (string) $inscricaoEvento['id'],
                'categoria' => $modalidade !== null ? 'Participante - ' . $modalidade : 'Participante',
                'inscricao' => $inscricaoEvento['homologado_em'] !== null ? 'Aprovado' : 'Pendente',
                'complementos' => '',
                'documento' => $tipoDocumento === 'CPF' ? \App\Validation\CpfValidador::formatar((string) $inscricaoEvento['perfil_documento']) : (string) $inscricaoEvento['perfil_documento'],
                'tipo_documento' => $tipoDocumento,
                'cargo' => (string) $inscricaoEvento['perfil_cargo'],
                'categoria_profissional' => '',
                'orgao_origem' => (string) $inscricaoEvento['perfil_orgao_origem'],
            ];
        }

        foreach ((new EventoAtividadeFacilitadorRepository())->listarTodosPorAtividade($id) as $facilitador) {
            $linhas[] = [
                'id' => (string) $facilitador['id'],
                'nome' => $facilitador['usuario_nome'],
                'email' => $facilitador['usuario_email'],
                'data_inscricao' => formatarDataHora($facilitador['designado_em']),
                'numero_inscricao' => (string) $facilitador['id'],
                'categoria' => $facilitador['perfil_nome'] . ' - ' . $this->rotuloModalidade($atividade['modalidade']),
                'inscricao' => 'Aprovado',
                'complementos' => (string) $facilitador['minicurriculo'],
                'documento' => $facilitador['tipo_documento'] === 'CPF' ? \App\Validation\CpfValidador::formatar((string) $facilitador['documento']) : (string) $facilitador['documento'],
                'tipo_documento' => (string) $facilitador['tipo_documento'],
                'cargo' => (string) $facilitador['cargo'],
                'categoria_profissional' => (string) $facilitador['categoria_profissional'],
                'orgao_origem' => (string) $facilitador['orgao_origem'],
            ];
        }

        $this->enviarCsv($linhas);
    }

    /**
     * Confirma manualmente alguem da lista de espera - a ocupacao ja' fica
     * visivel na tela inscritos.php antes deste clique (ver plano da Fase
     * 46), entao a decisao consciente do Admin de passar da capacidade
     * nominal, se for o caso, e' dele. Notifica o participante (e-mail +
     * painel), mesmo par de canais usado nas demais notificacoes desta fase.
     */
    public function confirmarEspera()
    {
        $id = (int) (isset($_POST['id']) ? $_POST['id'] : 0);
        $registro = $this->inscricoesAtividade->buscarPorId($id);

        if ($registro === null) {
            http_response_code(404);
            exit('Inscrição não encontrada.');
        }

        $this->inscricoesAtividade->confirmarDaEspera($id);

        $evento = $this->eventos->buscarPorId($registro['evento_id']);
        (new NotificacaoService())->avisoIndividualEvento(
            $registro['usuario_email'],
            $evento,
            'Vaga confirmada: ' . $registro['atividade_nome'],
            '<p>Olá, ' . htmlspecialchars($registro['usuario_nome'], ENT_QUOTES, 'UTF-8') . '!</p>'
            . '<p>Sua vaga na atividade "' . htmlspecialchars($registro['atividade_nome'], ENT_QUOTES, 'UTF-8') . '" foi confirmada.</p>'
        );
        (new \App\Repositories\NotificacaoPainelRepository())->criar(
            (int) $registro['usuario_id'],
            'atividade_vaga_confirmada',
            'Vaga confirmada',
            'Sua vaga em "' . $registro['atividade_nome'] . '" foi confirmada.',
            ['url' => url('eventoApp/atividades/' . (int) $registro['evento_id'])]
        );

        flashSucesso('Inscrição confirmada.');
        $this->redirecionar('atividades/inscritos/' . (int) $registro['atividade_id']);
    }

    private function dadosDoFormulario()
    {
        return [
            'nome' => trim(isset($_POST['nome']) ? $_POST['nome'] : ''),
            'descricao_html' => isset($_POST['descricao_html']) ? sanitizarHtmlRico($_POST['descricao_html']) : '',
            'local' => trim(isset($_POST['local']) ? $_POST['local'] : ''),
            'modalidade' => in_array(isset($_POST['modalidade']) ? $_POST['modalidade'] : null, ['presencial', 'online', 'hibrido'], true)
                ? $_POST['modalidade']
                : 'presencial',
            'data_inicio' => isset($_POST['data_inicio']) ? trim($_POST['data_inicio']) : '',
            'data_fim' => isset($_POST['data_fim']) ? trim($_POST['data_fim']) : '',
            'exige_inscricao' => isset($_POST['exige_inscricao']),
            'emite_certificado' => isset($_POST['emite_certificado']),
            'vagas' => isset($_POST['vagas']) ? trim($_POST['vagas']) : '',
            'permite_lista_espera' => isset($_POST['permite_lista_espera']),
            'tolerancia_presenca_efetiva' => isset($_POST['tolerancia_presenca_efetiva']) ? trim($_POST['tolerancia_presenca_efetiva']) : '100',
            'antecedencia_abertura_presenca' => isset($_POST['antecedencia_abertura_presenca']) ? trim($_POST['antecedencia_abertura_presenca']) : '60',
        ];
    }

    private function validar(array $dados)
    {
        if ($dados['nome'] === '') {
            return 'Informe o nome da atividade.';
        }

        if ($dados['data_inicio'] === '' || $dados['data_fim'] === '') {
            return 'Informe o período (início e fim).';
        }

        if ($dados['data_fim'] < $dados['data_inicio']) {
            return 'A data de fim não pode ser anterior à data de início.';
        }

        if ($dados['vagas'] !== '' && (!ctype_digit($dados['vagas']) || (int) $dados['vagas'] < 1)) {
            return 'Vagas deve ser um número inteiro positivo, ou em branco para ilimitado.';
        }

        if (!in_array($dados['tolerancia_presenca_efetiva'], ['0', '25', '50', '75', '100'], true)) {
            return 'Tolerância de presença efetiva inválida.';
        }

        if (!in_array($dados['antecedencia_abertura_presenca'], ['15', '30', '60'], true)) {
            return 'Antecedência para abertura da confirmação de presença inválida.';
        }

        return null;
    }

    /**
     * Fase 48: modalidade que vai na coluna "Categoria" da exportacao EJURR
     * desta atividade - fixa quando a atividade nao e' hibrida (so' ha' um
     * caminho possivel); quando e' hibrida, depende do check-in real desta
     * pessoa NESTA atividade (null se ela nunca confirmou presenca aqui).
     */
    private function modalidadeParaLinhaDeAtividade(array $atividade, EventoCheckinRepository $checkins, $inscricaoEventoId)
    {
        if ($atividade['modalidade'] !== 'hibrido') {
            return $this->rotuloModalidade($atividade['modalidade']);
        }

        $checkin = $checkins->buscarPorAtividadeEInscricao($atividade['id'], $inscricaoEventoId);

        return $checkin !== null ? $this->rotuloModalidade($checkin['modalidade_acesso']) : null;
    }

    private function rotuloModalidade($modalidade)
    {
        return $modalidade === 'online' ? 'Online' : 'Presencial';
    }

    private function enviarCsv(array $linhas)
    {
        $servico = new EjurrExportService();
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $servico->nomeArquivo() . '"');
        $servico->escrever(fopen('php://output', 'w'), $linhas);
    }
}
