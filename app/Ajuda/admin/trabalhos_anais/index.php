<?php

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

return [
    'titulo' => 'Anais do Evento',
    'resumo' => 'Os Anais são o volume único, em PDF, com os trabalhos apresentados no evento, com tudo dentro (capa, folha de rosto, ficha catalográfica, expediente, comissões, sumário e os trabalhos). Há duas formas de ter uma versão: montar o volume fora do sistema e enviar aqui, ou pedir que o próprio sistema o monte na aba Montagem dos Anais. Nos dois casos a versão aparece na lista abaixo; aqui você confere, publica e o sistema avisa os autores.',
    'operacoes' => [
        [
            'nome' => 'Identificação',
            'como' => 'Título dos Anais, identificador editorial (ISSN ou ISBN, ou nenhum), descrição curta e o texto do e-mail de aviso aos autores. O título, o identificador e a descrição aparecem junto do botão Anais no aplicativo do participante.',
            'observacao' => 'O título não muda depois da primeira publicação: o sistema junta as versões pelo título, e um título novo separaria o histórico. O ISSN tem o formato 0000-0000; o ISBN tem 10 ou 13 dígitos, com ou sem hífens.',
        ],
        [
            'nome' => 'Enviar uma versão',
            'como' => 'Escolha o PDF dos Anais e, se quiser, escreva uma observação para a equipe. Cada envio vira uma versão numerada e fica guardado em área privada, sem aparecer para ninguém, até você publicar.',
            'observacao' => 'O sistema confere que o arquivo é mesmo um PDF e respeita o tamanho máximo do servidor, mostrado na tela. O conteúdo do volume é responsabilidade de quem o montou.',
        ],
        [
            'nome' => 'Abrir o PDF de uma versão',
            'icone' => 'baixar',
            'como' => 'Abre o arquivo da versão, publicada ou não, para conferir antes de publicar.',
        ],
        [
            'nome' => 'Remover uma versão',
            'icone' => 'remover',
            'como' => 'Apaga uma versão que nunca foi publicada, junto com o arquivo enviado.',
            'observacao' => 'Versão que já foi publicada não pode ser removida: ela fica no histórico.',
        ],
        [
            'nome' => 'Publicar',
            'icone' => 'publicar',
            'como' => 'Escolha a versão, marque se os autores dos trabalhos incluídos devem ser avisados (sino do aplicativo e e-mail, pela fila de envio, dez por minuto) e confirme. O PDF é copiado para a área pública, entra como Documento do Evento do tipo Anais e o botão Anais passa a aparecer no aplicativo do participante. A versão publicada antes fica arquivada.',
            'observacao' => 'Só é possível publicar depois de publicar o resultado de Trabalhos. O arquivo publicado fica acessível a qualquer pessoa com o endereço: confira antes que não há CPF, telefone pessoal ou qualquer dado que não deva ser público.',
        ],
        [
            'nome' => 'Despublicar os Anais',
            'icone' => 'despublicar',
            'como' => 'Tira o botão da página e do aplicativo. As versões continuam guardadas, e a lista de trabalhos dos Anais volta a poder ser editada.',
            'observacao' => 'Enquanto os Anais estiverem publicados, o resultado de Trabalhos não pode ser reaberto.',
        ],
        [
            'nome' => 'Botão na página pública do evento',
            'como' => 'Depois de publicados, os Anais aparecem na lista de documentos de um botão do Cronograma (Seções da página, Cronograma, campo "Documento do evento"). Escolha o documento com o título dos Anais e o botão abre sempre a versão publicada.',
        ],
    ],
    'conceitos' => ['nunca_apaga_so_versiona'],
];
