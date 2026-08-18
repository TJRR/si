<?php

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

/**
 * Fase 31: ordem explícita de capítulos/telas do manual, independente da
 * árvore de diretórios de app/Ajuda/ (que espelha Views/rotas, não a
 * organização narrativa do inventário). Cada grupo é uma área de navegação
 * de InventarioTelasFase31_SistemaAjuda.md, na mesma ordem do documento;
 * cada entrada de 'telas' é a chave de $view (o mesmo identificador usado
 * por Controller::renderizar()) de uma tela com conteúdo em app/Ajuda/.
 *
 * Fora do escopo de propósito (não navegáveis / decisão explícita, ver
 * README e o próprio inventário): politica.php/termos.php (standalone,
 * fora do roteador), popups de modal (resultados/popupSubmissao,
 * resultados/popupNotas, oficinaAdmin/inscritos), downloads puros
 * (relatorioPdf, relatorioNotasPdf, ValidacaoPublicaController::pdf),
 * notificacoesPainel/* e navegacao/filhos (endpoints sem view própria).
 *
 * database/gerar_manual_markdown.php (rodado localmente, fora do sistema)
 * usa este arquivo para decidir a ordem do manual.
 */
return [
    ['area' => 'Autenticação e Cadastro', 'telas' => [
        'auth/login',
        'auth/cadastro',
        'auth/esqueci_senha',
        'auth/definir_senha',
    ]],
    ['area' => 'Home Pública e Páginas Estáticas', 'telas' => [
        'home/index',
    ]],
    ['area' => 'Painel Administrativo (dashboard)', 'telas' => [
        'home/administrativo',
    ]],
    ['area' => 'Núcleo do Concurso', 'telas' => [
        'admin/concursos/index',
        'admin/concursos/form',
        'admin/trilhas/index',
        'admin/trilhas/form',
        'admin/etapas/index',
        'admin/etapas/form',
        'admin/etapas/formulario_vinculado',
        'admin/temas/index',
        'admin/temas/form',
        'admin/temas/desafios',
        'admin/temas/form_desafio',
        'admin/formularios/index',
        'admin/formularios/form',
        'admin/formularios/campos',
        'admin/campos/form',
        'admin/formularios/duplicar',
        'admin/criterios/index',
        'admin/criterios/form',
        'admin/formulas/etapa',
        'admin/formulas/trilha',
        'admin/desempate/index',
        'admin/desempate/form',
        'admin/apuracao/index',
        'admin/designacoes/index',
        'admin/designacoes/progresso',
        'admin/designacoes/distribuir_previa',
        'admin/designacoes/selecionar_avaliadores',
        'admin/categorias_avaliador/index',
        'admin/categorias_avaliador/form',
        'admin/vagas_avaliador/index',
        'admin/resultados/etapa',
        'admin/resultados/trilha',
        'admin/resultados/destaque',
        'admin/homologacao/index',
    ]],
    ['area' => 'Avaliação (Avaliador)', 'telas' => [
        'avaliacao/index',
        'avaliacao/submissoes',
        'avaliacao/notar',
        'avaliacao/notar_compartilhado',
    ]],
    ['area' => 'Conteúdo do Site / Identidade Visual', 'telas' => [
        'admin/slides/index',
        'admin/slides/form',
        'admin/banners/index',
        'admin/banners/form',
        'admin/blocos/index',
        'admin/blocos/form',
        'admin/premios/index',
        'admin/premios/form',
        'admin/contatos_concurso/form',
        'admin/contatos_concurso/mensagens',
        'admin/faq/index',
        'admin/faq/form',
        'admin/faq_concurso/index',
        'admin/documentos/index',
        'admin/documentos/form',
        'admin/documentos/editar',
        'admin/documentos/historico',
        'admin/midia/index',
        'admin/midia/form',
        'admin/eventos_cronograma/index',
        'admin/eventos_cronograma/form',
        'admin/ordenacao_home/index',
        'admin/tema/form',
        'admin/tema/cabecalho',
        'admin/tema/rodape',
        'admin/conteudo/form',
    ]],
    ['area' => 'Administração Geral', 'telas' => [
        'admin/auditoria/index',
        'admin/configuracoes/index',
        'admin/usuarios',
        'admin/usuarios_editar',
        'admin/usuarios_convidar',
        'meuPerfil/index',
    ]],
    ['area' => 'Mentorias e Oficinas', 'telas' => [
        'admin/mentorias/index',
        'admin/mentorias/form',
        'admin/oficinas/index',
        'admin/oficinas/form',
        'participante/mentorias',
        'participante/oficinas',
        'publico/mentorias',
        'publico/oficinas',
    ]],
    ['area' => 'Dúvidas (Tira-Dúvidas)', 'telas' => [
        'admin/duvidas/minhas_escaladas',
        'admin/duvidas/ver',
        'participante/duvidas',
        'participante/duvida_form',
        'participante/duvida_ver',
    ]],
    ['area' => 'Requerimentos', 'telas' => [
        'admin/requerimentos/minhas_escaladas',
        'admin/requerimentos/ver',
        'participante/requerimentos',
        'participante/requerimento_novo',
        'participante/requerimento_ver',
        'admin/modelos_documento/index',
        'admin/modelos_documento/form',
    ]],
    ['area' => 'Painel do Participante', 'telas' => [
        'participante/painel',
        'participante/editar_equipe',
        'participante/meus_dados',
        'participante/feedback',
    ]],
    ['area' => 'Público (sem login)', 'telas' => [
        'publico/formulario',
        'publico/sucesso',
        'publico/inscricao',
        'publico/inscricao_sucesso',
        'publico/equipes_homologadas',
        'publico/resultado_etapa',
        'publico/edicoes/index',
        'publico/edicoes/detalhe',
    ]],
];
