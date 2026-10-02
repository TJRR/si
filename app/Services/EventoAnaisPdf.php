<?php

namespace App\Services;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use setasign\Fpdi\Fpdi;

/**
 * Montagem do volume dos Anais com marcadores (o painel lateral do leitor de
 * PDF). Nem a FPDF, nem a FPDI, nem o Dompdf escrevem marcadores; esta
 * classe acrescenta a arvore de contorno ao documento, no formato da
 * especificacao do PDF, sem dependencia nova.
 */
class EventoAnaisPdf extends Fpdi
{
    private $marcadores = [];
    private $raizDosMarcadores;

    /**
     * Marcador para a pagina atual, a partir do topo dela. $nivel 0 fica na
     * raiz; $nivel 1 fica dentro do ultimo marcador de nivel 0.
     */
    public function marcador($titulo, $nivel = 0)
    {
        $this->marcadores[] = [
            't' => (string) $titulo,
            'l' => max(0, (int) $nivel),
            'y' => $this->h * $this->k,
            'p' => $this->PageNo(),
        ];
    }

    protected function _putresources()
    {
        parent::_putresources();
        $this->gravarMarcadores();
    }

    protected function _putcatalog()
    {
        parent::_putcatalog();

        if ($this->marcadores !== []) {
            $this->_put('/Outlines ' . $this->raizDosMarcadores . ' 0 R');
            $this->_put('/PageMode /UseOutlines');
        }
    }

    private function gravarMarcadores()
    {
        $total = count($this->marcadores);

        if ($total === 0) {
            return;
        }

        $ultimoPorNivel = [];
        $nivelAnterior = 0;

        foreach ($this->marcadores as $i => $marcador) {
            if ($marcador['l'] > 0 && isset($ultimoPorNivel[$marcador['l'] - 1])) {
                $pai = $ultimoPorNivel[$marcador['l'] - 1];
                $this->marcadores[$i]['parent'] = $pai;
                $this->marcadores[$pai]['last'] = $i;

                if ($marcador['l'] > $nivelAnterior) {
                    $this->marcadores[$pai]['first'] = $i;
                }
            } else {
                $this->marcadores[$i]['l'] = 0;
                $marcador['l'] = 0;
                $this->marcadores[$i]['parent'] = $total;
            }

            if ($marcador['l'] <= $nivelAnterior && $i > 0 && isset($ultimoPorNivel[$marcador['l']])) {
                $anterior = $ultimoPorNivel[$marcador['l']];
                $this->marcadores[$anterior]['next'] = $i;
                $this->marcadores[$i]['prev'] = $anterior;
            }

            $ultimoPorNivel[$marcador['l']] = $i;

            foreach (array_keys($ultimoPorNivel) as $nivel) {
                if ($nivel > $marcador['l']) {
                    unset($ultimoPorNivel[$nivel]);
                }
            }

            $nivelAnterior = $marcador['l'];
        }

        $primeiro = $this->n + 1;

        foreach ($this->marcadores as $marcador) {
            $this->_newobj();
            $this->_put('<</Title ' . $this->_textstring($marcador['t']));
            $this->_put('/Parent ' . ($primeiro + $marcador['parent']) . ' 0 R');

            foreach (['prev' => '/Prev', 'next' => '/Next', 'first' => '/First', 'last' => '/Last'] as $chave => $nome) {
                if (isset($marcador[$chave])) {
                    $this->_put($nome . ' ' . ($primeiro + $marcador[$chave]) . ' 0 R');
                }
            }

            $this->_put(sprintf('/Dest [%d 0 R /XYZ 0 %.2F null]', $this->PageInfo[$marcador['p']]['n'], $marcador['y']));
            $this->_put('/Count 0>>');
            $this->_put('endobj');
        }

        $this->_newobj();
        $this->raizDosMarcadores = $this->n;
        $this->_put('<</Type /Outlines /First ' . $primeiro . ' 0 R');
        $this->_put('/Last ' . ($primeiro + $ultimoPorNivel[0]) . ' 0 R>>');
        $this->_put('endobj');
    }
}
