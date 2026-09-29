<?php

namespace App\Controllers;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Core\Auditoria;
use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Repositories\EventoInscricaoRepository;
use App\Repositories\SemanaInovacaoRepository;
use App\Repositories\TemaVisualRepository;
use App\Repositories\TokenSenhaRepository;
use App\Repositories\TrabalhoAutorRepository;
use App\Repositories\UsuarioPerfilRepository;
use App\Repositories\UsuarioRepository;
use App\Services\ImagemService;
use App\Validation\CpfValidador;

/**
 * Fase 55: "Meu Perfil" DENTRO do aplicativo do Evento, com tres abas -
 * meus dados e o que compartilho, Aparencia (pendencia 18) e Alterar senha.
 * Nasce porque as Conexoes precisam de um lugar em que o participante
 * escolha o que mostra a quem se conectar com ele, e o aplicativo nao tinha
 * nenhum caminho ate' a tela "Meu perfil" do painel administrativo.
 *
 * CONTROLADOR PROPRIO, e nao metodos novos em MeuPerfilController, por
 * decisao do dono: aquela tela esta em uso real por todos os perfis,
 * inclusive no caminho de troca de senha, e o precedente do projeto (Fase
 * 54, EstandeRepresentanteConviteService) e' COPIAR a logica de que se
 * precisa em arquivo novo do Evento, nunca reescrever por dentro codigo
 * ativo do Concurso. MeuPerfilController e app/Views/meuPerfil/* ficam
 * intocados. A duplicacao e' proposital e esta registrada na pendencia 25,
 * para ser unificada depois do fim do concurso.
 *
 * Conferencia de posse igual a de EventoAppController: evento existe e a
 * pessoa tem inscricao NELE, com o mesmo redirecionamento neutro nos dois
 * casos de falha.
 */
class EventoAppPerfilController extends Controller
{
    private $usuarios;
    private $perfis;
    private $imagens;

    public function __construct()
    {
        if (!Auth::autenticado()) {
            header('Location: ' . url('auth/login'));
            exit;
        }

        $this->usuarios = new UsuarioRepository();
        $this->perfis = new UsuarioPerfilRepository();
        $this->imagens = new ImagemService();
    }

    public function index($id)
    {
        $contexto = $this->contextoOu404($id);

        if ($contexto === null) {
            return;
        }

        $usuario = $this->usuarios->buscarPorId(Auth::usuarioId());
        $erro = null;
        $valores = null;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $valores = $this->valoresEnviados();
            $erro = $this->salvarDados($usuario, $valores);

            if ($erro === null) {
                $this->redirecionar('eventoAppPerfil/index/' . (int) $contexto['evento']['id']);
                return;
            }

            // Correcao pos-validacao do dono: o erro sai na faixa de mensagem
            // do aplicativo (eventoApp/_app_bar.php, Fase 48B), com as cores e
            // o icone das demais mensagens, e nao mais num paragrafo solto no
            // meio da tela, que passava despercebido. Continua re-renderizando
            // a mesma tela, e nao redirecionando, para nao perder o que a
            // pessoa acabou de digitar.
            flashErro($erro);
            $usuario = $this->usuarios->buscarPorId(Auth::usuarioId());
        }

        $this->renderizar('eventoApp/perfil', [
            'evento' => $contexto['evento'],
            'usuario' => $usuario,
            'perfil' => $this->perfis->buscarPorUsuarioId($usuario['id']),
            'valores' => $valores,
            'temSenha' => $usuario['senha_hash'] !== null,
            'abaAtiva' => 'dados',
        ], 'Meu Perfil: ' . $contexto['evento']['nome']);
    }

    /**
     * Fase 55 (pendencia 18): selecao de tema visual sem sair do aplicativo.
     * Mesma regra da tela administrativa: a pessoa escolhe entre os temas
     * PUBLICADOS, e o tema vale em todo o sistema (ver
     * TemaVisualRepository::resolverAtivo()).
     */
    public function aparencia($id)
    {
        $contexto = $this->contextoOu404($id);

        if ($contexto === null) {
            return;
        }

        $temasVisuais = new TemaVisualRepository();
        $disponiveis = $temasVisuais->listarPublicados();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $temaId = (int) (isset($_POST['tema_id']) ? $_POST['tema_id'] : 0);

            if ($temaId > 0 && !$this->temaEstaDisponivel($temaId, $disponiveis)) {
                flashErro('Tema não encontrado ou não disponível.');
                $this->redirecionar('eventoAppPerfil/aparencia/' . (int) $contexto['evento']['id']);
                return;
            }

            $this->usuarios->definirTemaVisual(Auth::usuarioId(), $temaId > 0 ? $temaId : null);

            flashSucesso('Tema atualizado.');
            $this->redirecionar('eventoAppPerfil/aparencia/' . (int) $contexto['evento']['id']);
            return;
        }

        $usuario = $this->usuarios->buscarPorId(Auth::usuarioId());

        $this->renderizar('eventoApp/perfil_aparencia', [
            'evento' => $contexto['evento'],
            'temasDisponiveis' => $disponiveis,
            'temaAtualId' => $usuario['tema_visual_id'],
            'temSenha' => $usuario['senha_hash'] !== null,
            'abaAtiva' => 'aparencia',
        ], 'Aparência: ' . $contexto['evento']['nome']);
    }

    /**
     * Fase 55: troca de senha dentro do aplicativo. So' existe para conta
     * que JA' tem senha - quem entra so' pela conta Google nao tem senha
     * atual a conferir, e o caminho dela continua sendo o de definir a
     * primeira senha por endereco de uso unico.
     */
    public function senha($id)
    {
        $contexto = $this->contextoOu404($id);

        if ($contexto === null) {
            return;
        }

        $eventoId = (int) $contexto['evento']['id'];
        $usuario = $this->usuarios->buscarPorId(Auth::usuarioId());

        if ($usuario === null || $usuario['senha_hash'] === null) {
            flashErro('Esta conta não usa senha para entrar.');
            $this->redirecionar('eventoAppPerfil/index/' . $eventoId);
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->renderizar('eventoApp/perfil_senha', [
                'evento' => $contexto['evento'],
                'temSenha' => true,
                'abaAtiva' => 'senha',
            ], 'Alterar senha: ' . $contexto['evento']['nome']);
            return;
        }

        $atual = isset($_POST['senha_atual']) ? $_POST['senha_atual'] : '';
        $nova = isset($_POST['senha_nova']) ? $_POST['senha_nova'] : '';
        $confirmacao = isset($_POST['confirmacao']) ? $_POST['confirmacao'] : '';

        if (!password_verify($atual, $usuario['senha_hash'])) {
            flashErro('Senha atual incorreta.');
        } elseif (strlen($nova) < 8) {
            flashErro('A nova senha deve ter ao menos 8 caracteres.');
        } elseif ($nova !== $confirmacao) {
            flashErro('As senhas não conferem.');
        } elseif ($nova === $atual) {
            flashErro('A nova senha deve ser diferente da atual.');
        } else {
            $this->usuarios->definirSenha($usuario['id'], password_hash($nova, PASSWORD_DEFAULT));

            // Endereco de "definir senha" ainda pendente (convite ou
            // recuperacao) deixa de valer: quem acabou de provar a senha
            // atual nao pode ficar com uma segunda porta aberta.
            (new TokenSenhaRepository())->invalidarPendentes($usuario['id'], 'definir');

            // Sessao nova depois de trocar credencial, mesmo cuidado de
            // Auth::login().
            session_regenerate_id(true);

            flashSucesso('Senha alterada.');
        }

        $this->redirecionar('eventoAppPerfil/senha/' . $eventoId);
    }

    /**
     * Evento existe e a pessoa tem inscricao nele. Devolve null e ja
     * redireciona quando qualquer um dos dois falha, com o mesmo destino
     * neutro usado em todo o aplicativo.
     */
    private function contextoOu404($id)
    {
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

    private function temaEstaDisponivel($temaId, array $disponiveis)
    {
        foreach ($disponiveis as $tema) {
            if ((int) $tema['id'] === (int) $temaId) {
                return true;
            }
        }

        return false;
    }

    /**
     * Tudo o que o formulario manda, ja limpo, para gravar e para repopular
     * a tela quando houver erro.
     */
    private function valoresEnviados()
    {
        $tipoDocumento = in_array(isset($_POST['tipo_documento']) ? $_POST['tipo_documento'] : null, UsuarioPerfilRepository::TIPOS_DOCUMENTO, true)
            ? $_POST['tipo_documento']
            : 'CPF';

        $categoria = in_array(isset($_POST['categoria_profissional']) ? $_POST['categoria_profissional'] : null, UsuarioPerfilRepository::CATEGORIAS_PROFISSIONAIS, true)
            ? $_POST['categoria_profissional']
            : '';

        $redes = [];

        foreach (UsuarioPerfilRepository::REDES_SUPORTADAS as $rede) {
            $campo = 'rede_' . $rede;
            $redes[$rede] = trim(isset($_POST[$campo]) ? $_POST[$campo] : '');
        }

        $valores = [
            'nome' => trim(isset($_POST['nome']) ? $_POST['nome'] : ''),
            'tipo_documento' => $tipoDocumento,
            'documento' => trim(isset($_POST['documento']) ? $_POST['documento'] : ''),
            'cargo' => trim(isset($_POST['cargo']) ? $_POST['cargo'] : ''),
            'categoria_profissional' => $categoria,
            'orgao_origem' => trim(isset($_POST['orgao_origem']) ? $_POST['orgao_origem'] : ''),
            'minicurriculo' => trim(isset($_POST['minicurriculo']) ? $_POST['minicurriculo'] : ''),
            'telefone' => trim(isset($_POST['telefone']) ? $_POST['telefone'] : ''),
            'telefone_whatsapp' => !empty($_POST['telefone_whatsapp']) ? 1 : 0,
            'redes_sociais' => $redes,
        ];

        foreach (UsuarioPerfilRepository::MARCAS_VISIBILIDADE as $marca) {
            $valores[$marca] = !empty($_POST[$marca]) ? 1 : 0;
        }

        return $valores;
    }

    /**
     * Grava nome, foto e os dados da pessoa. Devolve a mensagem de erro ou
     * null quando deu tudo certo.
     *
     * A conferencia do CPF que chega aos trabalhos e' a mesma regra que a
     * tela administrativa aplica (Fase 54, decisao do dono): CPF valido e
     * que nao seja de outra pessoa num trabalho do mesmo evento, conferido
     * ANTES de gravar qualquer coisa.
     */
    private function salvarDados(array $usuario, array $valores)
    {
        if ($valores['nome'] === '') {
            return 'Informe o nome.';
        }

        $telefoneFormatado = null;

        if ($valores['telefone'] !== '') {
            $telefoneFormatado = formatarTelefoneBr($valores['telefone']);

            if ($telefoneFormatado === null) {
                return 'Telefone inválido. Informe o número com o código de área, por exemplo (95) 98765-4321.';
            }
        }

        // Correcao pedida pelo dono: a pessoa informa o nome de usuario e o
        // sistema monta o endereco; quem colar o endereco inteiro tambem e'
        // aceito. So' recusa o que nao da' para interpretar com seguranca,
        // como endereco de outro site colado no campo da rede errada.
        $redesNormalizadas = [];

        foreach ($valores['redes_sociais'] as $rede => $informado) {
            $endereco = UsuarioPerfilRepository::normalizarEnderecoRede($rede, $informado);

            if ($endereco === null) {
                $rotulos = UsuarioPerfilRepository::REDES_ROTULOS;
                $rotulo = isset($rotulos[$rede]) ? $rotulos[$rede] : $rede;

                return 'Não foi possível entender o que você informou em ' . $rotulo
                    . '. Informe o seu nome de usuário ou o endereço completo do seu perfil no ' . $rotulo . '.';
            }

            $redesNormalizadas[$rede] = $endereco;
        }

        $autores = new TrabalhoAutorRepository();
        $cpfParaTrabalhos = null;

        if ($valores['tipo_documento'] === 'CPF' && $valores['documento'] !== ''
            && $autores->possuiTrabalhoEmQualquerEvento($usuario['id'])) {
            if (!CpfValidador::valido($valores['documento'])) {
                return 'CPF inválido. Confira o número: ele também é usado nos trabalhos que você enviou.';
            }

            $cpfParaTrabalhos = CpfValidador::apenasDigitos($valores['documento']);
            $eventosEmConflito = $autores->eventosComCpfDeOutraPessoa($usuario['id'], $cpfParaTrabalhos);

            if (!empty($eventosEmConflito)) {
                return 'Este CPF já consta como de outra pessoa num trabalho de: ' . implode(', ', $eventosEmConflito)
                    . '. Confira o número digitado.';
            }
        }

        $this->usuarios->atualizarNome($usuario['id'], $valores['nome']);
        $_SESSION['usuario_nome'] = $valores['nome'];

        if (!empty($_FILES['foto']) && $_FILES['foto']['error'] !== UPLOAD_ERR_NO_FILE) {
            if ($_FILES['foto']['error'] !== UPLOAD_ERR_OK) {
                return 'Falha ao enviar a foto.';
            }

            try {
                $novoCaminho = $this->imagens->salvar($_FILES['foto'], 'usuarios', 400, 400);

                if (!empty($usuario['foto_path'])) {
                    $this->imagens->remover($usuario['foto_path']);
                }

                $this->usuarios->atualizarFoto($usuario['id'], $novoCaminho);
            } catch (\RuntimeException $e) {
                return $e->getMessage();
            }
        }

        $dadosPerfil = [
            'documento' => $valores['documento'],
            'tipo_documento' => $valores['tipo_documento'],
            'cargo' => $valores['cargo'],
            'categoria_profissional' => $valores['categoria_profissional'],
            'orgao_origem' => $valores['orgao_origem'],
            'minicurriculo' => $valores['minicurriculo'],
            'telefone' => $telefoneFormatado !== null ? $telefoneFormatado : '',
            'telefone_whatsapp' => $valores['telefone_whatsapp'],
            'redes_sociais' => $redesNormalizadas,
        ];

        foreach (UsuarioPerfilRepository::MARCAS_VISIBILIDADE as $marca) {
            $dadosPerfil[$marca] = $valores[$marca];
        }

        $trabalhosCorrigidos = 0;
        $pdo = Database::conexao();
        $pdo->beginTransaction();

        try {
            $this->perfis->salvar($usuario['id'], $dadosPerfil);

            if ($cpfParaTrabalhos !== null) {
                $trabalhosCorrigidos = $autores->atualizarCpfDoUsuarioNaTransacaoAtual($usuario['id'], $cpfParaTrabalhos);
            }

            $pdo->commit();
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            throw $e;
        }

        if ($trabalhosCorrigidos > 0) {
            Auditoria::registrar('corrigir_cpf_nos_trabalhos', 'trabalho_autores', $usuario['id'], null, ['trabalhos_corrigidos' => $trabalhosCorrigidos]);
        }

        flashSucesso($trabalhosCorrigidos > 0
            ? 'Perfil atualizado. O CPF também foi corrigido nos trabalhos que você enviou.'
            : 'Perfil atualizado.');

        return null;
    }
}
