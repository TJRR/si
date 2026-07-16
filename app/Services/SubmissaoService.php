<?php

namespace App\Services;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Core\Database;
use App\Repositories\CampoDinamicoRepository;
use App\Repositories\DesafioRepository;
use App\Repositories\EtapaRepository;
use App\Repositories\FormularioDinamicoRepository;
use App\Repositories\SubmissaoRepository;
use App\Repositories\TrilhaRepository;
use App\Validation\CpfValidador;
use App\Validation\UploadPdfValidador;
use App\Validation\YoutubeValidador;

class SubmissaoService
{
    const LIMITE_UPLOAD_BYTES = 15728640; // 15 MB, sempre reforcado no servidor.

    private $etapas;
    private $trilhas;
    private $formularios;
    private $campos;
    private $desafios;
    private $submissoes;

    public function __construct()
    {
        $this->etapas = new EtapaRepository();
        $this->trilhas = new TrilhaRepository();
        $this->formularios = new FormularioDinamicoRepository();
        $this->campos = new CampoDinamicoRepository();
        $this->desafios = new DesafioRepository();
        $this->submissoes = new SubmissaoRepository();
    }

    /**
     * $equipeId (Fase 17, Bug 2) e' opcional - quando informado, carrega a
     * submissao ja existente da equipe nesta etapa (se houver) pra
     * pre-preencher o formulario e permitir edicao em vez de sempre mostrar
     * vazio. Sem $equipeId, preparar() so' descreve o formulario (uso
     * historico, ex.: reconstruir_campos_etapa1.php nao usa este metodo, mas
     * outros callers que so' querem a estrutura continuam funcionando).
     */
    public function preparar($etapaId, $equipeId = null)
    {
        $etapa = $this->etapas->buscarPorId($etapaId);

        if ($etapa === null || $etapa['formulario_dinamico_id'] === null) {
            return ['sucesso' => false, 'mensagem' => 'Não há formulário disponível para esta etapa.'];
        }

        if ((int) $etapa['ordem'] === 1) {
            return ['sucesso' => false, 'mensagem' => 'Esta etapa é de inscrição de equipe, não de submissão.'];
        }

        $formulario = $this->formularios->buscarPorId($etapa['formulario_dinamico_id']);

        if ($formulario === null || $formulario['status'] !== 'publicado') {
            return ['sucesso' => false, 'mensagem' => 'Este formulário não está disponível no momento.'];
        }

        $hoje = date('Y-m-d');

        if ($etapa['data_inicio'] !== null && $hoje < $etapa['data_inicio']) {
            return ['sucesso' => false, 'mensagem' => 'O prazo de submissão ainda não começou.'];
        }

        if ($etapa['data_fim'] !== null && $hoje > $etapa['data_fim']) {
            return ['sucesso' => false, 'mensagem' => 'O prazo de submissão já foi encerrado.'];
        }

        $trilha = $this->trilhas->buscarPorId($etapa['trilha_id']);

        $submissaoExistente = $equipeId !== null ? $this->submissoes->buscarPorEquipeEEtapa($equipeId, $etapa['id']) : null;
        $valoresExistentes = [];

        if ($submissaoExistente !== null) {
            $dados = json_decode((string) $submissaoExistente['dados_json'], true);
            $valoresExistentes = isset($dados['campos']) && is_array($dados['campos']) ? $dados['campos'] : [];
        }

        return [
            'sucesso' => true,
            'etapa' => $etapa,
            'formulario' => $formulario,
            'trilha' => $trilha,
            'campos' => $this->campos->listarPorFormulario($formulario['id']),
            'desafios' => $this->desafios->listarAtivosPorTrilha($trilha['id']),
            'submissaoExistente' => $submissaoExistente,
            'valoresExistentes' => $valoresExistentes,
        ];
    }

    public function processar($etapaId, array $post, array $files, $equipeId)
    {
        $preparo = $this->preparar($etapaId, $equipeId);

        if (!$preparo['sucesso']) {
            return $preparo;
        }

        $trilhaId = $preparo['trilha']['id'];
        $desafiosValidos = array_map('intval', array_column($preparo['desafios'], 'id'));
        $postCampos = isset($post['campos']) && is_array($post['campos']) ? $post['campos'] : [];
        $filesCampos = $this->normalizarArquivos($files);

        $valoresProcessados = [];
        $erros = [];
        $cpfsEncontrados = [];

        foreach ($preparo['campos'] as $campo) {
            $campoId = (int) $campo['id'];
            $config = $campo['config_json'] !== null ? json_decode($campo['config_json'], true) : [];
            $valorPost = isset($postCampos[$campoId]) ? $postCampos[$campoId] : null;
            $arquivoPost = isset($filesCampos[$campoId]) ? $filesCampos[$campoId] : null;
            $valorExistente = isset($preparo['valoresExistentes'][(string) $campoId]) ? $preparo['valoresExistentes'][(string) $campoId] : null;

            $resultado = $this->validarCampo($campo, $config, $valorPost, $arquivoPost, $desafiosValidos, $valorExistente);

            if (!$resultado['valido']) {
                $erros[$campoId] = $resultado['mensagem'];
                continue;
            }

            $valoresProcessados[$campoId] = $resultado['valor'];

            foreach ($resultado['cpfs'] as $cpf) {
                $cpfsEncontrados[] = $cpf;
            }
        }

        $cpfsDuplicadosNaSubmissao = array_diff_assoc($cpfsEncontrados, array_unique($cpfsEncontrados));

        if (!empty($cpfsDuplicadosNaSubmissao)) {
            return [
                'sucesso' => false,
                'mensagem' => 'Há CPFs repetidos dentro da própria submissão: ' . implode(', ', array_unique($cpfsDuplicadosNaSubmissao)),
                'erros' => $erros,
            ];
        }

        foreach (array_unique($cpfsEncontrados) as $cpf) {
            if ($this->submissoes->cpfJaExisteNaTrilha($trilhaId, $cpf)) {
                return [
                    'sucesso' => false,
                    'mensagem' => "O CPF {$cpf} já foi utilizado em outra submissão desta trilha.",
                    'erros' => $erros,
                ];
            }
        }

        if (!empty($erros)) {
            return ['sucesso' => false, 'mensagem' => 'Corrija os campos indicados.', 'erros' => $erros];
        }

        $resultado = $this->gravar($preparo, $valoresProcessados, array_unique($cpfsEncontrados), $preparo['submissaoExistente']);

        if ($resultado['sucesso']) {
            $this->notificarConfirmacaoSubmissao($preparo, $valoresProcessados, $resultado['submissao_id']);
        }

        return $resultado;
    }

    private function notificarConfirmacaoSubmissao(array $preparo, array $valoresProcessados, $submissaoId)
    {
        $emailDestinatario = null;

        foreach ($preparo['campos'] as $campo) {
            if ($campo['tipo'] === 'email' && !empty($valoresProcessados[(int) $campo['id']])) {
                $emailDestinatario = $valoresProcessados[(int) $campo['id']];
                break;
            }
        }

        if ($emailDestinatario === null) {
            return;
        }

        try {
            (new NotificacaoService())->confirmarSubmissao(
                $emailDestinatario,
                $preparo['trilha'],
                $preparo['etapa'],
                $submissaoId
            );
        } catch (\Exception $e) {
            // Falha de notificacao nunca deve quebrar a submissao ja gravada.
        }
    }

    /**
     * $submissaoExistente (Fase 17, Bug 2): se a equipe ja submeteu nesta
     * etapa, atualiza a submissao existente (upsert) em vez de criar uma
     * nova - reenviar o formulario e' edicao, nao uma segunda inscricao.
     */
    private function gravar(array $preparo, array $valoresProcessados, array $cpfs, $submissaoExistente = null)
    {
        $pdo = Database::conexao();
        $arquivosMovidos = [];
        $criada = $submissaoExistente === null;

        $pdo->beginTransaction();

        try {
            $submissaoId = $criada
                ? $this->submissoes->criar(
                    $preparo['etapa']['id'],
                    $preparo['formulario']['id'],
                    ['campos' => $valoresProcessados]
                )
                : (int) $submissaoExistente['id'];

            foreach ($valoresProcessados as $campoId => $valor) {
                if (!is_array($valor) || !isset($valor['__upload_tmp'])) {
                    continue;
                }

                $pastaDestino = __DIR__ . '/../../storage/uploads/submissoes/' . $submissaoId;

                if (!is_dir($pastaDestino)) {
                    mkdir($pastaDestino, 0775, true);
                }

                $nomeArmazenado = bin2hex(random_bytes(16)) . '.pdf';
                $caminhoDestino = $pastaDestino . '/' . $campoId . '_' . $nomeArmazenado;

                if (!move_uploaded_file($valor['__upload_tmp'], $caminhoDestino)) {
                    throw new \RuntimeException('Falha ao salvar o arquivo enviado.');
                }

                $arquivosMovidos[] = $caminhoDestino;

                $valoresProcessados[$campoId] = [
                    'nome_original' => $valor['nome_original'],
                    'nome_armazenado' => $nomeArmazenado,
                    'tamanho_bytes' => $valor['tamanho_bytes'],
                    'caminho_relativo' => 'submissoes/' . $submissaoId . '/' . $campoId . '_' . $nomeArmazenado,
                ];
            }

            $this->submissoes->atualizarDadosJson($submissaoId, ['campos' => $valoresProcessados]);

            foreach ($cpfs as $cpf) {
                $this->submissoes->inserirCpf($submissaoId, $preparo['trilha']['id'], $cpf);
            }

            $pdo->commit();

            return [
                'sucesso' => true,
                'submissao_id' => $submissaoId,
                'criada' => $criada,
                'desafio_id' => $this->extrairDesafioEscolhido($preparo['campos'], $valoresProcessados),
            ];
        } catch (\Exception $e) {
            $pdo->rollBack();

            foreach ($arquivosMovidos as $arquivo) {
                if (file_exists($arquivo)) {
                    unlink($arquivo);
                }
            }

            return ['sucesso' => false, 'mensagem' => 'Não foi possível gravar a submissão. Tente novamente.'];
        }
    }

    /**
     * Fase 17 (Bug 2): acha o valor do campo "selecao_tema_desafio" (se
     * existir no formulario) entre os valores ja validados, para o Controller
     * gravar de volta em equipes.desafio_id (EquipeRepository::definirDesafio) -
     * primeira vez que essa coluna passa a ser escrita de verdade.
     */
    private function extrairDesafioEscolhido(array $campos, array $valoresProcessados)
    {
        foreach ($campos as $campo) {
            if ($campo['tipo'] === 'selecao_tema_desafio') {
                $campoId = (int) $campo['id'];

                return isset($valoresProcessados[$campoId]) ? (int) $valoresProcessados[$campoId] : null;
            }
        }

        return null;
    }

    private function validarCampo(array $campo, array $config, $valorPost, $arquivoPost, array $desafiosValidos, $valorExistente = null)
    {
        $tipo = $campo['tipo'];
        $obrigatorio = (bool) $campo['obrigatorio'];

        if ($tipo === 'upload_pdf') {
            if ($arquivoPost === null || $arquivoPost['error'] === UPLOAD_ERR_NO_FILE) {
                // Fase 17 (Bug 2): reabrir pra editar sem escolher um arquivo
                // novo mantem o arquivo ja enviado (o <input type="file"> do
                // navegador nunca vem pre-preenchido, entao "vazio" aqui nao
                // significa "o participante quis apagar o arquivo").
                if (is_array($valorExistente) && isset($valorExistente['caminho_relativo'])) {
                    return ['valido' => true, 'valor' => $valorExistente, 'cpfs' => []];
                }

                if ($obrigatorio) {
                    return ['valido' => false, 'mensagem' => 'Campo obrigatório.'];
                }

                return ['valido' => true, 'valor' => null, 'cpfs' => []];
            }

            $validacao = UploadPdfValidador::validar($arquivoPost, self::LIMITE_UPLOAD_BYTES);

            if (!$validacao['valido']) {
                return ['valido' => false, 'mensagem' => $validacao['mensagem']];
            }

            return [
                'valido' => true,
                'cpfs' => [],
                'valor' => [
                    '__upload_tmp' => $arquivoPost['tmp_name'],
                    'nome_original' => basename($arquivoPost['name']),
                    'tamanho_bytes' => (int) $arquivoPost['size'],
                ],
            ];
        }

        if ($tipo === 'grupo_participantes') {
            return $this->validarGrupoParticipantes($config, $obrigatorio, $valorPost);
        }

        $valor = is_string($valorPost) ? trim($valorPost) : $valorPost;

        if ($obrigatorio && ($valor === null || $valor === '')) {
            return ['valido' => false, 'mensagem' => 'Campo obrigatório.'];
        }

        if ($valor === null || $valor === '') {
            return ['valido' => true, 'valor' => null, 'cpfs' => []];
        }

        switch ($tipo) {
            case 'texto':
            case 'texto_longo':
                return ['valido' => true, 'valor' => $valor, 'cpfs' => []];

            case 'numero':
                if (!is_numeric($valor)) {
                    return ['valido' => false, 'mensagem' => 'Informe um número válido.'];
                }

                return ['valido' => true, 'valor' => $valor + 0, 'cpfs' => []];

            case 'cpf':
                if (!CpfValidador::valido($valor)) {
                    return ['valido' => false, 'mensagem' => 'CPF inválido.'];
                }

                $cpfNormalizado = CpfValidador::apenasDigitos($valor);

                return ['valido' => true, 'valor' => $cpfNormalizado, 'cpfs' => [$cpfNormalizado]];

            case 'email':
                if (filter_var($valor, FILTER_VALIDATE_EMAIL) === false) {
                    return ['valido' => false, 'mensagem' => 'E-mail inválido.'];
                }

                return ['valido' => true, 'valor' => $valor, 'cpfs' => []];

            case 'telefone':
                $digitos = preg_replace('/\D/', '', $valor);

                if (strlen($digitos) < 10 || strlen($digitos) > 11) {
                    return ['valido' => false, 'mensagem' => 'Telefone inválido.'];
                }

                return ['valido' => true, 'valor' => $digitos, 'cpfs' => []];

            case 'link_youtube':
                if (!YoutubeValidador::valido($valor)) {
                    return ['valido' => false, 'mensagem' => 'Link do YouTube inválido.'];
                }

                return ['valido' => true, 'valor' => $valor, 'cpfs' => []];

            case 'selecao_tema_desafio':
                if (!in_array((int) $valor, $desafiosValidos, true)) {
                    return ['valido' => false, 'mensagem' => 'Selecione um desafio válido.'];
                }

                return ['valido' => true, 'valor' => (int) $valor, 'cpfs' => []];

            default:
                return ['valido' => false, 'mensagem' => 'Tipo de campo desconhecido.'];
        }
    }

    private function validarGrupoParticipantes(array $config, $obrigatorio, $valorPost)
    {
        $minimo = isset($config['minimo_repeticoes']) ? (int) $config['minimo_repeticoes'] : 1;
        $maximo = isset($config['maximo_repeticoes']) ? (int) $config['maximo_repeticoes'] : 10;

        $linhas = is_array($valorPost) ? $valorPost : [];
        $linhasValidas = [];

        foreach ($linhas as $linha) {
            $nome = trim(isset($linha['nome']) ? $linha['nome'] : '');

            if ($nome === '' && empty($linha['cpf']) && empty($linha['email']) && empty($linha['telefone'])) {
                continue; // linha em branco, ignorar (sobrou de "adicionar mais um" sem preencher).
            }

            $linhasValidas[] = $linha;
        }

        if (empty($linhasValidas)) {
            if ($obrigatorio) {
                return ['valido' => false, 'mensagem' => 'Adicione ao menos um participante.'];
            }

            return ['valido' => true, 'valor' => [], 'cpfs' => []];
        }

        if (count($linhasValidas) < $minimo) {
            return ['valido' => false, 'mensagem' => "Informe ao menos {$minimo} participante(s)."];
        }

        if (count($linhasValidas) > $maximo) {
            return ['valido' => false, 'mensagem' => "No máximo {$maximo} participante(s)."];
        }

        $participantes = [];
        $cpfs = [];

        foreach ($linhasValidas as $linha) {
            $nome = trim(isset($linha['nome']) ? $linha['nome'] : '');
            $cpf = trim(isset($linha['cpf']) ? $linha['cpf'] : '');
            $email = trim(isset($linha['email']) ? $linha['email'] : '');
            $telefone = trim(isset($linha['telefone']) ? $linha['telefone'] : '');
            $vinculo = trim(isset($linha['vinculo_profissao']) ? $linha['vinculo_profissao'] : '');

            if ($nome === '') {
                return ['valido' => false, 'mensagem' => 'Nome do participante é obrigatório.'];
            }

            if (!CpfValidador::valido($cpf)) {
                return ['valido' => false, 'mensagem' => "CPF inválido para o participante {$nome}."];
            }

            if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
                return ['valido' => false, 'mensagem' => "E-mail inválido para o participante {$nome}."];
            }

            $cpfNormalizado = CpfValidador::apenasDigitos($cpf);
            $cpfs[] = $cpfNormalizado;

            $participantes[] = [
                'nome' => $nome,
                'cpf' => $cpfNormalizado,
                'email' => $email,
                'telefone' => preg_replace('/\D/', '', $telefone),
                'vinculo_profissao' => $vinculo,
            ];
        }

        return ['valido' => true, 'valor' => $participantes, 'cpfs' => $cpfs];
    }

    private function normalizarArquivos(array $files)
    {
        if (!isset($files['campos']['tmp_name']) || !is_array($files['campos']['tmp_name'])) {
            return [];
        }

        $resultado = [];

        foreach ($files['campos']['tmp_name'] as $campoId => $tmpName) {
            $resultado[(int) $campoId] = [
                'name' => $files['campos']['name'][$campoId],
                'type' => $files['campos']['type'][$campoId],
                'tmp_name' => $tmpName,
                'error' => $files['campos']['error'][$campoId],
                'size' => $files['campos']['size'][$campoId],
            ];
        }

        return $resultado;
    }
}
