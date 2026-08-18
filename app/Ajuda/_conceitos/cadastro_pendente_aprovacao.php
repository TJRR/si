<?php

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

return [
    'titulo' => 'Todo cadastro nasce pendente',
    'texto' => 'Não existe acesso automático a partir de um autocadastro — manual, por Google, ou por inscrição pública de equipe. Toda conta nova fica pendente até um Administrador aprovar, em algum dos dois pontos do sistema que fazem isso: a tela Usuários (cadastros diretos de login, atribuindo perfil e concurso/categoria de avaliador) ou a tela Homologação (integrantes de uma equipe recém-inscrita — homologar cria a conta e libera o acesso). Tentar entrar antes de ser aprovado mostra uma mensagem específica de "cadastro pendente", não um erro genérico.',
];
