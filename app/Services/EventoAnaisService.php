<?php

namespace App\Services;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Repositories\EventoAnaisRepository;
use App\Repositories\EventoAnaisVersaoRepository;
use App\Repositories\EventoTrabalhoTermoRepository;
use App\Repositories\TrabalhoAutorRepository;
use App\Repositories\TrabalhoRepository;
use App\Repositories\TrabalhoTermoAceiteRepository;

/**
 * Fase 53: regras dos Anais do Evento. O volume e' montado fora do servidor
 * e chega como um unico PDF; aqui o sistema so' confere o arquivo, guarda cada
 * versao, publica (Documento do Evento, pagina publica e aplicativo) e mantem
 * a lista dos trabalhos que constam no volume. Nada e' gerado nem juntado.
 */
class EventoAnaisService
{
    public const TITULO_MAXIMO = 120;
    public const DESCRICAO_MAXIMA = 500;
    public const OBSERVACAO_MAXIMA = 255;
    public const MOTIVO_MAXIMO = 255;

    private $anais;
    private $versoes;

    public function __construct()
    {
        $this->anais = new EventoAnaisRepository();
        $this->versoes = new EventoAnaisVersaoRepository();
    }

    /**
     * Confere e limpa os campos do formulario de identificacao. Devolve
     * ['erro' => mensagem ou null, 'dados' => campos prontos para gravar].
     * Depois da primeira publicacao o titulo nao muda mais: o Documento do
     * Evento agrupa as versoes por tipo e titulo, e um titulo novo separaria
     * o historico. Um titulo enviado nesse caso e' ignorado.
     */
    public function validarDados(array $post, array $atual = null)
    {
        $tituloBloqueado = $atual !== null && !empty($atual['documento_titulo']);
        $titulo = $tituloBloqueado ? (string) $atual['titulo'] : trim(isset($post['titulo']) ? (string) $post['titulo'] : '');

        $tipo = isset($post['identificador_tipo']) && in_array($post['identificador_tipo'], EventoAnaisRepository::IDENTIFICADORES, true)
            ? $post['identificador_tipo']
            : 'nenhum';
        $identificador = trim(isset($post['identificador']) ? (string) $post['identificador'] : '');
        $descricao = trim(isset($post['descricao']) ? (string) $post['descricao'] : '');
        $mensagemHtml = isset($post['mensagem_publicacao_html']) ? (string) $post['mensagem_publicacao_html'] : '';

        $dados = [
            'titulo' => $titulo,
            'identificador_tipo' => $tipo,
            'identificador' => $tipo === 'nenhum' || $identificador === '' ? null : $identificador,
            'descricao' => $descricao !== '' ? $descricao : null,
            'mensagem_publicacao_html' => trim(strip_tags($mensagemHtml)) !== '' ? sanitizarHtmlRico($mensagemHtml) : null,
        ];

        $erro = null;

        if ($titulo === '') {
            $erro = 'Informe o título dos Anais.';
        } elseif (mb_strlen($titulo) > self::TITULO_MAXIMO) {
            $erro = 'O título dos Anais aceita até ' . self::TITULO_MAXIMO . ' caracteres.';
        } elseif (mb_strlen($descricao) > self::DESCRICAO_MAXIMA) {
            $erro = 'A descrição aceita até ' . self::DESCRICAO_MAXIMA . ' caracteres.';
        } elseif ($tipo !== 'nenhum' && $identificador === '') {
            $erro = 'Informe o número do ' . strtoupper($tipo) . ' ou escolha "Sem identificador".';
        } elseif ($tipo === 'issn') {
            if (!preg_match('/^\d{4}-\d{3}[\dXx]$/', $identificador)) {
                $erro = 'Informe o ISSN no formato 0000-0000.';
            } else {
                $dados['identificador'] = strtoupper($identificador);
            }
        } elseif ($tipo === 'isbn') {
            $digitos = preg_replace('/[^0-9Xx]/', '', $identificador);

            if (!preg_match('/^[0-9Xx-]+$/', $identificador) || !in_array(strlen($digitos), [10, 13], true)) {
                $erro = 'Informe o ISBN com 10 ou 13 dígitos (hífens são aceitos).';
            } else {
                $dados['identificador'] = strtoupper($identificador);
            }
        }

        return ['erro' => $erro, 'dados' => $dados];
    }

    /**
     * Devolve a mensagem de erro de validacao, ou null se salvou.
     */
    public function salvarDados($eventoId, array $post)
    {
        $resultado = $this->validarDados($post, $this->anais->buscarPorEvento($eventoId));

        if ($resultado['erro'] !== null) {
            return $resultado['erro'];
        }

        $this->anais->salvarDados($eventoId, $resultado['dados']);

        return null;
    }

    /**
     * Guarda o PDF enviado como a proxima versao. So' confere que o arquivo e'
     * um PDF de verdade (pelo conteudo, nao pela extensao) e o tamanho; o
     * conteudo do volume e' responsabilidade de quem o montou. Devolve o
     * numero da versao criada. Lanca RuntimeException com mensagem para a
     * tela.
     */
    public function enviarVersao($eventoId, array $arquivo, $observacao, $usuarioId)
    {
        $observacao = trim((string) $observacao);

        if (mb_strlen($observacao) > self::OBSERVACAO_MAXIMA) {
            throw new \RuntimeException('A observação aceita até ' . self::OBSERVACAO_MAXIMA . ' caracteres.');
        }

        $erroEnvio = isset($arquivo['error']) ? $arquivo['error'] : UPLOAD_ERR_NO_FILE;

        if ($erroEnvio === UPLOAD_ERR_INI_SIZE || $erroEnvio === UPLOAD_ERR_FORM_SIZE) {
            throw new \RuntimeException('O arquivo é maior que o limite de ' . ArquivoService::limiteMaximoMB() . 'MB do servidor.');
        }

        if ($erroEnvio !== UPLOAD_ERR_OK) {
            throw new \RuntimeException('Envie o arquivo PDF dos Anais.');
        }

        if ($arquivo['size'] > ArquivoService::limiteMaximoBytes()) {
            throw new \RuntimeException('O arquivo é maior que o limite de ' . ArquivoService::limiteMaximoMB() . 'MB do servidor.');
        }

        $caminho = ArquivoPrivadoService::salvar($arquivo, 'anais/' . (int) $eventoId);
        $fisico = ArquivoPrivadoService::caminhoFisico($caminho);

        try {
            $criada = $this->versoes->criar($eventoId, [
                'arquivo_path' => $caminho,
                'nome_original' => mb_substr(basename((string) $arquivo['name']), 0, 255),
                'tamanho_bytes' => $fisico !== null ? (int) filesize($fisico) : (int) $arquivo['size'],
                'sha256' => $fisico !== null ? hash_file('sha256', $fisico) : null,
                'observacao' => $observacao !== '' ? $observacao : null,
                'enviado_por' => $usuarioId,
            ]);
        } catch (\Throwable $e) {
            ArquivoPrivadoService::remover($caminho);

            throw $e;
        }

        return $criada['numero'];
    }

    public function removerVersao($eventoId, $versaoId)
    {
        $versao = $this->versoes->removerNaoPublicada($eventoId, $versaoId);

        if ($versao === null) {
            throw new \RuntimeException('Versão dos Anais não encontrada.');
        }

        ArquivoPrivadoService::remover($versao['arquivo_path']);

        return $versao;
    }

    /**
     * Publica a versao: copia o PDF para a pasta publica, e o repositorio cria
     * o Documento do Evento, marca a versao e grava o ponteiro numa transacao
     * so'. Se qualquer coisa falhar, o banco volta ao que era e a copia e'
     * apagada (nenhum Documento aponta para ela nesse caso).
     *
     * Devolve ['numero' => n, 'documento_id' => id].
     */
    public function publicar($eventoId, $versaoId, $usuarioId)
    {
        $motivo = $this->anais->motivoQueImpedePublicar($eventoId);

        if ($motivo !== null) {
            throw new \RuntimeException($motivo);
        }

        $versao = $this->versoes->buscarDoEvento($eventoId, $versaoId);

        if ($versao === null) {
            throw new \RuntimeException('Versão dos Anais não encontrada.');
        }

        $origem = ArquivoPrivadoService::caminhoFisico($versao['arquivo_path']);

        if ($origem === null) {
            throw new \RuntimeException('O arquivo desta versão não foi encontrado no servidor. Envie o PDF de novo.');
        }

        $pastaRelativa = 'uploads/arquivos/anais';
        $pastaFisica = __DIR__ . '/../../assets/' . $pastaRelativa;

        if (!is_dir($pastaFisica) && !mkdir($pastaFisica, 0755, true) && !is_dir($pastaFisica)) {
            throw new \RuntimeException('Não foi possível criar a pasta pública dos Anais.');
        }

        if (!is_writable($pastaFisica)) {
            throw new \RuntimeException('A pasta pública dos Anais está sem permissão de escrita.');
        }

        $nome = bin2hex(random_bytes(16)) . '.pdf';

        if (!copy($origem, $pastaFisica . '/' . $nome)) {
            throw new \RuntimeException('Não foi possível copiar o arquivo para a pasta pública.');
        }

        try {
            return $this->anais->publicarVersao($eventoId, $versaoId, $usuarioId, $pastaRelativa . '/' . $nome);
        } catch (\Throwable $e) {
            if (is_file($pastaFisica . '/' . $nome)) {
                unlink($pastaFisica . '/' . $nome);
            }

            throw $e;
        }
    }

    public function despublicar($eventoId, $usuarioId)
    {
        return $this->anais->despublicar($eventoId, $usuarioId);
    }

    /**
     * Linhas da tela "Trabalhos nos Anais": todos os trabalhos aprovados do
     * evento, na ordem da classificacao, com o que o Administrador precisa
     * para decidir (autoria sem CPF nem e-mail, eixo, nota, posicao e o
     * alerta de declaracao obrigatoria pendente).
     */
    public function montarSelecao($eventoId)
    {
        $exclusoes = $this->anais->listarExclusoes($eventoId);
        $autores = new TrabalhoAutorRepository();
        $aceites = new TrabalhoTermoAceiteRepository();

        $obrigatorias = [];

        foreach ((new EventoTrabalhoTermoRepository())->listarAtivos($eventoId) as $termo) {
            if ((int) $termo['obrigatorio'] === 1) {
                $obrigatorias[] = $termo;
            }
        }

        $linhas = [];

        foreach ((new TrabalhoRepository())->listarPorEvento($eventoId) as $trabalho) {
            if ($trabalho['status'] !== 'aprovado') {
                continue;
            }

            $nomes = [];

            foreach ($autores->listarPorTrabalho($trabalho['id']) as $autor) {
                $nomes[] = $autor['nome'] . (!empty($autor['orgao_origem']) ? ' (' . $autor['orgao_origem'] . ')' : '');
            }

            $pendentes = [];

            if (!empty($obrigatorias)) {
                $aceitos = $aceites->listarPorTrabalho($trabalho['id']);

                foreach ($obrigatorias as $termo) {
                    $aceito = false;

                    foreach ($aceitos as $registro) {
                        if ((int) $registro['termo_id'] === (int) $termo['id'] || $registro['rotulo_snapshot'] === $termo['rotulo']) {
                            $aceito = true;
                            break;
                        }
                    }

                    if (!$aceito) {
                        $pendentes[] = $termo['rotulo'];
                    }
                }
            }

            $linhas[] = [
                'id' => (int) $trabalho['id'],
                'titulo' => $trabalho['titulo'],
                'eixo_nome' => $trabalho['eixo_nome'],
                'autores' => $nomes,
                'nota_final' => $trabalho['nota_final'],
                'posicao' => $trabalho['posicao'] !== null ? (int) $trabalho['posicao'] : null,
                'incluido' => !isset($exclusoes[(int) $trabalho['id']]),
                'motivo' => isset($exclusoes[(int) $trabalho['id']]) ? (string) $exclusoes[(int) $trabalho['id']]['motivo'] : '',
                'declaracoes_pendentes' => $pendentes,
            ];
        }

        usort($linhas, function ($a, $b) {
            if ($a['posicao'] === $b['posicao']) {
                return strcasecmp($a['titulo'], $b['titulo']);
            }

            if ($a['posicao'] === null) {
                return 1;
            }

            if ($b['posicao'] === null) {
                return -1;
            }

            return $a['posicao'] - $b['posicao'];
        });

        return $linhas;
    }

    /**
     * Grava a lista de exclusoes a partir do formulario: quem estiver
     * desmarcado (fora de "incluir") sai dos Anais, com o motivo digitado.
     * Recusa enquanto os Anais estiverem publicados: a lista que os autores
     * ja viram nao muda por baixo deles.
     */
    public function salvarSelecao($eventoId, array $post, $usuarioId)
    {
        if ($this->anais->estaPublicado($eventoId)) {
            throw new \RuntimeException('Os Anais estão publicados. Despublique-os para mudar quais trabalhos constam.');
        }

        $incluidos = isset($post['incluir']) && is_array($post['incluir']) ? array_map('intval', $post['incluir']) : [];
        $motivos = isset($post['motivo']) && is_array($post['motivo']) ? $post['motivo'] : [];
        $exclusoes = [];

        foreach ((new TrabalhoRepository())->listarPorEvento($eventoId) as $trabalho) {
            if ($trabalho['status'] !== 'aprovado' || in_array((int) $trabalho['id'], $incluidos, true)) {
                continue;
            }

            $motivo = isset($motivos[$trabalho['id']]) ? trim((string) $motivos[$trabalho['id']]) : '';
            $exclusoes[(int) $trabalho['id']] = mb_substr($motivo, 0, self::MOTIVO_MAXIMO);
        }

        return $this->anais->salvarExclusoes($eventoId, $exclusoes, $usuarioId);
    }
}
