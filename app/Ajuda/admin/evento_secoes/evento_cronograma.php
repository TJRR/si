<?php

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

return [
    'titulo' => 'Cronograma',
    'resumo' => 'Seção em duas colunas, como a de submissão de trabalhos: à esquerda, etiqueta, título, texto, até três botões e o contato; à direita, um quadro branco com a linha do tempo dos marcos.',
    'operacoes' => [
        ['nome' => 'Título do quadro', 'como' => 'Título do quadro branco da linha do tempo, por exemplo "Cronograma de submissão".'],
        ['nome' => 'Botão principal', 'como' => 'Pode apontar para um documento do evento (sub-aba Documentos) ou para um destino digitado. Com documento escolhido, abre sempre a versão atual dele, e some da página se o documento for despublicado ou removido.'],
        ['nome' => 'Botão secundário', 'como' => 'Botão com contorno, ao lado do principal, com destino digitado.'],
        ['nome' => 'Terceiro botão', 'como' => 'Botão com contorno, ao lado dos outros dois. Funciona como o principal: pode apontar para um documento do evento ou para um destino digitado. Serve, por exemplo, para oferecer o modelo do resumo expandido para baixar: envie o arquivo em Evento, Documentos e escolha-o aqui. Sem texto ou sem destino válido, o botão não aparece na página.'],
        ['nome' => 'Destinos aceitos', 'como' => 'Nos três botões: âncora da própria página ("#programacao"), endereço completo ("https://...") ou endereço interno do sistema ("trabalho/formulario/1"), que o sistema completa sozinho.'],
        ['nome' => 'Mostrar e-mail e WhatsApp', 'como' => 'Mostra, abaixo dos botões e com ícones, o e-mail e o WhatsApp cadastrados em Configurações, aba Contato (os mesmos do rodapé).'],
        ['nome' => 'Período', 'como' => 'Escreva como deve ser lido na página: "16/10/2026, até 23h59" ou "19 a 26/10/2026".'],
        ['nome' => 'Data de referência', 'como' => 'Opcional, serve para ordenar e destacar o marco; um intervalo não precisa dela.'],
        ['nome' => 'Cor', 'como' => 'Cor do marcador daquele item na linha do tempo.'],
    ],
    'conceitos' => ['reordenar_arraste'],
];
