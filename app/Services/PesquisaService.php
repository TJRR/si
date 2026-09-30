<?php

namespace App\Services;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Core\Database;
use App\Repositories\PesquisaRespondenteRepository;
use App\Repositories\PesquisaRespostaRepository;

/**
 * Fase 57: pesquisa de satisfacao do evento.
 *
 * A pesquisa e' ANONIMA por decisao do dono: quem respondeu fica em
 * evento_pesquisa_respondentes (nominal, chaveado por USUARIO desde o bloco
 * E, e' o que impede responder duas vezes e o que habilita o credito), e o
 * conteudo fica em evento_pesquisa_respostas, sem nenhum elo com a pessoa. As duas gravacoes
 * acontecem na mesma transacao para nao existir resposta sem porta nem
 * porta sem resposta, e a auditoria cobre so' a parte nominal.
 *
 * A validacao por tipo de pergunta mora aqui, e nao no controller (ao
 * contrario do formulario de inscricao, EventoInscricaoPublicaController),
 * porque sao quatro tipos, dois deles novos no sistema, e a regra e' a mesma
 * na gravacao e na conferencia.
 */
class PesquisaService
{
    const TIPOS_PERGUNTA = [
        'escala' => 'Escala de 1 a 5',
        'lista_opcoes' => 'Lista de opções, uma escolha',
        'multipla_escolha' => 'Múltipla escolha',
        'texto' => 'Texto livre',
    ];

    const ESCALA_MINIMA = 1;
    const ESCALA_MAXIMA = 5;
    const LIMITE_TEXTO = 2000;

    /**
     * Abaixo deste numero de respostas, a tela de resultado mostra so' o
     * total. Com tres respostas, "anonimo" e' cortesia e nao propriedade: o
     * texto livre identifica sozinho quem escreveu, e a relacao de quem
     * respondeu e' nominal.
     */
    const MINIMO_PARA_EXIBIR = 5;

    private $respondentes;
    private $respostas;

    public function __construct()
    {
        $this->respondentes = new PesquisaRespondenteRepository();
        $this->respostas = new PesquisaRespostaRepository();
    }

    public static function rotuloDoTipo($tipo)
    {
        return isset(self::TIPOS_PERGUNTA[$tipo]) ? self::TIPOS_PERGUNTA[$tipo] : $tipo;
    }

    /**
     * Janela propria da pesquisa; as duas datas em branco fazem valer as
     * datas do evento. As colunas sao de data, entao o ultimo dia conta
     * inteiro. Mesmo desenho de DivulgacaoService (Fase 56).
     */
    public function dentroDaJanela(array $evento, array $config)
    {
        $inicio = $this->inicioDa($evento, $config);
        $fim = $this->fimDa($evento, $config);

        if ($inicio === null || $fim === null) {
            return true;
        }

        $hoje = date('Y-m-d');

        return $hoje >= $inicio && $hoje <= $fim;
    }

    public function janelaTexto(array $evento, array $config)
    {
        $inicio = $this->inicioDa($evento, $config);
        $fim = $this->fimDa($evento, $config);

        if ($inicio === null || $fim === null) {
            return '';
        }

        return date('d/m/Y', strtotime($inicio)) . ' a ' . date('d/m/Y', strtotime($fim));
    }

    /**
     * Confere as respostas enviadas contra as perguntas ativas e devolve
     * ['erros' => [pergunta_id => mensagem], 'linhas' => [...]], com as
     * linhas prontas para gravacao.
     *
     * Multipla escolha vira varias linhas, uma por opcao marcada. Em todos
     * os tipos de opcao, o valor guardado e' a POSICAO na lista cadastrada,
     * a partir de 1, e opcao fora da lista e' descartada, no mesmo espirito
     * da lista de opcoes do formulario de inscricao.
     */
    public function validarRespostas(array $perguntas, array $enviado)
    {
        $erros = [];
        $linhas = [];

        foreach ($perguntas as $pergunta) {
            $id = (int) $pergunta['id'];
            $tipo = $pergunta['tipo'];
            $obrigatoria = !empty($pergunta['obrigatoria']);
            $config = $this->configDa($pergunta);
            $valor = isset($enviado[$id]) ? $enviado[$id] : null;

            if ($tipo === 'escala') {
                $nota = is_scalar($valor) ? (int) $valor : 0;

                if ($nota < self::ESCALA_MINIMA || $nota > self::ESCALA_MAXIMA) {
                    if ($obrigatoria) {
                        $erros[$id] = 'Escolha uma nota de ' . self::ESCALA_MINIMA . ' a ' . self::ESCALA_MAXIMA . '.';
                    }

                    continue;
                }

                $linhas[] = ['pergunta_id' => $id, 'valor_numero' => $nota, 'valor_texto' => null];
                continue;
            }

            if ($tipo === 'lista_opcoes') {
                $posicao = $this->posicaoDaOpcao($config, is_scalar($valor) ? (string) $valor : '');

                if ($posicao === null) {
                    if ($obrigatoria) {
                        $erros[$id] = 'Escolha uma das opções.';
                    }

                    continue;
                }

                $linhas[] = ['pergunta_id' => $id, 'valor_numero' => $posicao, 'valor_texto' => null];
                continue;
            }

            if ($tipo === 'multipla_escolha') {
                $marcadas = is_array($valor) ? $valor : [];
                $posicoes = [];

                foreach ($marcadas as $marcada) {
                    $posicao = $this->posicaoDaOpcao($config, is_scalar($marcada) ? (string) $marcada : '');

                    if ($posicao !== null && !in_array($posicao, $posicoes, true)) {
                        $posicoes[] = $posicao;
                    }
                }

                if ($posicoes === []) {
                    if ($obrigatoria) {
                        $erros[$id] = 'Marque ao menos uma opção.';
                    }

                    continue;
                }

                sort($posicoes);

                foreach ($posicoes as $posicao) {
                    $linhas[] = ['pergunta_id' => $id, 'valor_numero' => $posicao, 'valor_texto' => null];
                }

                continue;
            }

            $texto = is_scalar($valor) ? trim((string) $valor) : '';

            if ($texto === '') {
                if ($obrigatoria) {
                    $erros[$id] = 'Escreva a sua resposta.';
                }

                continue;
            }

            $linhas[] = [
                'pergunta_id' => $id,
                'valor_numero' => 0,
                'valor_texto' => mb_substr($texto, 0, self::LIMITE_TEXTO),
            ];
        }

        return ['erros' => $erros, 'linhas' => $linhas];
    }

    /**
     * Grava a porta e o conteudo na mesma transacao. Devolve true quando
     * gravou e false quando a pessoa ja' havia respondido (erro de chave
     * repetida, estado 23000), que e' o caso de dois envios simultaneos.
     *
     * O identificador do envio e' sorteado aqui e nunca sai para lugar
     * nenhum: ele agrupa as respostas de um preenchimento entre si, sem
     * dizer de quem sao.
     */
    public function registrar(array $evento, $usuarioId, array $linhas)
    {
        $pdo = Database::conexao();
        $pdo->beginTransaction();

        try {
            $this->respondentes->registrarNaTransacaoAtual((int) $evento['id'], $usuarioId);
            $this->respostas->gravarEnvioNaTransacaoAtual(bin2hex(random_bytes(16)), $linhas);
            $pdo->commit();
        } catch (\PDOException $e) {
            $pdo->rollBack();

            if ($e->getCode() === '23000') {
                return false;
            }

            throw $e;
        } catch (\Exception $e) {
            $pdo->rollBack();
            throw $e;
        }

        // Fora da transacao, e so' do fato nominal: a gravacao das respostas
        // nunca e' auditada, porque a trilha carimba usuario e instante e
        // abriria sozinha o elo que as tabelas separam.
        $this->respondentes->auditarResposta((int) $evento['id'], $usuarioId);

        return true;
    }

    public function configDa(array $pergunta)
    {
        if (empty($pergunta['config_json'])) {
            return [];
        }

        $config = json_decode($pergunta['config_json'], true);

        return is_array($config) ? $config : [];
    }

    public function opcoesDa(array $pergunta)
    {
        $config = $this->configDa($pergunta);

        return isset($config['opcoes']) && is_array($config['opcoes']) ? array_values($config['opcoes']) : [];
    }

    /**
     * Posicao da opcao na lista cadastrada, a partir de 1, ou null quando o
     * valor enviado nao esta' na lista. E' o que impede uma opcao forjada de
     * entrar no resultado.
     */
    private function posicaoDaOpcao(array $config, $valor)
    {
        if ($valor === '') {
            return null;
        }

        $opcoes = isset($config['opcoes']) && is_array($config['opcoes']) ? array_values($config['opcoes']) : [];
        $indice = array_search($valor, $opcoes, true);

        return $indice === false ? null : ((int) $indice) + 1;
    }

    private function inicioDa(array $evento, array $config)
    {
        if (!empty($config['data_inicio'])) {
            return $config['data_inicio'];
        }

        return !empty($evento['data_inicio']) ? substr((string) $evento['data_inicio'], 0, 10) : null;
    }

    private function fimDa(array $evento, array $config)
    {
        if (!empty($config['data_fim'])) {
            return $config['data_fim'];
        }

        return !empty($evento['data_fim']) ? substr((string) $evento['data_fim'], 0, 10) : null;
    }
}
