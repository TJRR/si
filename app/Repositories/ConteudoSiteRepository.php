<?php

namespace App\Repositories;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Core\Database;

class ConteudoSiteRepository
{
    public function listar()
    {
        $pdo = Database::conexao();

        return $pdo->query('SELECT * FROM conteudos_site ORDER BY id ASC')->fetchAll();
    }

    public function listarComoMapa()
    {
        $mapa = [];

        foreach ($this->listar() as $conteudo) {
            $mapa[$conteudo['chave']] = $conteudo['valor'];
        }

        return $mapa;
    }

    public function atualizarValor($chave, $valor)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('UPDATE conteudos_site SET valor = :valor WHERE chave = :chave');
        $stmt->execute(['valor' => $valor, 'chave' => $chave]);
    }
}
