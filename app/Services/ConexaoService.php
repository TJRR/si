<?php

namespace App\Services;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Core\Auditoria;
use App\Core\Database;
use App\Repositories\ConexaoRepository;

/**
 * Regra de negocio da conexao entre dois participantes do mesmo Evento.
 * Fica fora do controlador porque a gravacao precisa de transacao propria,
 * e fora do repositorio porque a transacao cobre contagem e gravacao juntas.
 */
class ConexaoService
{
    private $conexoes;

    public function __construct()
    {
        $this->conexoes = new ConexaoRepository();
    }

    /**
     * A conexao so' pontua enquanto o evento esta acontecendo (decisao do
     * dono na Fase 55, sem campo novo: sao as datas ja cadastradas em
     * eventos). As colunas sao de data, entao o ultimo dia conta inteiro.
     */
    public function dentroDaJanela(array $evento)
    {
        if (empty($evento['data_inicio']) || empty($evento['data_fim'])) {
            return true;
        }

        $hoje = date('Y-m-d');

        return $hoje >= substr((string) $evento['data_inicio'], 0, 10)
            && $hoje <= substr((string) $evento['data_fim'], 0, 10);
    }

    /**
     * Grava a conexao creditando os DOIS lados numa transacao so'.
     *
     * As duas inscricoes sao bloqueadas em ordem crescente de numero: sem
     * ordem fixa, duas pessoas lendo o cracha uma da outra ao mesmo tempo
     * travariam uma na outra. O teto e' recontado ja' sob o bloqueio, nunca
     * antes, senao duas leituras simultaneas da mesma pessoa passariam do
     * limite.
     *
     * Devolve ['conexao' => linha, 'pontos_leitor' => int, 'ja_existia' =>
     * bool]. Leitura simultanea que perca a corrida cai no erro 23000 da
     * chave unica e volta como "ja existia", sem duplicar nada.
     */
    public function conectar(array $evento, array $config, array $inscricaoLeitor, array $inscricaoLida)
    {
        $pdo = Database::conexao();
        list($menor, $maior) = ConexaoRepository::parCanonico($inscricaoLeitor['id'], $inscricaoLida['id']);

        $pdo->beginTransaction();

        try {
            $stmt = $pdo->prepare('SELECT id FROM evento_inscricoes WHERE id IN (:menor, :maior) ORDER BY id ASC FOR UPDATE');
            $stmt->execute(['menor' => $menor, 'maior' => $maior]);
            $stmt->fetchAll();

            $existente = $this->conexoes->buscarPorPar($menor, $maior);

            if ($existente !== null) {
                $pdo->commit();

                return [
                    'conexao' => $existente,
                    'pontos_leitor' => $this->pontosDoLado($existente, (int) $inscricaoLeitor['id']),
                    'ja_existia' => true,
                ];
            }

            $pontosMenor = $this->pontosParaLado($menor, $config);
            $pontosMaior = $this->pontosParaLado($maior, $config);

            // Fase 58: depois do encerramento da gincana a conexao continua
            // sendo registrada (ela vale como contato entre as pessoas), mas
            // nao pontua para nenhum dos lados.
            if (GamificacaoService::encerrada((int) $evento['id'])) {
                $pontosMenor = 0;
                $pontosMaior = 0;
            }

            $id = $this->conexoes->inserirNaTransacaoAtual(
                (int) $evento['id'],
                $menor,
                $maior,
                (int) $inscricaoLeitor['id'],
                $pontosMenor,
                $pontosMaior
            );

            $pdo->commit();
        } catch (\PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            if ($e->getCode() === '23000') {
                $existente = $this->conexoes->buscarPorPar($menor, $maior);

                if ($existente !== null) {
                    return [
                        'conexao' => $existente,
                        'pontos_leitor' => $this->pontosDoLado($existente, (int) $inscricaoLeitor['id']),
                        'ja_existia' => true,
                    ];
                }
            }

            throw $e;
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            throw $e;
        }

        // Auditoria depois do commit, e so' com numeros: nome, e-mail e
        // telefone das duas pessoas nunca entram na trilha de auditoria.
        Auditoria::registrar('registrar_conexao', 'evento_conexoes', $id, null, [
            'evento_id' => (int) $evento['id'],
            'inscricao_menor_id' => $menor,
            'inscricao_maior_id' => $maior,
            'iniciador_inscricao_id' => (int) $inscricaoLeitor['id'],
            'pontos_creditados_menor' => $pontosMenor,
            'pontos_creditados_maior' => $pontosMaior,
        ]);

        $conexao = $this->conexoes->buscarPorPar($menor, $maior);

        return [
            'conexao' => $conexao,
            'pontos_leitor' => $this->pontosDoLado($conexao, (int) $inscricaoLeitor['id']),
            'ja_existia' => false,
        ];
    }

    /**
     * Pontos que ESTE lado recebe agora: zero quando o evento nao credita
     * ponto por conexao, zero quando a pessoa ja atingiu o teto, e o valor
     * configurado nos demais casos. Chamado de dentro da transacao.
     */
    private function pontosParaLado($inscricaoId, array $config)
    {
        $pontos = (int) $config['pontos_por_conexao'];
        $teto = (int) $config['teto_conexoes_pontuadas'];

        if ($pontos <= 0) {
            return 0;
        }

        if ($teto > 0 && $this->conexoes->contarPontuadasDaInscricao($inscricaoId) >= $teto) {
            return 0;
        }

        return $pontos;
    }

    private function pontosDoLado(array $conexao = null, $inscricaoId = 0)
    {
        if ($conexao === null) {
            return 0;
        }

        return (int) $conexao['inscricao_menor_id'] === (int) $inscricaoId
            ? (int) $conexao['pontos_creditados_menor']
            : (int) $conexao['pontos_creditados_maior'];
    }
}
