<?php

namespace App\Repositories;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Core\Database;

/**
 * O QUE foi respondido na pesquisa (anonimo). Nenhum metodo deste
 * repositorio recebe, devolve ou consulta identificacao de pessoa, e nenhum
 * chama Auditoria::registrar(): ver Implantar.md, secao 13.16.
 *
 * valor_numero guarda a nota da escala (1 a 5), a POSICAO da opcao escolhida
 * ou marcada (a partir de 1), ou zero no texto livre. Multipla escolha grava
 * uma linha por opcao marcada, todas com o mesmo resposta_uid.
 */
class PesquisaRespostaRepository
{
    /**
     * Grava as respostas de um envio dentro da transacao ja' aberta por
     * PesquisaService::registrar(). $linhas vem na forma
     * [['pergunta_id' => n, 'valor_numero' => n, 'valor_texto' => string|null], ...].
     *
     * respondido_em e' CURDATE(), so' a data: data e hora ao segundo, dos
     * dois lados, seria praticamente uma chave de ligacao com quem
     * respondeu.
     */
    public function gravarEnvioNaTransacaoAtual($respostaUid, array $linhas)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'INSERT INTO evento_pesquisa_respostas (resposta_uid, pergunta_id, valor_numero, valor_texto, respondido_em)
             VALUES (:uid, :pergunta, :valor_numero, :valor_texto, CURDATE())'
        );

        foreach ($linhas as $linha) {
            $stmt->execute([
                'uid' => $respostaUid,
                'pergunta' => (int) $linha['pergunta_id'],
                'valor_numero' => (int) $linha['valor_numero'],
                'valor_texto' => $linha['valor_texto'],
            ]);
        }
    }

    /**
     * Distribuicao de uma pergunta: [valor_numero => quantas]. Serve tanto
     * para a escala (1 a 5) quanto para as opcoes (posicao a partir de 1).
     * Sai direto do indice (pergunta_id, valor_numero).
     */
    public function distribuicao($perguntaId)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'SELECT valor_numero, COUNT(*) AS total
               FROM evento_pesquisa_respostas
              WHERE pergunta_id = :pergunta
              GROUP BY valor_numero
              ORDER BY valor_numero ASC'
        );
        $stmt->execute(['pergunta' => (int) $perguntaId]);

        $distribuicao = [];

        foreach ($stmt->fetchAll() as $linha) {
            $distribuicao[(int) $linha['valor_numero']] = (int) $linha['total'];
        }

        return $distribuicao;
    }

    /**
     * Textos livres de uma pergunta, em ordem aleatoria de identificador de
     * envio - nunca em ordem de chegada, que reconstruiria a fila.
     */
    public function textos($perguntaId)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'SELECT valor_texto
               FROM evento_pesquisa_respostas
              WHERE pergunta_id = :pergunta AND valor_texto IS NOT NULL AND valor_texto <> \'\'
              ORDER BY resposta_uid ASC'
        );
        $stmt->execute(['pergunta' => (int) $perguntaId]);

        $textos = [];

        foreach ($stmt->fetchAll() as $linha) {
            $textos[] = $linha['valor_texto'];
        }

        return $textos;
    }

    /**
     * Quantos envios uma pergunta recebeu (conta o envio, nao a linha, para
     * que a multipla escolha nao infle o numero).
     */
    public function contarEnvios($perguntaId)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('SELECT COUNT(DISTINCT resposta_uid) FROM evento_pesquisa_respostas WHERE pergunta_id = :pergunta');
        $stmt->execute(['pergunta' => (int) $perguntaId]);

        return (int) $stmt->fetchColumn();
    }

    /**
     * Todas as respostas do evento agrupadas por envio, para a exportacao:
     * [resposta_uid => [pergunta_id => [valores]]]. A ordenacao por
     * resposta_uid e' aleatoria de fato, entao nem o arquivo que circula
     * fora do sistema carrega a ordem de chegada.
     */
    public function porEnvioDoEvento($eventoId)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'SELECT r.resposta_uid, r.pergunta_id, r.valor_numero, r.valor_texto, r.respondido_em
               FROM evento_pesquisa_respostas r
               INNER JOIN evento_pesquisa_perguntas p ON p.id = r.pergunta_id
              WHERE p.evento_id = :evento
              ORDER BY r.resposta_uid ASC, p.ordem ASC, r.valor_numero ASC'
        );
        $stmt->execute(['evento' => (int) $eventoId]);

        $envios = [];

        foreach ($stmt->fetchAll() as $linha) {
            $uid = $linha['resposta_uid'];
            $pergunta = (int) $linha['pergunta_id'];

            if (!isset($envios[$uid])) {
                $envios[$uid] = ['respondido_em' => $linha['respondido_em'], 'respostas' => []];
            }

            if (!isset($envios[$uid]['respostas'][$pergunta])) {
                $envios[$uid]['respostas'][$pergunta] = [];
            }

            $envios[$uid]['respostas'][$pergunta][] = [
                'valor_numero' => (int) $linha['valor_numero'],
                'valor_texto' => $linha['valor_texto'],
            ];
        }

        return $envios;
    }
}
