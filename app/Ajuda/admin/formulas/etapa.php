<?php

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

return [
    'titulo' => 'Fórmula da Etapa: cálculo da Nota da Etapa (NE)',
    'resumo' => 'Expressão matemática que calcula a nota final de uma submissão nesta etapa, a partir dos códigos de critério (ex.: (C1*3 + C2*4) / 7).',
    'operacoes' => [
        [
            'nome' => 'Gerar fórmula a partir dos pesos',
            'como' => 'Monta automaticamente uma média ponderada usando os pesos já cadastrados em Critérios; não salva sozinho, só preenche o campo.',
        ],
        [
            'nome' => 'Testar fórmula',
            'como' => 'Calcula o resultado com valores de exemplo que você digita, sem gravar nada. Use para conferir antes de salvar.',
        ],
        [
            'nome' => 'Salvar',
            'como' => 'Grava a expressão.',
            'observacao' => 'Os pesos ficam embutidos como números na expressão salva. Se um peso de critério mudar depois em Critérios, a fórmula aqui não se atualiza sozinha; é preciso gerar/editar de novo.',
        ],
        [
            'nome' => 'Casas decimais',
            'como' => 'Quantas casas decimais a Nota da Etapa (NE) desta etapa mostra em toda tela/relatório onde aparece (padrão 2). Não afeta a nota individual por critério/avaliador, sempre exibida com 1 casa.',
        ],
    ],
    'conceitos' => [],
];
