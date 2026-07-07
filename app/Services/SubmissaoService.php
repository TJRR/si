<?php

namespace App\Services;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Core\Database;
use App\Repositories\CampoDinamicoRepository;
use App\Repositories\EtapaRepository;
use App\Repositories\FormularioDinamicoRepository;
use App\Repositories\SubmissaoRepository;
use App\Repositories\TemaDesafioRepository;
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
    private $temas;
    private $submissoes;

    public function __construct()
    {
        $this->etapas = new EtapaRepository();
        $this->trilhas = new TrilhaRepository();
        $this->formularios = new FormularioDinamicoRepository();
        $this->campos = new CampoDinamicoRepository();
        $this->temas = new TemaDesafioRepository();
        $this->submissoes = new SubmissaoRepository();
    }

    public function preparar($etapaId)
    {
        $etapa = $this->etapas->buscarPorId($etapaId);

        if ($etapa === null || $etapa['formulario_dinamico_id'] === null) {
            return ['sucesso' => false, 'mensagem' => 'Nao ha formulario disponivel para esta etapa.'];
        }

        $formulario = $this->formularios->buscarPorId($etapa['formulario_dinamico_id']);

        if ($formulario === null || $formulario['status'] !== 'publicado') {
            return ['sucesso' => false, 'mensagem' => 'Este formulario nao esta disponivel no momento.'];
        }

        $hoje = date('Y-m-d');

        if ($etapa['data_inicio'] !== null && $hoje < $etapa['data_inicio']) {
            return ['sucesso' => false, 'mensagem' => 'O prazo de submissao ainda nao comecou.'];
        }

        if ($etapa['data_fim'] !== null && $hoje > $etapa['data_fim']) {
            return ['sucesso' => false, 'mensagem' => 'O prazo de submissao ja foi encerrado.'];
        }

        $trilha = $this->trilhas->buscarPorId($etapa['trilha_id']);

        return [
            'sucesso' => true,
            'etapa' => $etapa,
            'formulario' => $formulario,
            'trilha' => $trilha,
            'campos' => $this->campos->listarPorFormulario($formulario['id']),
            'temas' => $this->temas->listarAtivosPorTrilha($trilha['id']),
        ];
    }

    public function processar($etapaId, array $post, array $files)
    {
        $preparo = $this->preparar($etapaId);

        if (!$preparo['sucesso']) {
            return $preparo;
        }

        $trilhaId = $preparo['trilha']['id'];
        $temasValidos = array_map('intval', array_column($preparo['temas'], 'id'));
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

            $resultado = $this->validarCampo($campo, $config, $valorPost, $arquivoPost, $temasValidos);

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
                'mensagem' => 'Ha CPFs repetidos dentro da propria submissao: ' . implode(', ', array_unique($cpfsDuplicadosNaSubmissao)),
                'erros' => $erros,
            ];
        }

        foreach (array_unique($cpfsEncontrados) as $cpf) {
            if ($this->submissoes->cpfJaExisteNaTrilha($trilhaId, $cpf)) {
                return [
                    'sucesso' => false,
                    'mensagem' => "O CPF {$cpf} ja foi utilizado em outra submissao desta trilha.",
                    'erros' => $erros,
                ];
            }
        }

        if (!empty($erros)) {
            return ['sucesso' => false, 'mensagem' => 'Corrija os campos indicados.', 'erros' => $erros];
        }

        return $this->gravar($preparo, $valoresProcessados, array_unique($cpfsEncontrados));
    }

    private function gravar(array $preparo, array $valoresProcessados, array $cpfs)
    {
        $pdo = Database::conexao();
        $arquivosMovidos = [];

        $pdo->beginTransaction();

        try {
            $submissaoId = $this->submissoes->criar(
                $preparo['etapa']['id'],
                $preparo['formulario']['id'],
                ['campos' => $valoresProcessados]
            );

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

            return ['sucesso' => true, 'submissao_id' => $submissaoId];
        } catch (\Exception $e) {
            $pdo->rollBack();

            foreach ($arquivosMovidos as $arquivo) {
                if (file_exists($arquivo)) {
                    unlink($arquivo);
                }
            }

            return ['sucesso' => false, 'mensagem' => 'Nao foi possivel gravar a submissao. Tente novamente.'];
        }
    }

    private function validarCampo(array $campo, array $config, $valorPost, $arquivoPost, array $temasValidos)
    {
        $tipo = $campo['tipo'];
        $obrigatorio = (bool) $campo['obrigatorio'];

        if ($tipo === 'upload_pdf') {
            if ($arquivoPost === null || $arquivoPost['error'] === UPLOAD_ERR_NO_FILE) {
                if ($obrigatorio) {
                    return ['valido' => false, 'mensagem' => 'Campo obrigatorio.'];
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
            return ['valido' => false, 'mensagem' => 'Campo obrigatorio.'];
        }

        if ($valor === null || $valor === '') {
            return ['valido' => true, 'valor' => null, 'cpfs' => []];
        }

        switch ($tipo) {
            case 'texto':
                return ['valido' => true, 'valor' => $valor, 'cpfs' => []];

            case 'numero':
                if (!is_numeric($valor)) {
                    return ['valido' => false, 'mensagem' => 'Informe um numero valido.'];
                }

                return ['valido' => true, 'valor' => $valor + 0, 'cpfs' => []];

            case 'cpf':
                if (!CpfValidador::valido($valor)) {
                    return ['valido' => false, 'mensagem' => 'CPF invalido.'];
                }

                $cpfNormalizado = CpfValidador::apenasDigitos($valor);

                return ['valido' => true, 'valor' => $cpfNormalizado, 'cpfs' => [$cpfNormalizado]];

            case 'email':
                if (filter_var($valor, FILTER_VALIDATE_EMAIL) === false) {
                    return ['valido' => false, 'mensagem' => 'E-mail invalido.'];
                }

                return ['valido' => true, 'valor' => $valor, 'cpfs' => []];

            case 'telefone':
                $digitos = preg_replace('/\D/', '', $valor);

                if (strlen($digitos) < 10 || strlen($digitos) > 11) {
                    return ['valido' => false, 'mensagem' => 'Telefone invalido.'];
                }

                return ['valido' => true, 'valor' => $digitos, 'cpfs' => []];

            case 'link_youtube':
                if (!YoutubeValidador::valido($valor)) {
                    return ['valido' => false, 'mensagem' => 'Link do YouTube invalido.'];
                }

                return ['valido' => true, 'valor' => $valor, 'cpfs' => []];

            case 'selecao_tema_desafio':
                if (!in_array((int) $valor, $temasValidos, true)) {
                    return ['valido' => false, 'mensagem' => 'Selecione um tema/desafio valido.'];
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
            return ['valido' => false, 'mensagem' => "No maximo {$maximo} participante(s)."];
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
                return ['valido' => false, 'mensagem' => 'Nome do participante e obrigatorio.'];
            }

            if (!CpfValidador::valido($cpf)) {
                return ['valido' => false, 'mensagem' => "CPF invalido para o participante {$nome}."];
            }

            if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
                return ['valido' => false, 'mensagem' => "E-mail invalido para o participante {$nome}."];
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
