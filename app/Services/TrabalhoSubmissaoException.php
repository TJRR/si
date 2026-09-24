<?php

namespace App\Services;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

/**
 * Reabertura da Fase 51 (achados da equipe de Teste Cego): erro de validacao
 * da submissao de Trabalho que sabe QUAL campo do formulario o causou, para a
 * tela destacar o campo e rolar ate' ele em vez de mostrar so' uma frase
 * solta no topo. Continua sendo RuntimeException: quem so' captura
 * RuntimeException (como faz o restante do sistema) segue funcionando igual.
 *
 * $campo e' o nome do campo do formulario (ex.: autor_cpf, telefone_contato,
 * arquivo_avaliacao) ou nulo quando o problema nao pertence a um campo so'
 * (prazo encerrado, por exemplo). $indice e' a posicao do bloco de coautor no
 * formulario (0, 1, ...) quando o campo e' de um coautor.
 */
class TrabalhoSubmissaoException extends \RuntimeException
{
    private $campo;
    private $indice;

    public function __construct($mensagem, $campo = null, $indice = null)
    {
        parent::__construct($mensagem);
        $this->campo = $campo;
        $this->indice = $indice;
    }

    public function campo()
    {
        return $this->campo;
    }

    public function indice()
    {
        return $this->indice;
    }
}
