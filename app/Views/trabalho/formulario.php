<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>

<div class="site-page">
    <?php
    $eventoId = $evento['id'];
    $tituloTopo = $evento['nome'];
    require __DIR__ . '/../eventoApp/_app_bar.php';
    ?>

    <div class="site-form-page">
        <?php require __DIR__ . '/../eventoApp/_ajuda_card.php'; ?>
        <h2>Submeter trabalho</h2>

        <?php if (!empty($erro)): ?>
            <p class="flash-mensagem erro"><?php echo htmlspecialchars($erro, ENT_QUOTES, 'UTF-8'); ?></p>
        <?php endif; ?>

        <form method="post" action="<?php echo url('trabalho/formulario/' . (int) $evento['id']); ?>" enctype="multipart/form-data"><?= campoCsrf() ?>

            <div class="admin-card">
                <h3>Dados do trabalho</h3>
                <label>Título: <input type="text" name="titulo" required maxlength="255"></label>

                <?php if (!empty($eixos)): ?>
                    <label>Eixo temático:
                        <select name="eixo_tematico_id" required>
                            <?php foreach ($eixos as $eixo): ?>
                                <option value="<?php echo (int) $eixo['id']; ?>"><?php echo htmlspecialchars($eixo['nome'], ENT_QUOTES, 'UTF-8'); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                <?php endif; ?>

                <?php if (!empty($naturezas)): ?>
                    <label>Natureza:
                        <select name="natureza_id" required>
                            <?php foreach ($naturezas as $natureza): ?>
                                <option value="<?php echo (int) $natureza['id']; ?>"><?php echo htmlspecialchars($natureza['nome'], ENT_QUOTES, 'UTF-8'); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                <?php endif; ?>

                <?php if ((int) $config['exige_telefone_contato'] === 1): ?>
                    <label>Telefone para contato: <input type="tel" name="telefone_contato" required maxlength="20"></label>
                <?php endif; ?>
            </div>

            <div class="admin-card">
                <h3>Autor principal</h3>
                <label>Nome completo: <input type="text" name="autor_nome" required maxlength="150" value="<?php echo htmlspecialchars($usuario['nome'], ENT_QUOTES, 'UTF-8'); ?>"></label>
                <?php $cpfPreenchido = ($perfilPessoa !== null && $perfilPessoa['tipo_documento'] === 'CPF') ? $perfilPessoa['documento'] : ''; ?>
                <label>CPF: <input type="text" name="autor_cpf" required maxlength="14" class="campo-cpf-validar" value="<?php echo htmlspecialchars((string) $cpfPreenchido, ENT_QUOTES, 'UTF-8'); ?>"></label>
                <label>E-mail: <input type="email" name="autor_email" required maxlength="150" value="<?php echo htmlspecialchars($usuario['email'], ENT_QUOTES, 'UTF-8'); ?>"></label>
                <label>Cargo: <input type="text" name="autor_cargo" maxlength="150" value="<?php echo htmlspecialchars($perfilPessoa !== null ? (string) $perfilPessoa['cargo'] : '', ENT_QUOTES, 'UTF-8'); ?>"></label>
                <label>Órgão de origem: <input type="text" name="autor_orgao_origem" maxlength="150" value="<?php echo htmlspecialchars($perfilPessoa !== null ? (string) $perfilPessoa['orgao_origem'] : '', ENT_QUOTES, 'UTF-8'); ?>"></label>
            </div>

            <div class="admin-card" id="trabalho-coautores">
                <h3>Coautores</h3>
                <div id="trabalho-coautores-lista"></div>
                <?php if ((int) $config['quantidade_maxima_autores'] > 1): ?>
                    <button type="button" id="trabalho-coautor-adicionar" class="btn">Adicionar coautor</button>
                <?php endif; ?>
            </div>

            <div class="admin-card">
                <h3>Conteúdo do trabalho</h3>
                <label>Forma de envio:
                    <select name="metodo_submissao" id="trabalho-metodo">
                        <?php foreach ($metodosHabilitados as $metodo): ?>
                            <?php
                            $rotulosMetodo = [
                                'formulario' => 'Digitar o texto aqui',
                                'link_externo' => 'Endereço eletrônico de documento externo',
                                'documento_editavel' => 'Enviar documento editável',
                                'documento_nao_editavel' => 'Enviar documento em PDF',
                            ];
                            ?>
                            <option value="<?php echo $metodo; ?>"><?php echo htmlspecialchars($rotulosMetodo[$metodo], ENT_QUOTES, 'UTF-8'); ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>

                <div data-metodo-secao="formulario">
                    <p>Não escreva seu nome nem outra forma de identificação dentro do texto: o avaliador não deve conseguir identificar o autor.</p>
                    <label>Texto do trabalho:
                        <textarea name="conteudo_html" rows="16" cols="80"></textarea>
                    </label>
                </div>

                <div data-metodo-secao="link_externo">
                    <label>Endereço eletrônico sem identificação (o avaliador vê este):
                        <input type="url" name="link_avaliacao" placeholder="https://">
                    </label>
                    <?php if ((int) $config['sigilo_cego'] === 1): ?>
                        <label>Endereço eletrônico da versão completa (identificada, usada se aprovado):
                            <input type="url" name="link_publicacao" placeholder="https://">
                        </label>
                    <?php endif; ?>
                </div>

                <?php if ((int) $config['sigilo_cego'] === 1): ?>
                    <p>Como a avaliação deste evento é às cegas, são <strong>dois arquivos obrigatórios</strong>, com o mesmo conteúdo, diferindo só na identificação:
                    (1) uma versão <strong>sem qualquer identificação</strong> de autor, orientador ou instituição (nem no corpo do texto, nem nas propriedades do arquivo, nem em agradecimentos ou notas de rodapé), que é a única que o avaliador chega a ver;
                    (2) uma versão <strong>completa</strong>, com nome e vínculo institucional de todos os autores, usada só se o trabalho for aprovado. Sem os dois arquivos preenchidos, a submissão não é enviada.</p>
                <?php endif; ?>

                <div data-metodo-secao="documento_editavel">
                    <p>Extensões aceitas: <?php echo htmlspecialchars(implode(', ', $extensoesHabilitadas), ENT_QUOTES, 'UTF-8'); ?>. Tamanho máximo: <?php echo (int) $config['tamanho_maximo_mb']; ?>MB.</p>
                    <label>Arquivo sem identificação (obrigatório; é este que o avaliador vê):
                        <input type="file" name="arquivo_avaliacao">
                    </label>
                    <?php if ((int) $config['sigilo_cego'] === 1): ?>
                        <label>Arquivo da versão completa, com identificação (obrigatório; usado só se aprovado):
                            <input type="file" name="arquivo_publicacao">
                        </label>
                    <?php endif; ?>
                </div>

                <div data-metodo-secao="documento_nao_editavel">
                    <p>Tamanho máximo: <?php echo (int) $config['tamanho_maximo_mb']; ?>MB.</p>
                    <label>Arquivo em PDF sem identificação (obrigatório; é este que o avaliador vê):
                        <input type="file" name="arquivo_avaliacao">
                    </label>
                    <?php if ((int) $config['sigilo_cego'] === 1): ?>
                        <label>Arquivo em PDF da versão completa, com identificação (obrigatório; usado só se aprovado):
                            <input type="file" name="arquivo_publicacao">
                        </label>
                    <?php endif; ?>
                </div>
            </div>

            <?php if (!empty($termos)): ?>
                <div class="trabalho-termos">
                    <h3>Declarações</h3>
                    <?php foreach ($termos as $termo): ?>
                        <label class="trabalho-termo">
                            <input type="checkbox" name="termos_aceitos[]" value="<?php echo (int) $termo['id']; ?>"
                                <?php echo in_array((int) $termo['id'], array_map('intval', $termosMarcados), true) ? 'checked' : ''; ?>
                                <?php echo (int) $termo['obrigatorio'] === 1 ? 'required' : ''; ?>>
                            <span class="trabalho-termo-texto">
                                <?php echo $termo['texto_html']; ?>
                                <?php if ((int) $termo['obrigatorio'] === 1): ?>
                                    <strong class="trabalho-termo-obrigatorio">(obrigatório)</strong>
                                <?php endif; ?>
                            </span>
                        </label>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <button type="submit" class="btn">Enviar trabalho</button>
        </form>
    </div>
</div>

<template id="trabalho-coautor-modelo">
    <fieldset class="trabalho-coautor-item">
        <label>Nome completo: <input type="text" name="coautor_nome[]" maxlength="150"></label>
        <label>CPF: <input type="text" name="coautor_cpf[]" maxlength="14" class="campo-cpf-validar"></label>
        <label>E-mail: <input type="email" name="coautor_email[]" maxlength="150"></label>
        <label>Cargo: <input type="text" name="coautor_cargo[]" maxlength="150"></label>
        <label>Órgão de origem: <input type="text" name="coautor_orgao_origem[]" maxlength="150"></label>
        <button type="button" class="trabalho-coautor-remover btn">Remover coautor</button>
    </fieldset>
</template>

<script>
(function () {
    var selecaoMetodo = document.getElementById('trabalho-metodo');
    var secoes = document.querySelectorAll('[data-metodo-secao]');

    // Achado real do usuário: os 4 métodos ficam todos no HTML ao mesmo
    // tempo (só o CSS troca qual aparece), e o de "documento editável" e
    // o de "documento em PDF" reaproveitam os MESMOS nomes de campo
    // (arquivo_avaliacao/arquivo_publicacao). "display: none" esconde da
    // tela, mas não tira o campo do envio do formulário - o navegador
    // manda os 2 campos de arquivo do método escondido também (vazios),
    // e o servidor ficava com o último, que substituía o arquivo real
    // enviado no método visível. Corrigido desabilitando os campos de
    // toda seção que não é a selecionada - campo desabilitado não entra
    // no envio do formulário, de jeito nenhum.
    function atualizarSecoes() {
        secoes.forEach(function (secao) {
            var ativa = secao.getAttribute('data-metodo-secao') === selecaoMetodo.value;
            secao.style.display = ativa ? '' : 'none';
            secao.querySelectorAll('input, textarea, select').forEach(function (campo) {
                campo.disabled = !ativa;
            });
        });
    }

    if (selecaoMetodo) {
        selecaoMetodo.addEventListener('change', atualizarSecoes);
        atualizarSecoes();
    }

    var botaoAdicionar = document.getElementById('trabalho-coautor-adicionar');
    var lista = document.getElementById('trabalho-coautores-lista');
    var modelo = document.getElementById('trabalho-coautor-modelo');

    if (botaoAdicionar && lista && modelo) {
        botaoAdicionar.addEventListener('click', function () {
            var clone = modelo.content.cloneNode(true);
            lista.appendChild(clone);
        });

        lista.addEventListener('click', function (evento) {
            if (evento.target.classList.contains('trabalho-coautor-remover')) {
                evento.target.closest('.trabalho-coautor-item').remove();
            }
        });
    }
})();
</script>
<script src="<?php echo config('base_path'); ?>/assets/js/cpf-validador.js"></script>
