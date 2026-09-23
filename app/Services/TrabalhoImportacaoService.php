<?php

namespace App\Services;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Core\Database;
use App\Repositories\EventoComunicacaoRepository;
use App\Repositories\EventoTrabalhoTermoRepository;
use App\Repositories\PerfilRepository;
use App\Repositories\TokenSenhaRepository;
use App\Repositories\TrabalhoAutorRepository;
use App\Repositories\TrabalhoAvaliadorRepository;
use App\Repositories\TrabalhoConfigRepository;
use App\Repositories\TrabalhoEixoTematicoRepository;
use App\Repositories\TrabalhoNaturezaRepository;
use App\Repositories\TrabalhoRepository;
use App\Repositories\TrabalhoTermoAceiteRepository;
use App\Repositories\UsuarioPerfilRepository;
use App\Repositories\UsuarioRepository;
use App\Validation\CpfValidador;

/**
 * Fase 51: traz para o sistema os trabalhos recebidos por canal alternativo
 * (item 5.2 do edital n. 10/2026), no caso o formulario externo usado
 * enquanto o modulo de Trabalhos ainda nao estava em producao.
 *
 * Regras aplicadas, todas do proprio edital, nunca inventadas aqui:
 * item 5.4 (campos obrigatorios), item 5.6 (dois arquivos editaveis, versao
 * sem identificacao para avaliacao e versao identificada para publicacao),
 * item 3.4 (uma pessoa participa de um unico trabalho), item 5.9 (vale a
 * ultima submissao dentro do prazo, as anteriores sao desconsideradas) e
 * item 7.6 (data e hora de envio desempatam, por isso a data ORIGINAL e
 * preservada).
 *
 * O servico nunca envia e-mail: quando autorizado, enfileira o convite na
 * fila de envio do evento (10 por execucao do agendador), o mesmo lote que
 * protege o canal institucional compartilhado com o Concurso.
 */
class TrabalhoImportacaoService
{
    /**
     * Campos que o importador entende. O valor e' so' uma pista de busca
     * usada para sugerir o mapeamento a partir do cabecalho real da
     * planilha: o mapeamento valendo e' sempre o arquivo passado em --mapa,
     * conferido por quem importa.
     */
    private static $camposConhecidos = [
        'carimbo' => ['carimbo de data', 'timestamp'],
        'email_resposta' => ['endereço de e-mail', 'endereco de e-mail'],
        'declaracao' => ['declaro que li'],
        'titulo' => ['título do trabalho', 'titulo do trabalho'],
        'eixo' => ['eixo'],
        'natureza' => ['natureza'],
        'autor_nome' => ['autor principal - nome', 'nome completo do autor', 'nome do autor'],
        'autor_cpf' => ['autor principal - cpf', 'cpf do autor'],
        'autor_email' => ['autor principal - e-mail', 'e-mail do autor', 'email do autor'],
        'telefone' => ['telefone'],
        'autor_vinculo' => ['autor principal - instituição', 'autor principal - instituicao', 'vínculo institucional do autor', 'vinculo institucional do autor'],
        'coautor_nome' => ['coautor - nome', 'nome completo do coautor', 'nome do coautor'],
        'coautor_cpf' => ['coautor - cpf', 'cpf do coautor'],
        'coautor_email' => ['coautor - e-mail', 'e-mail do coautor', 'email do coautor'],
        'coautor_vinculo' => ['coautor - instituição', 'coautor - instituicao', 'vínculo institucional do coautor', 'vinculo institucional do coautor'],
        'arquivo_avaliacao' => ['versão para avaliação', 'versao para avaliacao'],
        'arquivo_publicacao' => ['versão para publicação', 'versao para publicacao'],
    ];

    private static $obrigatorios = ['carimbo', 'titulo', 'autor_nome', 'autor_cpf', 'autor_email', 'arquivo_avaliacao', 'arquivo_publicacao'];

    private $config;
    private $trabalhos;
    private $autores;
    private $eixos;
    private $naturezas;
    private $usuarios;
    private $usuarioPerfil;
    private $perfis;
    private $tokens;
    private $avaliadores;
    private $termos;
    private $aceites;
    private $comunicacoes;

    public function __construct()
    {
        $this->config = new TrabalhoConfigRepository();
        $this->trabalhos = new TrabalhoRepository();
        $this->autores = new TrabalhoAutorRepository();
        $this->eixos = new TrabalhoEixoTematicoRepository();
        $this->naturezas = new TrabalhoNaturezaRepository();
        $this->usuarios = new UsuarioRepository();
        $this->usuarioPerfil = new UsuarioPerfilRepository();
        $this->perfis = new PerfilRepository();
        $this->tokens = new TokenSenhaRepository();
        $this->avaliadores = new TrabalhoAvaliadorRepository();
        $this->termos = new EventoTrabalhoTermoRepository();
        $this->aceites = new TrabalhoTermoAceiteRepository();
        $this->comunicacoes = new EventoComunicacaoRepository();
    }

    /**
     * Sugestao de mapeamento a partir do cabecalho real: casa cada campo
     * conhecido com a primeira coluna cujo rotulo contenha uma das pistas.
     * Serve so' para o operador conferir e corrigir; nada e' importado a
     * partir do palpite sem ele confirmar no arquivo de mapeamento.
     */
    public static function sugerirMapa(array $cabecalho)
    {
        $mapa = [];
        $usadas = [];

        foreach (self::$camposConhecidos as $campo => $pistas) {
            foreach ($pistas as $pista) {
                foreach ($cabecalho as $coluna) {
                    if (in_array($coluna, $usadas, true)) {
                        continue;
                    }

                    if (mb_strpos(self::normalizar($coluna), self::normalizar($pista)) !== false) {
                        $mapa[$campo] = $coluna;
                        $usadas[] = $coluna;
                        break 2;
                    }
                }
            }

            if (!isset($mapa[$campo])) {
                $mapa[$campo] = null;
            }
        }

        return $mapa;
    }

    public static function colunasNaoMapeadas(array $cabecalho, array $mapa)
    {
        $mapeadas = array_filter(array_values($mapa));

        return array_values(array_filter($cabecalho, function ($coluna) use ($mapeadas) {
            return !in_array($coluna, $mapeadas, true);
        }));
    }

    public static function obrigatoriosFaltando(array $mapa)
    {
        return array_values(array_filter(self::$obrigatorios, function ($campo) use ($mapa) {
            return empty($mapa[$campo]);
        }));
    }

    /**
     * Le a planilha e devolve cada linha ja classificada, sem gravar nada.
     * E' o que a execucao em simulacao imprime, e tambem o que a execucao
     * confirmada percorre: as duas enxergam exatamente a mesma decisao.
     */
    public function analisar($eventoId, array $linhas, array $mapa, $pastaArquivos)
    {
        $config = $this->config->buscarPorEvento($eventoId);

        if ($config === null) {
            throw new \RuntimeException('Evento sem configuração de Trabalhos: rode as migrations antes de importar.');
        }

        $extensoes = $this->config->extensoesEditavelHabilitadas($eventoId);
        $limiteBytes = ((int) $config['tamanho_maximo_mb']) * 1024 * 1024;
        $prazoFinal = $config['data_fim_submissao'];

        $itens = [];

        foreach ($linhas as $indice => $linha) {
            $item = [
                'linha' => $indice + 2,
                'situacao' => 'aceito',
                'motivos' => [],
                'dados' => $this->extrairDados($linha, $mapa),
            ];

            $item['referencia'] = $this->montarReferencia($item['dados']);
            $itens[] = $this->validarItem($item, $eventoId, $extensoes, $limiteBytes, $prazoFinal, $pastaArquivos);
        }

        $itens = $this->aplicarUltimaSubmissaoValida($itens);

        return $itens;
    }

    /**
     * Grava os itens aceitos. Cada trabalho vai numa transacao propria: uma
     * linha problematica no meio do lote nunca deixa outra pela metade nem
     * derruba a importacao inteira.
     *
     * $enfileirarConvites: quando falso (padrao), nada e' enfileirado e
     * nenhum e-mail chega a existir - e' assim que a simulacao de
     * atualizacao roda com dados reais de producao sem avisar ninguem.
     */
    public function importar($eventoId, array $itens, $enfileirarConvites, $usuarioResponsavelId, $textoDeclaracao)
    {
        $relatorio = ['gravados' => 0, 'falhas' => [], 'convites_novos' => [], 'convites_existentes' => []];

        foreach ($itens as $item) {
            if ($item['situacao'] !== 'aceito') {
                continue;
            }

            try {
                $resultado = $this->gravarItem($eventoId, $item, $textoDeclaracao);
                $relatorio['gravados']++;

                if ($resultado['conta_nova']) {
                    $relatorio['convites_novos'][] = $resultado['destinatario'];
                } else {
                    $relatorio['convites_existentes'][] = $resultado['destinatario'];
                }
            } catch (\Throwable $e) {
                $relatorio['falhas'][] = ['linha' => $item['linha'], 'erro' => $e->getMessage()];
            }
        }

        if ($enfileirarConvites) {
            $this->enfileirarConvites($eventoId, $usuarioResponsavelId, $relatorio['convites_novos'], $relatorio['convites_existentes']);
        }

        return $relatorio;
    }

    private function gravarItem($eventoId, array $item, $textoDeclaracao)
    {
        $dados = $item['dados'];
        $cpfAutor = CpfValidador::apenasDigitos($dados['autor_cpf']);
        $email = mb_strtolower($dados['autor_email']);

        $usuario = $this->usuarios->buscarPorEmail($email);
        $contaNova = $usuario === null;
        $tokenSenhaId = null;

        $pdo = Database::conexao();
        $pdo->beginTransaction();

        try {
            if ($contaNova) {
                $usuarioId = $this->usuarios->criarAprovadoSemSenha($dados['autor_nome'], $email);
            } else {
                $usuarioId = (int) $usuario['id'];

                if ($usuario['status'] !== 'aprovado') {
                    $this->usuarios->atualizarStatus($usuarioId, 'aprovado');
                }
            }

            $perfilInscrito = $this->perfis->buscarPorChave('inscrito');

            if ($perfilInscrito !== null && !$this->perfis->possuiPerfil($usuarioId, $perfilInscrito['id'], null)) {
                $this->perfis->atribuir($usuarioId, $perfilInscrito['id'], null);
            }

            // Dados da pessoa moram em usuarios_perfil (fonte unica, Fase
            // 49B): preenche o que estiver vazio, nunca sobrescreve o que a
            // propria pessoa ja cadastrou.
            $this->usuarioPerfil->atualizarParcial($usuarioId, array_filter([
                'documento' => $cpfAutor,
                'tipo_documento' => 'CPF',
                'cargo' => $dados['autor_vinculo'] !== '' ? $dados['autor_vinculo'] : null,
            ]));

            $trabalhoId = $this->trabalhos->criar([
                'evento_id' => $eventoId,
                'eixo_tematico_id' => $item['eixo_id'],
                'natureza_id' => $item['natureza_id'],
                'titulo' => $dados['titulo'],
                'telefone_contato' => $dados['telefone'] !== '' ? $dados['telefone'] : null,
                'metodo_submissao' => 'documento_editavel',
                'origem' => 'canal_alternativo',
                'origem_referencia' => $item['referencia'],
                'submetido_em' => $item['data_envio'],
            ]);

            $caminhoAvaliacao = TrabalhoArquivoValidador::salvarArquivoLocal(
                $item['arquivos']['arquivo_avaliacao']['caminho'],
                $item['arquivos']['arquivo_avaliacao']['extensao'],
                $trabalhoId
            );
            $caminhoPublicacao = TrabalhoArquivoValidador::salvarArquivoLocal(
                $item['arquivos']['arquivo_publicacao']['caminho'],
                $item['arquivos']['arquivo_publicacao']['extensao'],
                $trabalhoId
            );
            $this->trabalhos->atualizarArquivos($trabalhoId, $caminhoAvaliacao, $caminhoPublicacao);

            $this->autores->inserir(
                $trabalhoId,
                true,
                $usuarioId,
                $dados['autor_nome'],
                $cpfAutor,
                $email,
                $dados['autor_vinculo'] !== '' ? $dados['autor_vinculo'] : null,
                null
            );

            if ($dados['coautor_nome'] !== '') {
                $this->autores->inserir(
                    $trabalhoId,
                    false,
                    null,
                    $dados['coautor_nome'],
                    CpfValidador::apenasDigitos($dados['coautor_cpf']),
                    mb_strtolower($dados['coautor_email']),
                    $dados['coautor_vinculo'] !== '' ? $dados['coautor_vinculo'] : null,
                    null
                );
            }

            // O aceite registrado e' a declaracao original do canal
            // alternativo, com a data e a hora do envio original, nunca a
            // data da importacao.
            $this->aceites->registrar($trabalhoId, [[
                'termo_id' => null,
                'rotulo' => 'Declaração do formulário de submissão (canal alternativo)',
                'texto_html' => $textoDeclaracao,
                'origem' => 'canal_alternativo',
                'aceito_em' => $item['data_envio'],
            ]]);

            if ($contaNova) {
                $this->tokens->criar($usuarioId, 'definir', 168);
                $token = $this->tokens->maisRecentePorUsuarioETipo($usuarioId, 'definir');
                $tokenSenhaId = $token !== null ? (int) $token['id'] : null;
            }

            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();

            throw $e;
        }

        return [
            'conta_nova' => $contaNova,
            'destinatario' => [
                'usuario_id' => $usuarioId,
                'email' => $email,
                'nome' => $dados['autor_nome'],
                'token_senha_id' => $tokenSenhaId,
            ],
        ];
    }

    /**
     * Uma campanha por tipo de texto, ambas na fila comum: quem processa e'
     * o agendador, 10 por execucao. Nenhum e-mail sai daqui.
     */
    private function enfileirarConvites($eventoId, $usuarioResponsavelId, array $novos, array $existentes)
    {
        if (!empty($novos)) {
            $this->comunicacoes->criarCampanhaAvulsa([
                'evento_id' => $eventoId,
                'autor_usuario_id' => $usuarioResponsavelId,
                'tipo' => 'convite_autor_importado',
                'assunto' => 'Convite de acesso aos autores importados',
                'corpo_html' => '<p>Convite de acesso com endereço para definir senha, montado individualmente no envio.</p>',
            ], $novos);
        }

        if (!empty($existentes)) {
            $this->comunicacoes->criarCampanhaAvulsa([
                'evento_id' => $eventoId,
                'autor_usuario_id' => $usuarioResponsavelId,
                'tipo' => 'aviso_autor_importado',
                'assunto' => 'Aviso aos autores importados que já tinham conta',
                'corpo_html' => '<p>Aviso de trabalho registrado, montado individualmente no envio.</p>',
            ], $existentes);
        }
    }

    private function extrairDados(array $linha, array $mapa)
    {
        $dados = [];

        foreach ($mapa as $campo => $coluna) {
            $dados[$campo] = ($coluna !== null && isset($linha[$coluna])) ? trim((string) $linha[$coluna]) : '';
        }

        if ($dados['autor_email'] === '' && !empty($dados['email_resposta'])) {
            $dados['autor_email'] = $dados['email_resposta'];
        }

        return $dados;
    }

    /**
     * Referencia estavel da resposta de origem: carimbo mais e-mail. E' o
     * que impede importar duas vezes a mesma linha, mesmo que a planilha
     * seja exportada de novo depois.
     */
    private function montarReferencia(array $dados)
    {
        return 'formulario_externo|' . $dados['carimbo'] . '|' . mb_strtolower($dados['autor_email']);
    }

    private function validarItem(array $item, $eventoId, array $extensoes, $limiteBytes, $prazoFinal, $pastaArquivos)
    {
        $dados = $item['dados'];

        if ($dados['autor_email'] === '') {
            $item['situacao'] = 'sem_email';
            $item['motivos'][] = 'Resposta sem e-mail: sem ele não há como convidar nem vincular o trabalho a uma conta.';

            return $item;
        }

        if ($this->trabalhos->buscarPorOrigemReferencia($eventoId, $item['referencia']) !== null) {
            $item['situacao'] = 'ja_importado';
            $item['motivos'][] = 'Esta resposta já foi importada numa execução anterior.';

            return $item;
        }

        foreach (self::$obrigatorios as $campo) {
            if ($dados[$campo] === '') {
                $item['situacao'] = 'recusado';
                $item['motivos'][] = 'Campo obrigatório do item 5.4 do edital ausente: ' . $campo . '.';
            }
        }

        if (!CpfValidador::valido($dados['autor_cpf'])) {
            $item['situacao'] = 'recusado';
            $item['motivos'][] = 'CPF do autor inválido.';
        }

        if ($dados['coautor_nome'] !== '' && !CpfValidador::valido($dados['coautor_cpf'])) {
            $item['situacao'] = 'recusado';
            $item['motivos'][] = 'CPF do coautor inválido.';
        }

        $item['data_envio'] = $this->converterCarimbo($dados['carimbo']);

        if ($item['data_envio'] === null) {
            $item['situacao'] = 'recusado';
            $item['motivos'][] = 'Carimbo de data e hora em formato não reconhecido: ' . $dados['carimbo'];
        } elseif ($prazoFinal !== null && $item['data_envio'] > $prazoFinal) {
            $item['situacao'] = 'fora_prazo';
            $item['motivos'][] = 'Enviado depois do prazo final de submissão (' . $prazoFinal . ').';
        }

        if (isset($item['dados']['declaracao']) && trim($item['dados']['declaracao']) === '') {
            $item['situacao'] = 'recusado';
            $item['motivos'][] = 'Resposta sem a declaração de aceite marcada.';
        }

        if ($this->avaliadores->emailJaEhAvaliadorAtivo($eventoId, $dados['autor_email'])) {
            $item['situacao'] = 'recusado';
            $item['motivos'][] = 'Este e-mail já é avaliador avulso deste evento (exclusão mútua autor/avaliador).';
        }

        $item['arquivos'] = [];

        foreach (['arquivo_avaliacao', 'arquivo_publicacao'] as $campo) {
            $resultado = $this->localizarArquivo($dados[$campo], $pastaArquivos, $extensoes, $limiteBytes);

            if ($resultado['erro'] !== null) {
                $item['situacao'] = 'recusado';
                $item['motivos'][] = $campo . ': ' . $resultado['erro'];
                continue;
            }

            $item['arquivos'][$campo] = $resultado;
        }

        $item['eixo_id'] = $this->casarCatalogo($this->eixos->listarPorEvento($eventoId), $dados['eixo']);
        $item['natureza_id'] = $this->casarCatalogo($this->naturezas->listarPorEvento($eventoId), $dados['natureza']);

        if ($dados['eixo'] !== '' && $item['eixo_id'] === null) {
            $item['motivos'][] = 'Eixo temático não reconhecido no cadastro do evento: ' . $dados['eixo'];
        }

        if ($dados['natureza'] !== '' && $item['natureza_id'] === null) {
            $item['motivos'][] = 'Natureza não reconhecida no cadastro do evento: ' . $dados['natureza'];
        }

        return $item;
    }

    /**
     * Item 5.9 do edital: quando a mesma pessoa aparece mais de uma vez,
     * vale a ultima resposta dentro do prazo; as anteriores ficam
     * registradas como descartadas, nunca sumem em silencio. A pessoa e'
     * identificada pelo CPF, que tambem e' como o item 3.4 conta
     * participacao.
     */
    private function aplicarUltimaSubmissaoValida(array $itens)
    {
        $ultimoPorCpf = [];

        foreach ($itens as $indice => $item) {
            if ($item['situacao'] !== 'aceito') {
                continue;
            }

            $cpf = CpfValidador::apenasDigitos($item['dados']['autor_cpf']);

            if (!isset($ultimoPorCpf[$cpf])) {
                $ultimoPorCpf[$cpf] = $indice;
                continue;
            }

            $anterior = $ultimoPorCpf[$cpf];

            if ($itens[$indice]['data_envio'] >= $itens[$anterior]['data_envio']) {
                $itens[$anterior]['situacao'] = 'descartado';
                $itens[$anterior]['motivos'][] = 'Item 5.9: existe envio mais recente do mesmo autor dentro do prazo (linha ' . $itens[$indice]['linha'] . ').';
                $ultimoPorCpf[$cpf] = $indice;
            } else {
                $itens[$indice]['situacao'] = 'descartado';
                $itens[$indice]['motivos'][] = 'Item 5.9: existe envio mais recente do mesmo autor dentro do prazo (linha ' . $itens[$anterior]['linha'] . ').';
            }
        }

        return $itens;
    }

    private function casarCatalogo(array $opcoes, $valor)
    {
        if ($valor === '') {
            return null;
        }

        $alvo = self::normalizar($valor);

        foreach ($opcoes as $opcao) {
            if (self::normalizar($opcao['nome']) === $alvo) {
                return (int) $opcao['id'];
            }
        }

        foreach ($opcoes as $opcao) {
            $nome = self::normalizar($opcao['nome']);

            if (mb_strpos($alvo, $nome) !== false || mb_strpos($nome, $alvo) !== false) {
                return (int) $opcao['id'];
            }
        }

        return null;
    }

    /**
     * O anexo chega pelo endereco do Drive na planilha; o arquivo em si vem
     * na pasta exportada, renomeado pelo identificador daquele endereco
     * (ver o roteiro de exportacao no DeployFase51.md). Casar por
     * identificador, e nao por nome, e' o que evita trocar um arquivo pelo
     * de outra pessoa.
     */
    private function localizarArquivo($endereco, $pastaArquivos, array $extensoes, $limiteBytes)
    {
        $resultado = ['erro' => null, 'caminho' => null, 'extensao' => null];

        if (trim((string) $endereco) === '') {
            $resultado['erro'] = 'anexo não informado na resposta.';

            return $resultado;
        }

        if (!preg_match('#(?:id=|/d/)([A-Za-z0-9_-]{10,})#', $endereco, $partes)) {
            $resultado['erro'] = 'não foi possível extrair o identificador do arquivo do endereço: ' . $endereco;

            return $resultado;
        }

        $identificador = $partes[1];
        $encontrados = glob(rtrim($pastaArquivos, '/') . '/' . $identificador . '.*');

        if (empty($encontrados)) {
            $resultado['erro'] = 'arquivo ' . $identificador . ' não encontrado na pasta de anexos.';

            return $resultado;
        }

        $caminho = $encontrados[0];
        $extensao = mb_strtolower(pathinfo($caminho, PATHINFO_EXTENSION));

        if (!in_array($extensao, $extensoes, true)) {
            $resultado['erro'] = 'extensão não permitida pelo edital (' . $extensao . ').';

            return $resultado;
        }

        if (filesize($caminho) > $limiteBytes) {
            $resultado['erro'] = 'arquivo acima do tamanho máximo configurado.';

            return $resultado;
        }

        $resultado['caminho'] = $caminho;
        $resultado['extensao'] = $extensao;

        return $resultado;
    }

    /**
     * O Google exporta o carimbo em formato local (dd/mm/aaaa hh:mm:ss).
     * Sem conversao explicita, strtotime() leria como mes/dia e trocaria a
     * ordem de chegada, justamente o que desempata pelo item 7.6.
     */
    private function converterCarimbo($valor)
    {
        $valor = trim($valor);

        if ($valor === '') {
            return null;
        }

        $formatos = ['d/m/Y H:i:s', 'd/m/Y H:i', 'Y-m-d H:i:s', 'Y-m-d H:i'];

        foreach ($formatos as $formato) {
            $data = \DateTime::createFromFormat($formato, $valor);

            if ($data !== false) {
                return $data->format('Y-m-d H:i:s');
            }
        }

        return null;
    }

    private static function normalizar($texto)
    {
        $texto = mb_strtolower(trim((string) $texto));
        $de = ['á', 'à', 'â', 'ã', 'ä', 'é', 'ê', 'è', 'í', 'ì', 'ó', 'ô', 'õ', 'ò', 'ú', 'ù', 'ü', 'ç'];
        $para = ['a', 'a', 'a', 'a', 'a', 'e', 'e', 'e', 'i', 'i', 'o', 'o', 'o', 'o', 'u', 'u', 'u', 'c'];

        return str_replace($de, $para, $texto);
    }
}
