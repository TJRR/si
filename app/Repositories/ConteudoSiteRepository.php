<?php

namespace App\Repositories;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Core\Auditoria;
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
            $mapa[$conteudo['chave']] = $conteudo['tipo'] === 'imagem'
                ? $conteudo['arquivo_path']
                : $conteudo['valor'];
        }

        return $mapa;
    }

    public function buscarPorChave($chave)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('SELECT * FROM conteudos_site WHERE chave = :chave');
        $stmt->execute(['chave' => $chave]);

        $linha = $stmt->fetch();

        return $linha !== false ? $linha : null;
    }

    public function atualizarValor($chave, $valor)
    {
        $antes = $this->buscarPorChave($chave);
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('UPDATE conteudos_site SET valor = :valor WHERE chave = :chave');
        $stmt->execute(['valor' => $valor, 'chave' => $chave]);

        Auditoria::registrar('atualizar_valor', 'conteudos_site', null, $antes, ['chave' => $chave, 'valor' => $valor]);
    }

    public function atualizarImagem($chave, $arquivoPath)
    {
        $antes = $this->buscarPorChave($chave);
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('UPDATE conteudos_site SET arquivo_path = :arquivo_path WHERE chave = :chave');
        $stmt->execute(['arquivo_path' => $arquivoPath, 'chave' => $chave]);

        Auditoria::registrar('atualizar_imagem', 'conteudos_site', null, $antes, ['chave' => $chave, 'arquivo_path' => $arquivoPath]);
    }
}
