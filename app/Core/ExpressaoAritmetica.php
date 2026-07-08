<?php

namespace App\Core;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

/**
 * Avaliador de expressoes aritmeticas simples (+ - * / parenteses e variaveis numericas).
 * Recursivo-descendente, sem eval()/create_function/execucao de codigo: so aritmetica.
 */
class ExpressaoAritmetica
{
    private $tokens;
    private $posicao;
    private $variaveis;

    public static function avaliar($expressao, array $variaveis)
    {
        $instancia = new self();

        return $instancia->executar($expressao, $variaveis);
    }

    public static function validar($expressao, array $variaveisPermitidas)
    {
        $valoresFicticios = array_fill_keys($variaveisPermitidas, 1.0);

        try {
            (new self())->executar($expressao, $valoresFicticios);

            return ['valido' => true, 'mensagem' => null];
        } catch (\RuntimeException $e) {
            return ['valido' => false, 'mensagem' => $e->getMessage()];
        }
    }

    private function executar($expressao, array $variaveis)
    {
        $this->tokens = $this->tokenizar($expressao);
        $this->posicao = 0;
        $this->variaveis = $variaveis;

        if (empty($this->tokens)) {
            throw new \RuntimeException('Formula vazia.');
        }

        $resultado = $this->parseExpressao();

        if ($this->tokenAtual() !== null) {
            throw new \RuntimeException('Token inesperado na formula: ' . $this->tokenAtual()['valor']);
        }

        return $resultado;
    }

    private function tokenizar($expressao)
    {
        $semEspacos = preg_replace('/\s+/', '', (string) $expressao);

        if ($semEspacos === '') {
            return [];
        }

        preg_match_all('/[0-9]+(?:\.[0-9]+)?|[A-Za-z_][A-Za-z0-9_]*|[+\-*\/()]/', $semEspacos, $encontrados);
        $reconstruido = implode('', $encontrados[0]);

        if ($reconstruido !== $semEspacos) {
            throw new \RuntimeException('A formula contem caracteres invalidos.');
        }

        $tokens = [];

        foreach ($encontrados[0] as $token) {
            if (is_numeric($token)) {
                $tokens[] = ['tipo' => 'numero', 'valor' => (float) $token];
            } elseif (preg_match('/^[A-Za-z_]/', $token)) {
                $tokens[] = ['tipo' => 'variavel', 'valor' => $token];
            } else {
                $tokens[] = ['tipo' => 'operador', 'valor' => $token];
            }
        }

        return $tokens;
    }

    private function parseExpressao()
    {
        $valor = $this->parseTermo();

        while ($this->tokenEhOperador(['+', '-'])) {
            $operador = $this->consumir()['valor'];
            $direito = $this->parseTermo();
            $valor = $operador === '+' ? $valor + $direito : $valor - $direito;
        }

        return $valor;
    }

    private function parseTermo()
    {
        $valor = $this->parseFator();

        while ($this->tokenEhOperador(['*', '/'])) {
            $operador = $this->consumir()['valor'];
            $direito = $this->parseFator();

            if ($operador === '*') {
                $valor = $valor * $direito;
            } else {
                if ($direito == 0.0) {
                    throw new \RuntimeException('Divisao por zero na formula.');
                }
                $valor = $valor / $direito;
            }
        }

        return $valor;
    }

    private function parseFator()
    {
        $token = $this->tokenAtual();

        if ($token === null) {
            throw new \RuntimeException('Formula incompleta.');
        }

        if ($this->tokenEhOperador(['-'])) {
            $this->consumir();

            return -1 * $this->parseFator();
        }

        if ($this->tokenEhOperador(['+'])) {
            $this->consumir();

            return $this->parseFator();
        }

        if ($token['tipo'] === 'numero') {
            $this->consumir();

            return $token['valor'];
        }

        if ($token['tipo'] === 'variavel') {
            $this->consumir();

            if (!array_key_exists($token['valor'], $this->variaveis)) {
                throw new \RuntimeException('Variavel desconhecida na formula: ' . $token['valor']);
            }

            return (float) $this->variaveis[$token['valor']];
        }

        if ($this->tokenEhOperador(['('])) {
            $this->consumir();
            $valor = $this->parseExpressao();

            if (!$this->tokenEhOperador([')'])) {
                throw new \RuntimeException('Parenteses nao fechados na formula.');
            }

            $this->consumir();

            return $valor;
        }

        throw new \RuntimeException('Token inesperado na formula: ' . $token['valor']);
    }

    private function tokenAtual()
    {
        return isset($this->tokens[$this->posicao]) ? $this->tokens[$this->posicao] : null;
    }

    private function tokenEhOperador(array $valoresAceitos)
    {
        $token = $this->tokenAtual();

        return $token !== null && $token['tipo'] === 'operador' && in_array($token['valor'], $valoresAceitos, true);
    }

    private function consumir()
    {
        return $this->tokens[$this->posicao++];
    }
}
