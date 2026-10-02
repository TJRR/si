<?php

namespace App\Services;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Core\Auditoria;
use App\Core\Database;
use App\Repositories\DivulgacaoComprovacaoRepository;
use App\Repositories\DivulgacaoConfigRepository;

/**
 * Fase 56: regra de negocio da comprovacao de divulgacao. Fica fora do
 * controlador porque a gravacao precisa de transacao propria, e fora do
 * repositorio porque a transacao cobre contagem de teto e gravacao juntas,
 * no molde de ConexaoService (Fase 55).
 *
 * Diferenca central em relacao as fases anteriores: aqui nao ha leitura de
 * codigo, e sim envio de prova pela propria pessoa, com credito automatico
 * por decisao do dono. A conferencia humana acontece depois, quando ha
 * suspeita, pela anulacao com justificativa na tela administrativa.
 */
class DivulgacaoService
{
    /**
     * Parametros que so' servem para rastrear quem clicou, e que mudam a
     * cada compartilhamento da MESMA publicacao. Sem retira-los, reenviar o
     * mesmo endereco com "?utm_source=..." colado pelo botao de
     * compartilhar produziria resumo diferente e furaria a trava de prova
     * repetida. Os demais parametros ficam: youtube.com/watch?v=XYZ perde a
     * identidade da publicacao sem o "v".
     */
    const PARAMETROS_DE_RASTREAMENTO = ['fbclid', 'igshid', 'igsh', 'si', 'rdt', 'share_id', 'gclid', 'mibextid'];

    const TIPOS_ACAO = ['publicacao', 'acompanhar'];

    private $comprovacoes;

    public function __construct()
    {
        $this->comprovacoes = new DivulgacaoComprovacaoRepository();
    }

    /**
     * A comprovacao so' pontua dentro da janela que o ADMINISTRADOR
     * escreveu. Cada extremo limita por conta propria: data de inicio em
     * branco nao limita o comeco, data de fim em branco nao limita o fim, e
     * as duas em branco significam sem limite de data nenhum, valendo
     * enquanto o modulo estiver ativo.
     *
     * Fase 58 (defeito corrigido): antes, campo em branco caia na data
     * correspondente do evento, e a tela chegava a anunciar "comprovacoes
     * valem nas datas do evento" ao lado do selo de modulo desativado. Quem
     * define o periodo de liberacao e' o Administrador; o sistema nunca
     * assume um periodo que ninguem escreveu.
     *
     * As colunas sao de data, entao o ultimo dia conta inteiro. $evento fica
     * na assinatura para manter a mesma forma de chamada de PesquisaService
     * e ConexaoService.
     */
    public function dentroDaJanela(array $evento, array $config)
    {
        $hoje = date('Y-m-d');

        if (!empty($config['data_inicio']) && $hoje < $config['data_inicio']) {
            return false;
        }

        if (!empty($config['data_fim']) && $hoje > $config['data_fim']) {
            return false;
        }

        return true;
    }

    /**
     * Texto do periodo escrito pelo Administrador, ou '' quando ele nao
     * limitou por data. Quem exibe acrescenta a preposicao que couber: o
     * texto ja' vem com "de ... a ...", "a partir de ..." ou "ate' ...".
     */
    public function janelaTexto(array $evento, array $config)
    {
        $inicio = !empty($config['data_inicio']) ? $config['data_inicio'] : null;
        $fim = !empty($config['data_fim']) ? $config['data_fim'] : null;

        if ($inicio !== null && $fim !== null) {
            return 'de ' . date('d/m/Y', strtotime($inicio)) . ' a ' . date('d/m/Y', strtotime($fim));
        }

        if ($inicio !== null) {
            return 'a partir de ' . date('d/m/Y', strtotime($inicio));
        }

        if ($fim !== null) {
            return 'até ' . date('d/m/Y', strtotime($fim));
        }

        return '';
    }

    /**
     * Endereco de publicacao daquela rede, normalizado, ou null quando nao
     * da' para interpretar com seguranca.
     *
     * Confere o dominio contra UsuarioPerfilRepository::REDES_ENDERECO, que
     * e' a mesma fonte usada pelo cadastro de "Meu Perfil" na Fase 55 (lida,
     * nunca alterada), e passa por linkHttpValido() no fim, que e' o que
     * barra entrada do tipo "javascript:". A normalizacao existe para o
     * resumo criptografico: sem ela, a mesma publicacao com uma barra final
     * a mais seria tratada como prova diferente.
     */
    public function normalizarEndereco($rede, $valor)
    {
        $valor = is_string($valor) ? trim($valor) : '';

        // Fase 58: os dominios vem da lista de redes da Divulgacao
        // (DivulgacaoConfigRepository::dominiosDaRede()), que continua lendo
        // UsuarioPerfilRepository::REDES_ENDERECO para as redes do perfil.
        // Lista vazia: a rede nao aceita endereco.
        $dominios = DivulgacaoConfigRepository::dominiosDaRede($rede);

        if ($valor === '' || $dominios === []) {
            return null;
        }

        if (preg_match('#^https?://#i', $valor) !== 1) {
            $valor = 'https://' . ltrim($valor, '/');
        }

        $partes = parse_url($valor);

        if ($partes === false || empty($partes['host'])) {
            return null;
        }

        $host = strtolower($partes['host']);

        if (strpos($host, 'www.') === 0) {
            $host = substr($host, 4);
        }

        $dominioConfere = false;
        foreach ($dominios as $dominio) {
            if ($host === $dominio || substr($host, -(strlen($dominio) + 1)) === '.' . $dominio) {
                $dominioConfere = true;
                break;
            }
        }

        if (!$dominioConfere) {
            return null;
        }

        $caminho = isset($partes['path']) ? rtrim($partes['path'], '/') : '';
        $consulta = $this->consultaNormalizada(isset($partes['query']) ? $partes['query'] : '');

        $endereco = 'https://' . $host . $caminho . ($consulta !== '' ? '?' . $consulta : '');

        return linkHttpValido($endereco) ? $endereco : null;
    }

    public function resumoDoEndereco($endereco)
    {
        return hash('sha256', $endereco);
    }

    /**
     * Grava a comprovacao creditando os pontos numa transacao so'.
     *
     * A inscricao de quem envia e' bloqueada com leitura para atualizacao, o
     * que serializa os envios da mesma pessoa: sem isso, dois envios
     * simultaneos passariam do teto e dois "acompanhar" da mesma rede
     * entrariam juntos. Os dois tetos e a unicidade de "acompanhar" sao
     * reconferidos ja' sob o bloqueio, nunca antes.
     *
     * Devolve ['id' => int, 'pontos' => int, 'motivo_sem_pontos' => string].
     */
    public function registrar(array $evento, array $inscricao, array $configRede, array $dados)
    {
        // Fase 58: depois do encerramento da gincana, divulgacao nao
        // registra nada. O controlador ja' recusa antes; esta conferencia e'
        // a defesa de quem chamar o servico por outro caminho.
        if (GamificacaoService::encerrada((int) $evento['id'])) {
            return ['id' => null, 'pontos' => 0, 'motivo_sem_pontos' => 'gincana_encerrada'];
        }

        $pdo = Database::conexao();
        $motivo = '';
        $pdo->beginTransaction();

        try {
            $stmt = $pdo->prepare('SELECT id FROM evento_inscricoes WHERE id = :id FOR UPDATE');
            $stmt->execute(['id' => (int) $inscricao['id']]);
            $stmt->fetch();

            if ($dados['tipo_acao'] === 'acompanhar'
                && $this->comprovacoes->existeAcompanhar((int) $inscricao['id'], $dados['rede'])) {
                $pdo->rollBack();

                return ['id' => null, 'pontos' => 0, 'motivo_sem_pontos' => 'acompanhar_repetido'];
            }

            $pontos = $this->pontosPara($inscricao, $configRede, $dados['tipo_acao'], $dados['rede'], $motivo);

            $id = $this->comprovacoes->inserirNaTransacaoAtual([
                'evento_id' => (int) $evento['id'],
                'evento_inscricao_id' => (int) $inscricao['id'],
                'rede' => $dados['rede'],
                'rede_informada' => isset($dados['rede_informada']) ? $dados['rede_informada'] : null,
                'tipo_acao' => $dados['tipo_acao'],
                'endereco' => isset($dados['endereco']) ? $dados['endereco'] : null,
                'endereco_hash' => isset($dados['endereco_hash']) ? $dados['endereco_hash'] : null,
                'arquivo_path' => isset($dados['arquivo_path']) ? $dados['arquivo_path'] : null,
                'arquivo_nome' => isset($dados['arquivo_nome']) ? $dados['arquivo_nome'] : null,
                'arquivo_sha256' => isset($dados['arquivo_sha256']) ? $dados['arquivo_sha256'] : null,
                'arquivo_bytes' => isset($dados['arquivo_bytes']) ? $dados['arquivo_bytes'] : null,
                'pontos_creditados' => $pontos,
            ]);

            $pdo->commit();
        } catch (\PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            if ($e->getCode() === '23000') {
                return ['id' => null, 'pontos' => 0, 'motivo_sem_pontos' => 'prova_repetida'];
            }

            throw $e;
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            throw $e;
        }

        // Auditoria depois da confirmacao, e so' com numeros, rede e tipo: o
        // endereco da publicacao e o nome do arquivo nunca entram na trilha.
        Auditoria::registrar('registrar_comprovacao_divulgacao', 'evento_divulgacao_comprovacoes', $id, null, [
            'evento_id' => (int) $evento['id'],
            'evento_inscricao_id' => (int) $inscricao['id'],
            'rede' => $dados['rede'],
            'tipo_acao' => $dados['tipo_acao'],
            'pontos_creditados' => $pontos,
        ]);

        return ['id' => $id, 'pontos' => $pontos, 'motivo_sem_pontos' => $motivo];
    }

    /**
     * Pontos que esta comprovacao recebe agora: zero quando a rede nao
     * credita aquele tipo de acao (combinacao legitima: registra sem
     * pontuar) e zero quando um dos tetos ja foi atingido. Recontagem feita
     * ja' dentro da transacao, com linhas anuladas fora da conta.
     */
    private function pontosPara(array $inscricao, array $configRede, $tipoAcao, $rede, &$motivo)
    {
        $motivo = '';

        if ($tipoAcao === 'acompanhar') {
            return (int) $configRede['acompanhar_pontos'];
        }

        $pontos = (int) $configRede['publicacao_pontos'];

        if ($pontos <= 0) {
            return 0;
        }

        $tetoDia = (int) $configRede['publicacao_teto_dia'];

        if ($tetoDia > 0
            && $this->comprovacoes->contarPublicacoesNoDia((int) $inscricao['id'], $rede, date('Y-m-d')) >= $tetoDia) {
            $motivo = 'teto_dia';

            return 0;
        }

        $tetoEvento = (int) $configRede['publicacao_teto_evento'];

        if ($tetoEvento > 0
            && $this->comprovacoes->contarPublicacoesPontuadasNoEvento((int) $inscricao['id'], $rede) >= $tetoEvento) {
            $motivo = 'teto_evento';

            return 0;
        }

        return $pontos;
    }

    private function consultaNormalizada($consulta)
    {
        if ($consulta === '') {
            return '';
        }

        $pares = [];
        foreach (explode('&', $consulta) as $par) {
            if ($par === '') {
                continue;
            }

            $nome = strpos($par, '=') !== false ? substr($par, 0, strpos($par, '=')) : $par;
            $nomeMinusculo = strtolower($nome);

            if (strpos($nomeMinusculo, 'utm_') === 0 || in_array($nomeMinusculo, self::PARAMETROS_DE_RASTREAMENTO, true)) {
                continue;
            }

            $pares[] = $par;
        }

        sort($pares);

        return implode('&', $pares);
    }
}
