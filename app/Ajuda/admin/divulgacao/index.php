<?php

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

return [
    'titulo' => 'Divulgação',
    'resumo' => 'Comprovações de divulgação enviadas pelos participantes, com a prova de cada uma. Os pontos são creditados no momento do envio; a conferência é feita aqui, por amostragem, e a pontuação pode ser anulada com justificativa.',
    'operacoes' => [
        [
            'nome' => 'Números do evento',
            'como' => 'Comprovações válidas, pontos creditados no total, participantes que enviaram e quantas foram anuladas. Comprovação anulada sai de todas as somas.',
        ],
        [
            'nome' => 'Conferir uma comprovação',
            'como' => 'Compare a prova com o perfil que a pessoa cadastrou em Meu Perfil, mostrado ao lado. O endereço da publicação abre em outra aba; a imagem da tela abre pelo link "Ver a imagem enviada".',
            'observacao' => 'Só o Administrador abre as imagens: elas podem conter dados de terceiros que não escolheram estar ali. O perfil Suporte vê a lista, os números e o endereço da publicação.',
        ],
        [
            'nome' => 'Mesma imagem em vários participantes',
            'como' => 'Marca que aparece quando a mesma imagem foi enviada por mais de uma pessoa. Não é recusa nem indício certo de fraude: material oficial de campanha baixado por duas pessoas é idêntico. Serve para chamar a sua atenção àquele caso.',
        ],
        [
            'nome' => 'Anular pontuação',
            'como' => 'Escreva o motivo e confirme. A pessoa perde os pontos daquela comprovação, recebe um aviso no sino com o motivo que você escreveu, e a vaga volta a ficar disponível no limite dela.',
            'observacao' => 'A comprovação não é apagada, e a prova continua bloqueada: enviar de novo a mesma imagem ou o mesmo endereço não devolve os pontos.',
        ],
        [
            'nome' => 'Desfazer anulação',
            'como' => 'Devolve a pontuação de uma comprovação anulada por engano, avisa a pessoa e fica registrado.',
        ],
    ],
    'conceitos' => ['permissao_suporte_admin'],
];
