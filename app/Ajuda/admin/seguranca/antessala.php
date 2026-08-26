<?php

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

return [
    'titulo' => 'Segurança — antessala',
    'resumo' => 'Sub-aba de Configurações, visível apenas para o Administrador com perfil global — quem administra só um concurso não a enxerga, e o acesso direto pelo endereço é recusado. Parada obrigatória antes da tela de credenciais. Ela existe porque abrir a tela seguinte avisa, no mesmo instante, todos os outros administradores globais — e isso não tem como ser desfeito.',
    'operacoes' => [
        [
            'nome' => 'Abrir configuração de segurança',
            'icone' => 'cadeado_aberto',
            'como' => 'Segue para a tela de credenciais. É este clique que aceita o registro na auditoria e o aviso aos demais administradores.',
            'observacao' => 'Enquanto você estiver nesta antessala, nada foi registrado e ninguém foi avisado. Sair daqui não deixa rastro.',
        ],
    ],
    'conceitos' => [],
];
