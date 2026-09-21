<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<div class="pagina-titulo-acoes">
    <h1>Trabalhos: <?php echo htmlspecialchars($evento['nome'], ENT_QUOTES, 'UTF-8'); ?></h1>
    <div class="pagina-titulo-botoes">
        <a href="<?php echo url('eventos/editar/' . (int) $evento['id']); ?>" class="btn-voltar">Voltar</a>
    </div>
</div>

<p style="color:#555;font-size:0.9em;">Configuração do processo de submissão e avaliação de artigos e resumos expandidos deste evento. Todo valor aqui é próprio desta edição, nada fica fixo no sistema.</p>

<h2>Configurações gerais</h2>
<form method="post" action="<?php echo url('trabalhos/index/' . (int) $evento['id']); ?>"><?= campoCsrf() ?>
    <input type="hidden" name="acao" value="salvar_config">

    <fieldset>
        <legend>Prazos</legend>
        <label>Abertura da submissão:
            <input type="datetime-local" name="data_abertura_submissao" value="<?php echo $config !== null && $config['data_abertura_submissao'] !== null ? str_replace(' ', 'T', substr($config['data_abertura_submissao'], 0, 16)) : ''; ?>">
        </label>
        <label>Prazo final de submissão:
            <input type="datetime-local" name="data_fim_submissao" value="<?php echo $config !== null && $config['data_fim_submissao'] !== null ? str_replace(' ', 'T', substr($config['data_fim_submissao'], 0, 16)) : ''; ?>">
        </label>
        <label>Início da avaliação:
            <input type="datetime-local" name="data_inicio_avaliacao" value="<?php echo $config !== null && $config['data_inicio_avaliacao'] !== null ? str_replace(' ', 'T', substr($config['data_inicio_avaliacao'], 0, 16)) : ''; ?>">
        </label>
        <label>Fim da avaliação:
            <input type="datetime-local" name="data_fim_avaliacao" value="<?php echo $config !== null && $config['data_fim_avaliacao'] !== null ? str_replace(' ', 'T', substr($config['data_fim_avaliacao'], 0, 16)) : ''; ?>">
        </label>
    </fieldset>

    <fieldset>
        <legend>Autoria e duplicidade</legend>
        <label>Quantidade máxima de autores por trabalho (incluindo o autor principal):
            <input type="number" name="quantidade_maxima_autores" min="1" max="20" value="<?php echo $config !== null ? (int) $config['quantidade_maxima_autores'] : 2; ?>" required>
        </label>
        <label>
            <input type="checkbox" name="permite_multiplos_trabalhos_por_pessoa" <?php echo ($config !== null && (int) $config['permite_multiplos_trabalhos_por_pessoa'] === 1) ? 'checked' : ''; ?>>
            Permitir que a mesma pessoa (mesmo CPF) conste em mais de um trabalho deste evento
        </label>
    </fieldset>

    <fieldset>
        <legend>Avaliação</legend>
        <label>Quantidade de avaliadores por trabalho:
            <input type="number" name="quantidade_avaliadores_por_trabalho" min="1" max="10" value="<?php echo $config !== null ? (int) $config['quantidade_avaliadores_por_trabalho'] : 2; ?>" required>
        </label>
        <label>
            <input type="checkbox" name="sigilo_cego" <?php echo ($config === null || (int) $config['sigilo_cego'] === 1) ? 'checked' : ''; ?>>
            Avaliação às cegas (avaliador nunca vê o nome do autor; para os métodos de arquivo/endereço eletrônico, exige uma segunda versão sem identificação)
        </label>
        <label>Como combinar as notas dos avaliadores:
            <select name="metodo_agregacao_nota">
                <option value="media_aritmetica" <?php echo ($config === null || $config['metodo_agregacao_nota'] === 'media_aritmetica') ? 'selected' : ''; ?>>Média aritmética</option>
                <option value="mediana" <?php echo ($config !== null && $config['metodo_agregacao_nota'] === 'mediana') ? 'selected' : ''; ?>>Mediana</option>
            </select>
        </label>
        <label>Nota de corte para aprovação (deixe em branco para não exigir corte):
            <input type="number" step="0.01" name="nota_corte_aprovacao" value="<?php echo $config !== null && $config['nota_corte_aprovacao'] !== null ? htmlspecialchars($config['nota_corte_aprovacao'], ENT_QUOTES, 'UTF-8') : ''; ?>">
        </label>
        <label>Seleção entre os aprovados:
            <select name="regra_selecao_tipo">
                <option value="todos_aprovados" <?php echo ($config === null || $config['regra_selecao_tipo'] === 'todos_aprovados') ? 'selected' : ''; ?>>Todos os aprovados</option>
                <option value="numero_fixo" <?php echo ($config !== null && $config['regra_selecao_tipo'] === 'numero_fixo') ? 'selected' : ''; ?>>Número fixo de selecionados</option>
                <option value="percentual" <?php echo ($config !== null && $config['regra_selecao_tipo'] === 'percentual') ? 'selected' : ''; ?>>Percentual dos aprovados</option>
            </select>
        </label>
        <label>Valor (número ou percentual, conforme a opção acima):
            <input type="number" step="0.01" name="regra_selecao_valor" value="<?php echo $config !== null && $config['regra_selecao_valor'] !== null ? htmlspecialchars($config['regra_selecao_valor'], ENT_QUOTES, 'UTF-8') : ''; ?>">
        </label>
    </fieldset>

    <fieldset>
        <legend>Formulário de submissão</legend>
        <label>
            <input type="checkbox" name="exige_telefone_contato" <?php echo ($config === null || (int) $config['exige_telefone_contato'] === 1) ? 'checked' : ''; ?>>
            Exigir telefone de contato
        </label>

        <p>Métodos de submissão aceitos:</p>
        <?php
        $metodosAtuais = $config !== null && $config['metodos_submissao_json'] !== null ? json_decode($config['metodos_submissao_json'], true) : ['formulario', 'link_externo', 'documento_nao_editavel'];
        $opcoesMetodo = [
            'formulario' => 'Formulário direto na interface (texto digitado)',
            'link_externo' => 'Endereço eletrônico de documento externo (Google Drive, OneDrive, etc.)',
            'documento_editavel' => 'Documento editável (Word, OpenDocument, etc.)',
            'documento_nao_editavel' => 'Documento não editável (PDF)',
        ];
        ?>
        <?php foreach ($opcoesMetodo as $chave => $rotulo): ?>
            <label style="display:block;">
                <input type="checkbox" name="metodos[]" value="<?php echo $chave; ?>" <?php echo in_array($chave, (array) $metodosAtuais, true) ? 'checked' : ''; ?>>
                <?php echo htmlspecialchars($rotulo, ENT_QUOTES, 'UTF-8'); ?>
            </label>
        <?php endforeach; ?>

        <p>Extensões aceitas em "documento editável" (nada disso impede PDF, que é sempre aceito em "documento não editável"):</p>
        <?php
        $extensoesAtuais = $config !== null && $config['extensoes_editavel_json'] !== null ? json_decode($config['extensoes_editavel_json'], true) : ['doc', 'docx', 'odt', 'rtf'];
        $rotulosExtensao = ['doc' => '.doc', 'docx' => '.docx', 'odt' => '.odt', 'rtf' => '.rtf', 'pages' => '.pages (Apple Pages, verificação de conteúdo menos confiável)', 'wpd' => '.wpd (WordPerfect, formato raro, avaliador pode não conseguir abrir)'];
        ?>
        <?php foreach ($rotulosExtensao as $extensao => $rotulo): ?>
            <label style="display:block;">
                <input type="checkbox" name="extensoes[]" value="<?php echo $extensao; ?>" <?php echo in_array($extensao, (array) $extensoesAtuais, true) ? 'checked' : ''; ?>>
                <?php echo htmlspecialchars($rotulo, ENT_QUOTES, 'UTF-8'); ?>
            </label>
        <?php endforeach; ?>

        <label>Tamanho máximo do arquivo (MB):
            <input type="number" name="tamanho_maximo_mb" min="1" max="100" value="<?php echo $config !== null ? (int) $config['tamanho_maximo_mb'] : 15; ?>" required>
        </label>
    </fieldset>

    <fieldset>
        <legend>Situação</legend>
        <label>
            <select name="situacao">
                <option value="rascunho" <?php echo $situacaoAtual === 'rascunho' ? 'selected' : ''; ?>>Rascunho (formulário de submissão indisponível)</option>
                <option value="publicado" <?php echo $situacaoAtual === 'publicado' ? 'selected' : ''; ?>>Publicado (submissão aberta a quem estiver no prazo)</option>
                <option value="encerrado" <?php echo $situacaoAtual === 'encerrado' ? 'selected' : ''; ?>>Encerrado</option>
            </select>
        </label>
    </fieldset>

    <button type="submit">Salvar configurações</button>
</form>
