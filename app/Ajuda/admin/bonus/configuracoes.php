<?php

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

return [
    'titulo' => 'Configurações dos bônus',
    'resumo' => 'Liga ou desliga os bônus automáticos deste evento.',
    'operacoes' => [
        [
            'nome' => 'Ativar os bônus neste evento',
            'como' => 'Desativado, nada é apurado e o participante não vê o progresso no aplicativo. Os créditos já concedidos não são apagados nem perdem valor, e voltam a aparecer quando o módulo for ligado de novo.',
        ],
        [
            'nome' => 'Onde ficam os bônus',
            'como' => 'O nome, o tipo, a exigência e os pontos de cada bônus ficam na aba Bônus. Esta tela é só a chave geral do módulo no evento.',
        ],
        [
            'nome' => 'Salvar reconfere todo mundo',
            'como' => 'Ao salvar, o sistema refaz a conferência de todos os inscritos e concede o que estiver vencido, avisando quantos créditos novos saíram.',
        ],
    ],
    'conceitos' => ['permissao_suporte_admin'],
];
