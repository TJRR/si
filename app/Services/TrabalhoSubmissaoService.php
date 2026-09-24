<?php

namespace App\Services;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Core\Database;
use App\Repositories\EventoCampoInscricaoRepository;
use App\Repositories\EventoInscricaoRepository;
use App\Repositories\EventoTrabalhoTermoRepository;
use App\Repositories\PerfilRepository;
use App\Repositories\TrabalhoAutorRepository;
use App\Repositories\TrabalhoAvaliadorRepository;
use App\Repositories\TrabalhoConfigRepository;
use App\Repositories\TrabalhoRepository;
use App\Repositories\SemanaInovacaoRepository;
use App\Repositories\TokenSenhaRepository;
use App\Repositories\TrabalhoTermoAceiteRepository;
use App\Repositories\UsuarioPerfilRepository;
use App\Repositories\UsuarioRepository;
use App\Validation\CpfValidador;

/**
 * Fase 49: submissao de um Trabalho. Toda regra vem de
 * evento_trabalhos_config (metodos aceitos, quantidade maxima de
 * autores, exige telefone, deduplicacao de pessoa), nunca fixa aqui.
 *
 * Protecao de concorrencia (achado corrigido na revisao desta fase): a
 * checagem de CPF duplicado roda dentro de uma transacao que trava a
 * linha de configuracao do evento (FOR UPDATE), serializando submissoes
 * concorrentes do mesmo evento - mesmo mecanismo ja usado em
 * EventoAtividadeInscricaoRepository::inscrever() para vagas de
 * atividade, adaptado aqui para travar o recurso pai em vez de uma
 * contagem.
 */
class TrabalhoSubmissaoService
{
    private $config;
    private $trabalhos;
    private $autores;
    private $perfis;
    private $avaliadores;
    private $usuarioPerfil;
    private $termos;
    private $aceites;
    private $usuarios;
    private $tokens;
    private $inscricoes;
    private $camposInscricao;
    private $eventos;
    private $ultimoResumo = null;

    public function __construct()
    {
        $this->config = new TrabalhoConfigRepository();
        $this->trabalhos = new TrabalhoRepository();
        $this->autores = new TrabalhoAutorRepository();
        $this->perfis = new PerfilRepository();
        $this->avaliadores = new TrabalhoAvaliadorRepository();
        $this->usuarioPerfil = new UsuarioPerfilRepository();
        $this->termos = new EventoTrabalhoTermoRepository();
        $this->aceites = new TrabalhoTermoAceiteRepository();
        $this->usuarios = new UsuarioRepository();
        $this->tokens = new TokenSenhaRepository();
        $this->inscricoes = new EventoInscricaoRepository();
        $this->camposInscricao = new EventoCampoInscricaoRepository();
        $this->eventos = new SemanaInovacaoRepository();
    }

    /**
     * Reabertura da Fase 51: o que aconteceu na ultima chamada de submeter()
     * (quem foi inscrito, quem recebeu e-mail), para o controller montar UM
     * unico aviso na tela. Nulo antes da primeira submissao.
     */
    public function resumoUltimaSubmissao()
    {
        return $this->ultimoResumo;
    }

    /**
     * $dadosAutorPrincipal: nome, cpf, email, cargo, orgao_origem (cargo/
     * orgao_origem sincronizam com usuarios_perfil ao gravar - Fase 49B).
     * $dadosTrabalho: eixo_tematico_id, natureza_id, titulo, telefone_contato,
     * metodo_submissao, conteudo_html (metodo formulario), link_avaliacao/
     * link_publicacao (metodo link_externo).
     * $coautores: lista de ['nome','cpf','email','cargo','orgao_origem'].
     * $arquivosEnviados: $_FILES['arquivo_avaliacao']/['arquivo_publicacao']
     * (metodos de documento).
     * $termosAceitos (Fase 51): ids dos termos de aceite marcados no
     * formulario. Todo termo ativo e obrigatorio precisa estar aqui, e o
     * texto aceito e' gravado congelado em trabalho_termos_aceitos.
     */
    public function submeter($eventoId, $usuarioId, array $dadosAutorPrincipal, array $dadosTrabalho, array $coautores, array $arquivosEnviados = [], array $termosAceitos = [])
    {
        $config = $this->config->buscarPorEvento($eventoId);

        if ($config === null) {
            throw new TrabalhoSubmissaoException('Este evento ainda não está com a submissão de Trabalhos configurada.');
        }

        $this->validarPrazo($config);
        $this->validarMetodo($config, $dadosTrabalho);

        if (trim((string) $dadosTrabalho['titulo']) === '') {
            throw new TrabalhoSubmissaoException('Informe o título do trabalho.', 'titulo');
        }

        if (trim((string) $dadosAutorPrincipal['nome']) === '') {
            throw new TrabalhoSubmissaoException('Informe o nome completo do autor principal.', 'autor_nome');
        }

        if ((int) $config['exige_telefone_contato'] === 1 && empty($dadosTrabalho['telefone_contato'])) {
            throw new TrabalhoSubmissaoException('Informe um telefone para contato.', 'telefone_contato');
        }

        // Reabertura da Fase 51: telefone com DDD, 10 (fixo) ou 11 digitos
        // (celular), gravado sempre no mesmo formato de exibicao.
        if (!empty($dadosTrabalho['telefone_contato'])) {
            $telefoneFormatado = formatarTelefoneBr($dadosTrabalho['telefone_contato']);

            if ($telefoneFormatado === null) {
                throw new TrabalhoSubmissaoException('Informe o telefone com DDD, no formato (00) 0000-0000 ou (00) 00000-0000.', 'telefone_contato');
            }

            $dadosTrabalho['telefone_contato'] = $telefoneFormatado;
        }

        $totalAutores = 1 + count($coautores);

        if ($totalAutores > (int) $config['quantidade_maxima_autores']) {
            throw new TrabalhoSubmissaoException('Quantidade de autores acima do limite permitido para este evento (' . (int) $config['quantidade_maxima_autores'] . ').', 'coautores');
        }

        if (!CpfValidador::valido($dadosAutorPrincipal['cpf'])) {
            throw new TrabalhoSubmissaoException('CPF do autor principal inválido.', 'autor_cpf');
        }

        if (!filter_var($dadosAutorPrincipal['email'], FILTER_VALIDATE_EMAIL)) {
            throw new TrabalhoSubmissaoException('Informe um e-mail válido para o autor principal.', 'autor_email');
        }

        $emailPrincipal = mb_strtolower(trim($dadosAutorPrincipal['email']));

        foreach ($coautores as $posicao => $coautor) {
            $indiceCoautor = isset($coautor['indice']) ? $coautor['indice'] : $posicao;

            if (trim((string) $coautor['nome']) === '') {
                throw new TrabalhoSubmissaoException('Informe o nome completo do coautor.', 'coautor_nome', $indiceCoautor);
            }

            if (!CpfValidador::valido($coautor['cpf'])) {
                throw new TrabalhoSubmissaoException('CPF do coautor inválido.', 'coautor_cpf', $indiceCoautor);
            }

            if (!filter_var($coautor['email'], FILTER_VALIDATE_EMAIL)) {
                throw new TrabalhoSubmissaoException('Informe um e-mail válido para o coautor.', 'coautor_email', $indiceCoautor);
            }

            if (mb_strtolower(trim($coautor['email'])) === $emailPrincipal) {
                throw new TrabalhoSubmissaoException('O e-mail do coautor não pode ser igual ao do autor principal.', 'coautor_email', $indiceCoautor);
            }
        }

        // Fase 49B, achado do teste de fumaça (item 6.b): exclusão mútua
        // autor/avaliador dentro do mesmo evento, na outra direção - quem
        // já é avaliador avulso ATIVO deste evento não pode submeter um
        // trabalho nele. Checa autor principal e cada coautor, por e-mail.
        if ($this->avaliadores->emailJaEhAvaliadorAtivo($eventoId, $dadosAutorPrincipal['email'])) {
            throw new TrabalhoSubmissaoException('Este e-mail já está cadastrado como avaliador de Trabalhos deste evento e não pode submeter um trabalho.', 'autor_email');
        }

        foreach ($coautores as $posicao => $coautor) {
            $indiceCoautor = isset($coautor['indice']) ? $coautor['indice'] : $posicao;

            if ($this->avaliadores->emailJaEhAvaliadorAtivo($eventoId, $coautor['email'])) {
                throw new TrabalhoSubmissaoException('O e-mail do coautor já está cadastrado como avaliador de Trabalhos deste evento.', 'coautor_email', $indiceCoautor);
            }
        }

        $termosParaRegistrar = $this->validarTermos($eventoId, $termosAceitos);

        $conteudo = $this->validarConteudo($config, $dadosTrabalho, $arquivosEnviados);

        // Reabertura da Fase 51 (achado da equipe de Teste Cego): quando o
        // evento liga "inscrever autores ao submeter", o autor principal e
        // cada coautor entram inscritos no evento junto com o trabalho.
        $evento = $this->eventos->buscarPorId($eventoId);
        $inscreverAutores = $evento !== null
            && isset($config['inscrever_autores_ao_submeter'])
            && (int) $config['inscrever_autores_ao_submeter'] === 1;
        $modoCredenciamento = $evento !== null ? $evento['modo_credenciamento'] : 'assistido';
        $respostasInscricao = $inscreverAutores ? $this->respostasPadraoDaInscricao($eventoId) : [];
        $pessoas = [];

        $pdo = Database::conexao();
        $pdo->beginTransaction();

        try {
            $configTravada = $this->config->buscarPorEventoParaAtualizar($eventoId);

            if ((int) $configTravada['permite_multiplos_trabalhos_por_pessoa'] === 0) {
                $this->checarDuplicidade($eventoId, $dadosAutorPrincipal, $coautores);
            }

            $trabalhoId = $this->trabalhos->criar([
                'evento_id' => $eventoId,
                'eixo_tematico_id' => !empty($dadosTrabalho['eixo_tematico_id']) ? $dadosTrabalho['eixo_tematico_id'] : null,
                'natureza_id' => !empty($dadosTrabalho['natureza_id']) ? $dadosTrabalho['natureza_id'] : null,
                'titulo' => $dadosTrabalho['titulo'],
                'telefone_contato' => !empty($dadosTrabalho['telefone_contato']) ? $dadosTrabalho['telefone_contato'] : null,
                'metodo_submissao' => $dadosTrabalho['metodo_submissao'],
                'conteudo_html' => isset($conteudo['conteudo_html']) ? $conteudo['conteudo_html'] : null,
                'link_avaliacao' => isset($conteudo['link_avaliacao']) ? $conteudo['link_avaliacao'] : null,
                'link_publicacao' => isset($conteudo['link_publicacao']) ? $conteudo['link_publicacao'] : null,
            ]);

            if (isset($conteudo['arquivo_avaliacao'])) {
                $caminhoAvaliacao = TrabalhoArquivoValidador::salvar($conteudo['arquivo_avaliacao'], $conteudo['extensao_avaliacao'], $trabalhoId);
                $caminhoPublicacao = null;

                if (isset($conteudo['arquivo_publicacao'])) {
                    $caminhoPublicacao = TrabalhoArquivoValidador::salvar($conteudo['arquivo_publicacao'], $conteudo['extensao_publicacao'], $trabalhoId);
                }

                $this->trabalhos->atualizarArquivos($trabalhoId, $caminhoAvaliacao, $caminhoPublicacao);
            }

            $cpfAutorPrincipal = CpfValidador::apenasDigitos($dadosAutorPrincipal['cpf']);
            $cargoAutorPrincipal = !empty($dadosAutorPrincipal['cargo']) ? $dadosAutorPrincipal['cargo'] : null;
            $orgaoOrigemAutorPrincipal = !empty($dadosAutorPrincipal['orgao_origem']) ? $dadosAutorPrincipal['orgao_origem'] : null;

            $this->autores->inserir(
                $trabalhoId,
                true,
                $usuarioId,
                $dadosAutorPrincipal['nome'],
                $cpfAutorPrincipal,
                $dadosAutorPrincipal['email'],
                $cargoAutorPrincipal,
                $orgaoOrigemAutorPrincipal
            );

            // Fase 49B: o autor principal SEMPRE tem conta - CPF/cargo/
            // órgão de origem informados aqui sincronizam de volta com
            // usuarios_perfil (fonte única da pessoa), fechando o ciclo
            // documento/inscrição/submissão em vez de deixar uma cópia
            // solta só em trabalho_autores.
            $camposPerfilAutor = ['documento' => $cpfAutorPrincipal, 'tipo_documento' => 'CPF'];

            if ($cargoAutorPrincipal !== null) {
                $camposPerfilAutor['cargo'] = $cargoAutorPrincipal;
            }

            if ($orgaoOrigemAutorPrincipal !== null) {
                $camposPerfilAutor['orgao_origem'] = $orgaoOrigemAutorPrincipal;
            }

            $this->usuarioPerfil->atualizarParcial($usuarioId, $camposPerfilAutor);

            $pessoas[] = [
                'papel' => 'principal',
                'nome' => $dadosAutorPrincipal['nome'],
                'email' => trim($dadosAutorPrincipal['email']),
                'usuario_id' => $usuarioId,
                'conta_nova' => false,
                'token_senha' => null,
                'inscricao' => 'nao_aplicavel',
                'email_enviado' => null,
            ];

            foreach ($coautores as $coautor) {
                $conta = ['usuario_id' => null, 'conta_nova' => false, 'token_senha' => null];

                if ($inscreverAutores) {
                    $conta = $this->resolverContaDoCoautor($coautor);
                }

                $cpfCoautor = CpfValidador::apenasDigitos($coautor['cpf']);
                $cargoCoautor = !empty($coautor['cargo']) ? $coautor['cargo'] : null;
                $orgaoCoautor = !empty($coautor['orgao_origem']) ? $coautor['orgao_origem'] : null;

                $this->autores->inserir(
                    $trabalhoId,
                    false,
                    $conta['usuario_id'],
                    $coautor['nome'],
                    $cpfCoautor,
                    $coautor['email'],
                    $cargoCoautor,
                    $orgaoCoautor
                );

                if ($conta['usuario_id'] !== null) {
                    $this->preencherPerfilVazio($conta['usuario_id'], $cpfCoautor, $cargoCoautor, $orgaoCoautor);
                }

                $pessoas[] = [
                    'papel' => 'coautor',
                    'nome' => $coautor['nome'],
                    'email' => mb_strtolower(trim($coautor['email'])),
                    'usuario_id' => $conta['usuario_id'],
                    'conta_nova' => $conta['conta_nova'],
                    'token_senha' => $conta['token_senha'],
                    'inscricao' => 'nao_inscrito',
                    'email_enviado' => null,
                ];
            }

            if (!empty($termosParaRegistrar)) {
                $this->aceites->registrar($trabalhoId, $termosParaRegistrar);
            }

            $this->garantirPerfilInscrito($usuarioId);

            if ($inscreverAutores) {
                foreach ($pessoas as $posicaoPessoa => $pessoa) {
                    if ($pessoa['usuario_id'] === null) {
                        continue;
                    }

                    $this->garantirPerfilInscrito($pessoa['usuario_id']);

                    $recemInscrito = $this->inscricoes->inscrever($eventoId, $pessoa['usuario_id'], $respostasInscricao, $modoCredenciamento);
                    $pessoas[$posicaoPessoa]['inscricao'] = $recemInscrito ? 'nova' : 'ja_inscrito';
                }
            }

            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();

            throw $e;
        }

        $this->ultimoResumo = [
            'trabalho_id' => (int) $trabalhoId,
            'titulo' => $dadosTrabalho['titulo'],
            'inscricao_automatica' => $inscreverAutores,
            'modo_credenciamento' => $modoCredenciamento,
            'pessoas' => $this->enviarRecebimentos($pessoas, $trabalhoId, $dadosTrabalho['titulo'], $evento, $config, $inscreverAutores, $modoCredenciamento),
        ];

        return $trabalhoId;
    }

    /**
     * Reabertura da Fase 51: conta do coautor para a inscricao automatica.
     * Sem conta com esse e-mail: cria conta aprovada, sem senha, com endereco
     * de definir senha valido por 7 dias (mesmo padrao da importacao de
     * trabalhos). Com conta aprovada: usa a existente. Conta pendente ou
     * rejeitada nunca e' alterada por uma submissao alheia: o coautor fica
     * so' registrado no trabalho, sem inscricao automatica.
     */
    private function resolverContaDoCoautor(array $coautor)
    {
        $email = mb_strtolower(trim($coautor['email']));
        $usuario = $this->usuarios->buscarPorEmail($email);

        if ($usuario === null) {
            $usuarioId = $this->usuarios->criarAprovadoSemSenha($coautor['nome'], $email);
            $token = $this->tokens->criar($usuarioId, 'definir', 168);

            return ['usuario_id' => $usuarioId, 'conta_nova' => true, 'token_senha' => $token];
        }

        if ($usuario['status'] !== 'aprovado') {
            return ['usuario_id' => null, 'conta_nova' => false, 'token_senha' => null];
        }

        return ['usuario_id' => (int) $usuario['id'], 'conta_nova' => false, 'token_senha' => null];
    }

    /**
     * Dados da pessoa moram em usuarios_perfil (fonte unica): para o coautor
     * (que nao digitou nada disso na propria conta), preenche so' o que
     * estiver vazio, nunca sobrescreve o que a pessoa ja cadastrou.
     */
    private function preencherPerfilVazio($usuarioId, $cpf, $cargo, $orgaoOrigem)
    {
        $atual = $this->usuarioPerfil->buscarPorUsuarioId($usuarioId);
        $campos = [];

        if ($atual === null || trim((string) $atual['documento']) === '') {
            $campos['documento'] = $cpf;
            $campos['tipo_documento'] = 'CPF';
        }

        if ($cargo !== null && ($atual === null || trim((string) $atual['cargo']) === '')) {
            $campos['cargo'] = $cargo;
        }

        if ($orgaoOrigem !== null && ($atual === null || trim((string) $atual['orgao_origem']) === '')) {
            $campos['orgao_origem'] = $orgaoOrigem;
        }

        if (!empty($campos)) {
            $this->usuarioPerfil->atualizarParcial($usuarioId, $campos);
        }
    }

    /**
     * Resposta que a submissao ja sabe dar ao formulario de inscricao do
     * evento: o tipo de documento e' CPF (o CPF foi informado no envio).
     * Os demais campos do formulario de inscricao ficam em branco.
     */
    private function respostasPadraoDaInscricao($eventoId)
    {
        foreach ($this->camposInscricao->listarPorEvento($eventoId) as $campo) {
            if ($campo['rotulo'] !== EventoCampoInscricaoRepository::ROTULO_TIPO_DOCUMENTO) {
                continue;
            }

            $configuracao = $campo['config_json'] !== null ? json_decode($campo['config_json'], true) : null;
            $opcoes = is_array($configuracao) && isset($configuracao['opcoes']) ? $configuracao['opcoes'] : [];

            return in_array('CPF', $opcoes, true) ? [$campo['id'] => 'CPF'] : [];
        }

        return [];
    }

    /**
     * Um e-mail por pessoa, depois do 'commit': recebimento do trabalho e,
     * quando houver, a inscricao no evento. Falha de envio nunca desfaz a
     * submissao; o resultado de cada envio volta no proprio item da pessoa,
     * e o registro fica em notificacoes. Coautor sem conta (inscricao
     * automatica desligada, ou conta que nao pode ser alterada) nao recebe.
     */
    private function enviarRecebimentos(array $pessoas, $trabalhoId, $titulo, $evento, array $config, $inscreverAutores, $modoCredenciamento)
    {
        $nomePrincipal = $pessoas[0]['nome'];
        $dadosTrabalho = ['id' => (int) $trabalhoId, 'titulo' => $titulo, 'recebido_em' => date('Y-m-d H:i:s')];
        $notificacoes = new NotificacaoService();

        foreach ($pessoas as $posicao => $pessoa) {
            if ($pessoa['papel'] === 'coautor' && $pessoa['usuario_id'] === null) {
                continue;
            }

            try {
                $pessoas[$posicao]['email_enviado'] = $notificacoes->recebimentoTrabalho(
                    $pessoa,
                    $dadosTrabalho,
                    $evento !== null ? $evento : ['id' => 0, 'nome' => '', 'data_inicio' => null, 'data_fim' => null],
                    $config,
                    $nomePrincipal,
                    $inscreverAutores,
                    $modoCredenciamento
                );
            } catch (\Throwable $e) {
                $pessoas[$posicao]['email_enviado'] = false;
            }
        }

        return $pessoas;
    }

    /**
     * Fase 51: confere os termos de aceite do evento contra o que foi
     * marcado no formulario e devolve, ja pronto, o que sera gravado como
     * aceite (rotulo e texto do momento, copia congelada). Evento sem termo
     * cadastrado simplesmente nao exige nada: o mecanismo e' opcional, como
     * todo catalogo por evento neste sistema.
     */
    private function validarTermos($eventoId, array $termosAceitos)
    {
        $ativos = $this->termos->listarAtivos($eventoId);

        if (empty($ativos)) {
            return [];
        }

        $marcados = array_map('intval', $termosAceitos);
        $registrar = [];

        foreach ($ativos as $termo) {
            $foiMarcado = in_array((int) $termo['id'], $marcados, true);

            if (!$foiMarcado) {
                if ((int) $termo['obrigatorio'] === 1) {
                    throw new TrabalhoSubmissaoException('É necessário aceitar: ' . $termo['rotulo'], 'termos');
                }

                continue;
            }

            $registrar[] = [
                'termo_id' => (int) $termo['id'],
                'rotulo' => $termo['rotulo'],
                'texto_html' => $termo['texto_html'],
                'origem' => 'sistema',
            ];
        }

        return $registrar;
    }

    /**
     * Metodo "formulario direto" usa um campo de texto simples (textarea),
     * nao um editor rico tipo WYSIWYG: reaproveitar _editor_rico.php
     * (assets/js/editor-rico.js) exigiria confiar em HTML digitado por um
     * participante externo qualquer, quebrando o pressuposto de seguranca
     * daquele componente (so' e' seguro porque so' Administrador grava
     * nele hoje) e ainda ofereceria botoes (imagem, cor, fonte) que essa
     * submissao nao suporta. Em vez disso, o texto digitado e' sempre
     * escapado primeiro (nenhuma tag do participante sobrevive) e so'
     * depois reconstruido em paragrafos/quebras de linha - nao ha como uma
     * tag maliciosa passar, porque a reconstrucao nunca le tag nenhuma do
     * texto original.
     */
    private function textoParaHtmlSeguro($texto)
    {
        $texto = str_replace(["\r\n", "\r"], "\n", trim($texto));
        $paragrafos = preg_split('/\n{2,}/', $texto);

        $html = '';
        foreach ($paragrafos as $paragrafo) {
            $paragrafo = trim($paragrafo);

            if ($paragrafo === '') {
                continue;
            }

            $escapado = htmlspecialchars($paragrafo, ENT_QUOTES, 'UTF-8');
            $html .= '<p>' . nl2br($escapado) . '</p>';
        }

        return $html;
    }

    private function validarPrazo(array $config)
    {
        $agora = date('Y-m-d H:i:s');

        if ($config['data_abertura_submissao'] !== null && $agora < $config['data_abertura_submissao']) {
            throw new TrabalhoSubmissaoException('O prazo de submissão de trabalhos ainda não começou.');
        }

        if ($config['data_fim_submissao'] !== null && $agora > $config['data_fim_submissao']) {
            throw new TrabalhoSubmissaoException('O prazo de submissão de trabalhos já terminou.');
        }
    }

    private function validarMetodo(array $config, array $dadosTrabalho)
    {
        $metodosHabilitados = $config['metodos_submissao_json'] !== null ? json_decode($config['metodos_submissao_json'], true) : [];

        if (empty($dadosTrabalho['metodo_submissao']) || !in_array($dadosTrabalho['metodo_submissao'], (array) $metodosHabilitados, true)) {
            throw new TrabalhoSubmissaoException('Método de submissão inválido ou não habilitado para este evento.', 'metodo_submissao');
        }
    }

    /**
     * So' chamar dentro da transacao com FOR UPDATE em
     * evento_trabalhos_config (buscarPorEventoParaAtualizar() ja' chamado
     * pelo metodo publico antes deste).
     */
    private function checarDuplicidade($eventoId, array $dadosAutorPrincipal, array $coautores)
    {
        $cpfPrincipal = CpfValidador::apenasDigitos($dadosAutorPrincipal['cpf']);

        if ($this->autores->cpfJaExisteNoEvento($eventoId, $cpfPrincipal)) {
            throw new TrabalhoSubmissaoException('Este CPF já consta em outro trabalho submetido neste evento.', 'autor_cpf');
        }

        foreach ($coautores as $posicao => $coautor) {
            $indiceCoautor = isset($coautor['indice']) ? $coautor['indice'] : $posicao;
            $cpfCoautor = CpfValidador::apenasDigitos($coautor['cpf']);

            if ($this->autores->cpfJaExisteNoEvento($eventoId, $cpfCoautor)) {
                throw new TrabalhoSubmissaoException('O CPF do coautor já consta em outro trabalho submetido neste evento.', 'coautor_cpf', $indiceCoautor);
            }
        }
    }

    /**
     * Valida o conteudo conforme o metodo escolhido, ANTES de abrir a
     * transacao (e' entrada/saida de arquivo, nao deve ficar presa numa
     * transacao de banco). Devolve os dados ja' validados/sanitizados,
     * prontos para gravar.
     */
    private function validarConteudo(array $config, array $dadosTrabalho, array $arquivosEnviados)
    {
        $sigiloCego = (int) $config['sigilo_cego'] === 1;
        $metodo = $dadosTrabalho['metodo_submissao'];

        if ($metodo === 'formulario') {
            if (empty($dadosTrabalho['conteudo_html'])) {
                throw new TrabalhoSubmissaoException('Informe o conteúdo do trabalho.', 'conteudo_html');
            }

            return ['conteudo_html' => $this->textoParaHtmlSeguro($dadosTrabalho['conteudo_html'])];
        }

        if ($metodo === 'link_externo') {
            if (empty($dadosTrabalho['link_avaliacao']) || !linkHttpValido($dadosTrabalho['link_avaliacao'])) {
                throw new TrabalhoSubmissaoException('Informe, no campo do arquivo sem identificação, um endereço eletrônico válido, começando com http:// ou https://.', 'link_avaliacao');
            }

            $resultado = ['link_avaliacao' => $dadosTrabalho['link_avaliacao']];

            if ($sigiloCego) {
                if (empty($dadosTrabalho['link_publicacao']) || !linkHttpValido($dadosTrabalho['link_publicacao'])) {
                    throw new TrabalhoSubmissaoException('Informe o endereço eletrônico da versão completa (identificada), começando com http:// ou https://.', 'link_publicacao');
                }

                $resultado['link_publicacao'] = $dadosTrabalho['link_publicacao'];
            }

            return $resultado;
        }

        if ($metodo === 'documento_editavel' || $metodo === 'documento_nao_editavel') {
            $extensoesPermitidas = $metodo === 'documento_nao_editavel'
                ? ['pdf']
                : $this->config->extensoesEditavelHabilitadas($config['evento_id']);

            $limiteBytes = (int) $config['tamanho_maximo_mb'] * 1024 * 1024;

            if (!isset($arquivosEnviados['arquivo_avaliacao'])) {
                throw new TrabalhoSubmissaoException('Escolha o arquivo sem identificação.', 'arquivo_avaliacao');
            }

            $validacaoAvaliacao = TrabalhoArquivoValidador::validar($arquivosEnviados['arquivo_avaliacao'], $extensoesPermitidas, $limiteBytes, 'arquivo_avaliacao');

            if (!$validacaoAvaliacao['valido']) {
                throw new TrabalhoSubmissaoException('Arquivo sem identificação: ' . $validacaoAvaliacao['mensagem'], 'arquivo_avaliacao');
            }

            $resultado = [
                'arquivo_avaliacao' => $arquivosEnviados['arquivo_avaliacao'],
                'extensao_avaliacao' => $validacaoAvaliacao['extensao'],
            ];

            if ($sigiloCego) {
                if (!isset($arquivosEnviados['arquivo_publicacao'])) {
                    throw new TrabalhoSubmissaoException('Escolha o arquivo da versão completa, com identificação.', 'arquivo_publicacao');
                }

                $validacaoPublicacao = TrabalhoArquivoValidador::validar($arquivosEnviados['arquivo_publicacao'], $extensoesPermitidas, $limiteBytes, 'arquivo_publicacao');

                if (!$validacaoPublicacao['valido']) {
                    throw new TrabalhoSubmissaoException('Versão completa (identificada): ' . $validacaoPublicacao['mensagem'], 'arquivo_publicacao');
                }

                $resultado['arquivo_publicacao'] = $arquivosEnviados['arquivo_publicacao'];
                $resultado['extensao_publicacao'] = $validacaoPublicacao['extensao'];
            }

            return $resultado;
        }

        throw new TrabalhoSubmissaoException('Método de submissão inválido.', 'metodo_submissao');
    }

    /**
     * Mesmo mecanismo ja usado em AuthService::cadastrarInscrito() e em
     * EventoAtividadeFacilitadorRepository::garantirPerfilInscrito() (Fase
     * 48) - so' para Auth::destinoPainel() reconhecer a pessoa e o
     * roteamento do app funcionar (ver correcao de roteamento no plano
     * desta fase).
     */
    private function garantirPerfilInscrito($usuarioId)
    {
        $perfilInscrito = $this->perfis->buscarPorChave('inscrito');

        if ($perfilInscrito === null) {
            return;
        }

        if (!$this->perfis->possuiPerfil($usuarioId, $perfilInscrito['id'], null)) {
            $this->perfis->atribuir($usuarioId, $perfilInscrito['id'], null);
        }
    }
}
