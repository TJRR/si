<?php

namespace App\Services;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

/**
 * Fase 31: resolve o conteudo de ajuda contextual de uma tela a partir de
 * $view (ex. 'admin/concursos/index') - a mesma chave que todo controller ja
 * passa pra Controller::renderizar()/View::renderizar(). Cada tela com ajuda
 * tem um arquivo espelhado em app/Ajuda/{$view}.php devolvendo um array; a
 * ausencia do arquivo so' significa que aquela tela ainda nao tem ajuda
 * escrita (rollout incremental, ver InventarioTelasFase31_SistemaAjuda.md) -
 * nao e' erro.
 *
 * "Conceitos" transversais (app/Ajuda/_conceitos/*.php) sao a mesma ideia:
 * texto escrito uma vez, referenciado por 'conceitos' => ['slug', ...] em
 * quantas telas precisarem, sem duplicar.
 */
class AjudaService
{
    /**
     * $dadosDaTela (opcional): os dados que o controller entregou a' tela.
     * Um arquivo de ajuda pode devolver, em vez do array fixo, uma FUNCAO que
     * recebe esses dados e monta o texto conforme o que a tela realmente
     * mostra (ex.: so' as formas de envio habilitadas naquele evento). Os
     * arquivos em formato de array continuam funcionando sem mudanca.
     */
    public static function paraView($view, array $dadosDaTela = [])
    {
        $caminho = __DIR__ . '/../Ajuda/' . $view . '.php';

        if (!file_exists($caminho)) {
            return null;
        }

        $dados = require $caminho;

        if ($dados instanceof \Closure) {
            $dados = $dados($dadosDaTela);
        }
        $dados['conceitos'] = array_map(
            static function ($slug) {
                return self::conceito($slug);
            },
            $dados['conceitos'] ?? []
        );

        return $dados;
    }

    public static function conceito($slug)
    {
        $caminho = __DIR__ . '/../Ajuda/_conceitos/' . $slug . '.php';

        if (!file_exists($caminho)) {
            return ['titulo' => $slug, 'texto' => ''];
        }

        return require $caminho;
    }
}
