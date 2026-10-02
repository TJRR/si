<?php

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

return [
    'titulo' => 'Montagem dos Anais',
    'resumo' => 'Aqui o sistema monta o volume dos Anais sozinho, como alternativa ao envio do PDF pronto na aba Anais. Os autores principais enviam a versão final de cada trabalho pelo aplicativo; você preenche os dados editoriais e as comissões, confere a ordem dos trabalhos e pede a geração. O volume gerado entra como uma nova versão na aba Anais, sem publicar: nada é publicado sozinho, e a publicação continua sendo feita por você na aba Anais.',
    'operacoes' => [
        [
            'nome' => '1. Envio do PDF final pelos autores',
            'como' => 'Defina o prazo e escreva as instruções que o autor principal lê no quadro "Versão final para os Anais", na tela do trabalho, no aplicativo. O quadro só aparece depois que o resultado de Trabalhos é publicado e só para os trabalhos que constam nos Anais. Só o autor principal envia; os coautores veem apenas a situação. Até o fim do prazo, o autor pode trocar o arquivo quantas vezes quiser.',
            'observacao' => 'Você não envia arquivo em nome do autor. Se alguém perder o prazo, prorrogue o prazo aqui ou retire o trabalho na aba Trabalhos nos Anais. Prazo em branco fecha o envio.',
        ],
        [
            'nome' => 'Avisar os autores',
            'como' => 'Com a caixa marcada, ao salvar, cada autor principal recebe um aviso no sino do aplicativo e um e-mail pela fila de envio (dez por minuto), com o prazo e o texto do aviso escrito aqui (em branco, vale um texto padrão). Marque ao abrir o prazo e, se quiser, ao prorrogá-lo.',
            'observacao' => 'Nenhum aviso sai antes de o resultado de Trabalhos ser publicado, sem prazo preenchido ou com prazo já vencido. O prazo fica salvo mesmo que o aviso não possa sair: se você salvou o prazo antes de publicar o resultado, volte aqui depois da publicação e salve de novo com a caixa marcada.',
        ],
        [
            'nome' => 'PDF/A-1b',
            'como' => 'O sistema só consegue juntar PDFs gravados num formato mais simples, e por isso todo arquivo é conferido no momento do envio. Se um arquivo for recusado, a mensagem orienta a salvá-lo de novo como PDF/A-1b: no LibreOffice, Arquivo, Exportar como PDF, marque "Arquivo PDF/A" e escolha PDF/A-1b; no Word, Salvar como, tipo PDF, Opções, marque "Compatível com ISO 19005-1 (PDF/A)". Vale também para a capa.',
            'observacao' => 'Os autores não precisam numerar as páginas: o sistema coloca o número de cada página no rodapé do volume.',
        ],
        [
            'nome' => '2. Dados editoriais',
            'como' => 'Subtítulo, local e ano, organizadores, ficha catalográfica, expediente e apresentação. O título e o ISSN ou ISBN vêm da aba Anais. Organizadores saem no topo da folha de rosto; a ficha catalográfica, no verso da folha de rosto, no pé da página; o expediente, as comissões e a apresentação vêm em seguida, cada um começando numa página nova. Campo em branco não entra no volume.',
            'observacao' => 'Imagem só aparece no volume quando é inserida pelo botão de imagem do editor.',
        ],
        [
            'nome' => 'Capa',
            'como' => 'Envie a capa pronta, em PDF de uma página. Sem capa enviada, o sistema gera uma capa simples com o título, o subtítulo, o nome do evento, o local e o ano e o ISSN ou ISBN.',
            'observacao' => 'Trocar a capa substitui o arquivo anterior; remover volta para a capa simples.',
        ],
        [
            'nome' => 'Abrir o PDF',
            'icone' => 'baixar',
            'como' => 'Abre a capa enviada ou a versão final enviada por um autor, para conferir antes de gerar o volume.',
        ],
        [
            'nome' => '3. Comissões e membros',
            'icone' => 'editar',
            'como' => 'Inclua cada comissão e depois os seus membros (nome, função e instituição). No volume, cada membro sai numa linha, com os três dados separados por vírgula. O lápis edita a comissão ou o membro; a lixeira remove. Remover uma comissão apaga também os membros dela.',
        ],
        [
            'nome' => '4. Ordem dos trabalhos',
            'como' => 'A lista mostra os trabalhos que constam nos Anais, na ordem em que entram no volume, com a situação da versão final de cada um ("Enviado em..." ou "Pendente"). Sem ordem definida, eles seguem a ordem dos eixos temáticos e, dentro de cada eixo, o título. O sumário agrupa os trabalhos por eixo, então mantenha juntos os do mesmo eixo.',
            'observacao' => 'Trabalho retirado dos Anais sai pela aba Trabalhos nos Anais e deixa de aparecer aqui.',
        ],
        [
            'nome' => '5. Gerar prévia do volume',
            'como' => 'O botão só fica disponível com o resultado de Trabalhos publicado, o título dos Anais salvo e a versão final de todos os trabalhos enviada. O pedido entra numa fila e uma rotina agendada do servidor monta o volume em alguns minutos: capa, folha de rosto, ficha catalográfica, expediente, comissões, apresentação, sumário e os trabalhos. O número de cada página sai no rodapé a partir do primeiro trabalho; as páginas anteriores contam na sequência, sem número impresso, e o sumário aponta para esses números. No PDF gerado, clicar numa entrada do sumário leva à primeira página do trabalho, e o painel de marcadores do leitor de PDF mostra as partes do volume, os eixos e os trabalhos.',
            'observacao' => 'Quando o pedido aparecer como Concluída, abra a aba Anais, confira a versão nova e publique por lá, como qualquer outra versão. Se o pedido falhar, a mensagem diz o motivo (por exemplo, qual trabalho tem o arquivo com problema); corrija e peça de novo. Um pedido por vez para cada evento.',
        ],
    ],
    'conceitos' => ['reordenar_arraste'],
];
