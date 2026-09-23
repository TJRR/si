<?php

namespace App\Repositories;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Core\Auditoria;
use App\Core\Database;

/**
 * Fase 50, reescrito na Fase 51: ordem, liga/desliga e menu de TODAS as
 * secoes da pagina publica de cada Evento, nao so dos Blocos de conteudo.
 * Cada linha e uma secao da pagina: os Quadros de apresentacao e as Faixas
 * (uma linha cada, sem referencia, porque sao listas unicas do evento), cada
 * Bloco de conteudo e cada instancia de componente (contagem, cronograma,
 * cartoes, destaques, programacao, faq, local).
 *
 * O menu do cabecalho sai daqui, na mesma ordem da pagina - antes vinha de
 * evento_blocos_conteudo.mostrar_no_menu, que so enxergava bloco.
 *
 * A referencia e polimorfica (nao ha chave estrangeira possivel), entao quem
 * apaga o item apaga a linha de ordem na mesma transacao, via removerSecao().
 */
class EventoSecaoOrdemRepository
{
    /**
     * Tipos aceitos e a tabela de onde sai o titulo de cada um. 'quadros' e
     * 'faixas' nao tem tabela propria de secao: sao a lista inteira de
     * evento_slides/evento_banners daquele evento, por isso referencia nula.
     */
    private static $tabelasPorTipo = [
        'bloco' => 'evento_blocos_conteudo',
        'contagem' => 'evento_secao_contagem',
        'cronograma' => 'evento_secao_cronograma',
        'cartoes' => 'evento_secao_cartoes',
        'destaques' => 'evento_secao_destaques',
        'programacao' => 'evento_secao_programacao',
        'faq' => 'evento_secao_faq',
        'local' => 'evento_secao_local',
    ];

    private static $tiposSemReferencia = ['quadros', 'faixas'];

    public static function tiposComReferencia()
    {
        return array_keys(self::$tabelasPorTipo);
    }

    public static function tiposSemReferencia()
    {
        return self::$tiposSemReferencia;
    }

    /**
     * Todas as secoes do evento, ativas ou nao, com o titulo do item
     * referenciado ja resolvido (para a tela de ordenacao mostrar do que se
     * trata cada linha sem uma consulta por linha).
     */
    public function listarOrdenado($eventoId)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'SELECT o.id AS secao_id, o.tipo, o.referencia_id, o.ordem, o.ativo,
                    o.mostrar_no_menu, o.rotulo_menu,
                    COALESCE(b.titulo, ct.titulo, cr.titulo, ca.titulo, de.titulo, pr.titulo, fa.titulo, lo.titulo) AS titulo_item,
                    b.secao_ancora AS bloco_ancora
             FROM evento_secoes_ordem o
             LEFT JOIN evento_blocos_conteudo b ON o.tipo = \'bloco\' AND b.id = o.referencia_id
             LEFT JOIN evento_secao_contagem ct ON o.tipo = \'contagem\' AND ct.id = o.referencia_id
             LEFT JOIN evento_secao_cronograma cr ON o.tipo = \'cronograma\' AND cr.id = o.referencia_id
             LEFT JOIN evento_secao_cartoes ca ON o.tipo = \'cartoes\' AND ca.id = o.referencia_id
             LEFT JOIN evento_secao_destaques de ON o.tipo = \'destaques\' AND de.id = o.referencia_id
             LEFT JOIN evento_secao_programacao pr ON o.tipo = \'programacao\' AND pr.id = o.referencia_id
             LEFT JOIN evento_secao_faq fa ON o.tipo = \'faq\' AND fa.id = o.referencia_id
             LEFT JOIN evento_secao_local lo ON o.tipo = \'local\' AND lo.id = o.referencia_id
             WHERE o.evento_id = :evento_id
             ORDER BY o.ordem ASC, o.id ASC'
        );
        $stmt->execute(['evento_id' => $eventoId]);

        return $stmt->fetchAll();
    }

    public function listarAtivas($eventoId)
    {
        return array_values(array_filter($this->listarOrdenado($eventoId), function (array $secao) {
            return (int) $secao['ativo'] === 1;
        }));
    }

    /**
     * Itens do menu do cabecalho, na ordem da pagina. O destino e sempre uma
     * ancora da propria pagina: do bloco vem a ancora cadastrada nele; dos
     * demais, um identificador estavel montado com tipo e id da secao (o
     * mesmo que a view publica usa no atributo id de cada secao).
     */
    public function listarMenu($eventoId)
    {
        $itens = [];

        foreach ($this->listarAtivas($eventoId) as $secao) {
            if ((int) $secao['mostrar_no_menu'] !== 1) {
                continue;
            }

            $rotulo = trim((string) $secao['rotulo_menu']);

            if ($rotulo === '') {
                $rotulo = (string) $secao['titulo_item'];
            }

            if ($rotulo === '') {
                continue;
            }

            $itens[] = [
                'rotulo' => $rotulo,
                'ancora' => self::ancoraDaSecao($secao),
            ];
        }

        return $itens;
    }

    /**
     * Ancora usada tanto pelo menu quanto pelo atributo id da secao na view
     * publica - precisa sair de um lugar so, senao o menu aponta para um
     * destino que nao existe na pagina.
     */
    public static function ancoraDaSecao(array $secao)
    {
        if ($secao['tipo'] === 'bloco' && !empty($secao['bloco_ancora'])) {
            return $secao['bloco_ancora'];
        }

        if (in_array($secao['tipo'], self::$tiposSemReferencia, true)) {
            return 'secao-' . $secao['tipo'];
        }

        return 'secao-' . $secao['tipo'] . '-' . (int) $secao['referencia_id'];
    }

    public function buscarPorId($eventoId, $secaoId)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('SELECT * FROM evento_secoes_ordem WHERE id = :id AND evento_id = :evento_id LIMIT 1');
        $stmt->execute(['id' => $secaoId, 'evento_id' => $eventoId]);

        $linha = $stmt->fetch();

        return $linha !== false ? $linha : null;
    }

    /**
     * Registra a secao no fim da pagina. Idempotente: chamar duas vezes para
     * o mesmo par (tipo, referencia) nao duplica linha, so mantem a que ja
     * existe - o que permite chamar sem medo ao criar o item e tambem ao
     * abrir a tela de ordenacao.
     */
    public function registrarSecao($eventoId, $tipo, $referenciaId = null, $rotuloMenu = null)
    {
        if ($this->buscarPorItem($eventoId, $tipo, $referenciaId) !== null) {
            return;
        }

        $pdo = Database::conexao();
        $stmt = $pdo->prepare('SELECT COALESCE(MAX(ordem), -1) + 1 FROM evento_secoes_ordem WHERE evento_id = :evento_id');
        $stmt->execute(['evento_id' => $eventoId]);
        $proximaOrdem = (int) $stmt->fetchColumn();

        $stmt = $pdo->prepare(
            'INSERT INTO evento_secoes_ordem (evento_id, tipo, referencia_id, ordem, ativo, mostrar_no_menu, rotulo_menu)
             VALUES (:evento_id, :tipo, :referencia_id, :ordem, 1, 0, :rotulo_menu)'
        );
        $stmt->execute([
            'evento_id' => $eventoId,
            'tipo' => $tipo,
            'referencia_id' => $referenciaId !== null ? (int) $referenciaId : null,
            'ordem' => $proximaOrdem,
            'rotulo_menu' => $rotuloMenu,
        ]);

        Auditoria::registrar('criar', 'evento_secoes_ordem', (int) $pdo->lastInsertId(), null, [
            'evento_id' => $eventoId,
            'tipo' => $tipo,
            'referencia_id' => $referenciaId,
            'ordem' => $proximaOrdem,
        ]);
    }

    public function buscarPorItem($eventoId, $tipo, $referenciaId = null)
    {
        $pdo = Database::conexao();

        if ($referenciaId === null) {
            $stmt = $pdo->prepare('SELECT * FROM evento_secoes_ordem WHERE evento_id = :evento_id AND tipo = :tipo AND referencia_id IS NULL LIMIT 1');
            $stmt->execute(['evento_id' => $eventoId, 'tipo' => $tipo]);
        } else {
            $stmt = $pdo->prepare('SELECT * FROM evento_secoes_ordem WHERE evento_id = :evento_id AND tipo = :tipo AND referencia_id = :referencia_id LIMIT 1');
            $stmt->execute(['evento_id' => $eventoId, 'tipo' => $tipo, 'referencia_id' => (int) $referenciaId]);
        }

        $linha = $stmt->fetch();

        return $linha !== false ? $linha : null;
    }

    /**
     * Chamado por quem apaga o item (bloco ou componente), sempre na mesma
     * transacao - sem chave estrangeira polimorfica, esta e a unica garantia
     * de que nao sobra linha de ordem apontando para item que nao existe.
     */
    public function removerSecao($eventoId, $tipo, $referenciaId = null)
    {
        $antes = $this->buscarPorItem($eventoId, $tipo, $referenciaId);

        if ($antes === null) {
            return;
        }

        $pdo = Database::conexao();
        $stmt = $pdo->prepare('DELETE FROM evento_secoes_ordem WHERE id = :id');
        $stmt->execute(['id' => (int) $antes['id']]);

        Auditoria::registrar('remover', 'evento_secoes_ordem', (int) $antes['id'], $antes, null);
    }

    /**
     * Quadros de apresentacao e Faixas existem em todo evento (sao listas do
     * proprio evento, nao componentes que o Admin adiciona), entao a linha de
     * ordem delas e criada sob demanda na primeira vez que a pagina ou a tela
     * de ordenacao precisa - mesmo padrao lazy ja usado para numero de sigilo.
     */
    public function garantirSecoesFixas($eventoId)
    {
        foreach (self::$tiposSemReferencia as $tipo) {
            $this->registrarSecao($eventoId, $tipo, null, null);
        }
    }

    public function atualizarItem($eventoId, $secaoId, array $dados)
    {
        $antes = $this->buscarPorId($eventoId, $secaoId);

        if ($antes === null) {
            return;
        }

        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'UPDATE evento_secoes_ordem
             SET ativo = :ativo, mostrar_no_menu = :mostrar_no_menu, rotulo_menu = :rotulo_menu
             WHERE id = :id AND evento_id = :evento_id'
        );
        $stmt->execute([
            'ativo' => !empty($dados['ativo']) ? 1 : 0,
            'mostrar_no_menu' => !empty($dados['mostrar_no_menu']) ? 1 : 0,
            'rotulo_menu' => isset($dados['rotulo_menu']) && trim((string) $dados['rotulo_menu']) !== '' ? trim($dados['rotulo_menu']) : null,
            'id' => $secaoId,
            'evento_id' => $eventoId,
        ]);

        Auditoria::registrar('atualizar', 'evento_secoes_ordem', (int) $secaoId, $antes, $dados);
    }

    /**
     * Reordenacao em lote (arrastar-e-soltar). Os ids sao de
     * evento_secoes_ordem, e o evento_id entra no WHERE: um envio manipulado
     * com id de outro evento nao afeta nenhuma linha.
     */
    public function reordenar($eventoId, array $ids)
    {
        $pdo = Database::conexao();
        $pdo->beginTransaction();

        try {
            $stmt = $pdo->prepare('UPDATE evento_secoes_ordem SET ordem = :ordem WHERE id = :id AND evento_id = :evento_id');

            foreach ($ids as $indice => $id) {
                $stmt->execute(['ordem' => $indice, 'id' => (int) $id, 'evento_id' => $eventoId]);
            }

            $pdo->commit();
            Auditoria::registrar('reordenar', 'evento_secoes_ordem', null, null, ['evento_id' => $eventoId, 'ids' => $ids]);
        } catch (\Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
    }
}
