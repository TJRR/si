<?php

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

return [
    'titulo' => 'Atividade: Facilitadores',
    'resumo' => 'Instrutores, professores ou palestrantes desta atividade. Sempre um usuário já cadastrado no sistema: nenhuma conta nova é criada por aqui.',
    'operacoes' => [
        [
            'nome' => 'Buscar usuário',
            'como' => 'Digite ao menos dois caracteres do nome ou e-mail e escolha na lista de sugestões. Se a pessoa não tiver cadastro ainda, é preciso cadastrá-la antes, em Usuários.',
        ],
        [
            'nome' => 'Perfil',
            'como' => 'O papel dessa pessoa nesta atividade (Instrutor, Professor, Palestrante, ou outro nome cadastrado). Definido em Eventos, sub-aba Perfis. A mesma pessoa pode ter perfis diferentes em atividades diferentes.',
        ],
        [
            'nome' => 'Documento, cargo, categoria, órgão de origem, minicurrículo, foto',
            'como' => 'Dados da pessoa, não desta atividade específica: se ela já tiver esses dados preenchidos (de uma designação anterior, ou por ela mesma em Meu Perfil), aparecem automaticamente ao selecioná-la na busca. Preencha apenas o que ainda estiver vazio.',
        ],
        [
            'nome' => 'Remover',
            'como' => 'Desfaz o vínculo desta pessoa com esta atividade. O registro não é apagado de verdade: continua aparecendo em exportações já geradas, e revincular a mesma pessoa reaproveita o histórico.',
        ],
    ],
    'conceitos' => [],
];
