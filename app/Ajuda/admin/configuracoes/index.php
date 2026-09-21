<?php

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

return [
    'titulo' => 'Configurações',
    'resumo' => 'Ajustes globais do sistema: tempo de expiração de sessão por inatividade, identidade institucional, identidade do aplicativo instalável do Evento e o modo de manutenção.',
    'operacoes' => [
        [
            'nome' => 'Tempo de expiração de sessão',
            'como' => 'Minutos de inatividade até o logout automático.',
        ],
        [
            'nome' => 'Identidade institucional',
            'como' => 'Nome por extenso e sigla da instituição, e nome por extenso e sigla da unidade responsável pelo sistema. O nome por extenso aparece em textos formais, como o rodapé; a sigla aparece em telas, e-mails e títulos de página.',
            'observacao' => 'Os quatro campos são obrigatórios. Trocar esses valores muda o texto em todo o sistema, sem precisar editar código.',
        ],
        [
            'nome' => 'Nome do aplicativo',
            'como' => 'Nome completo e nome curto do aplicativo web instalável do Evento. O nome curto é o que aparece embaixo do ícone quando alguém instala na tela inicial do celular ou na área de trabalho.',
            'observacao' => 'Sem preenchimento, o aplicativo usa um nome padrão genérico.',
        ],
        [
            'nome' => 'Ícone do aplicativo',
            'como' => 'Envie uma imagem quadrada (mínimo 512×512 pixels). O sistema gera automaticamente os tamanhos necessários. Rejeitada se não for quadrada ou for menor que o mínimo.',
            'observacao' => 'Sem envio, o aplicativo usa um ícone padrão. Instalar de novo pode ser necessário para o celular/computador atualizar o ícone já salvo.',
        ],
        [
            'nome' => 'Desativar sistema',
            'como' => 'Bloqueia o acesso e desconecta todos os usuários, exceto administradores, imediatamente.',
            'observacao' => 'Confirmação reforçada, avisando o efeito exato; use só durante uma atualização. "Reativar sistema" desfaz, com aviso de que o acesso normal volta para todos na hora.',
        ],
    ],
    'conceitos' => [],
];
