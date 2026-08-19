<?php

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

return [
    'titulo' => 'Presença real na sala do Meet',
    'texto' => "Complementa o convite: enquanto o RSVP mostra quem RESPONDEU ao convite, a presença mostra quem de fato ENTROU na sala e por quanto tempo. Só existe para horários com integração ao Google Agenda ativa e que já terminaram.\n\n"
        . "A captura é automática, feita por um processo que roda sozinho no servidor a cada 30 minutos — não há botão para \"gerar\" o relatório. Ela só começa a tentar 2 horas depois do fim do horário, porque a reunião pode se estender além do previsto e o Google leva um tempo para fechar o registro da chamada. Enquanto isso a tela mostra \"Presença ainda não capturada\", o que é normal, não é erro.\n\n"
        . "Se depois de 30 tentativas os dados não vierem, o horário fica como \"Presença indisponível\" e os administradores são avisados no sino. Corrigida a causa, o botão \"Reprocessar presença\" devolve o horário para a fila.\n\n"
        . "ATENÇÃO À IDENTIFICAÇÃO: o Google não informa o e-mail de quem participou — só o nome da conta usada para entrar. O sistema casa esse nome com o nome do integrante cadastrado. Quem entra com uma conta pessoal, apelido ou nome social diferente do cadastro NÃO é reconhecido e aparece em \"Entraram sem identificação\", na mesma lista de quem realmente não foi convidado. Confira essa lista antes de concluir que alguém de fora entrou.\n\n"
        . "Quem cai da chamada e volta gera várias entradas; a permanência exibida é a SOMA de todas, e o número de entradas aparece ao lado quando é maior que uma.\n\n"
        . "Os nomes de quem entrou sem identificação são apagados 30 dias após a captura (a permanência e a contagem continuam disponíveis) — o Google também apaga os dados de origem no mesmo prazo.",
];
