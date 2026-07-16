<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>

<div class="site-page">
    <header class="site-header">
        <div class="site-header-inner">
            <img src="<?php echo htmlspecialchars($logoAdminSrc, ENT_QUOTES, 'UTF-8'); ?>" alt="Prêmio de Inovação TJRR" class="site-logo">
            <nav class="site-nav">
                <a href="<?php echo url('participante/index'); ?>" class="btn">Voltar</a>
            </nav>
        </div>
    </header>

    <div class="site-form-page">
<?php if ($erroGeral !== null && $preparo === null): ?>
    <h1>Formulário indisponível</h1>
    <p style="color:red;"><?php echo htmlspecialchars($erroGeral, ENT_QUOTES, 'UTF-8'); ?></p>
    </div>
</div>
    <?php return; ?>
<?php endif; ?>

<h1><?php echo htmlspecialchars($preparo['formulario']['nome'], ENT_QUOTES, 'UTF-8'); ?></h1>

<?php if (!empty($preparo['submissaoExistente'])): ?>
    <p><em>Você já enviou esta submissão — os dados abaixo são os que foram salvos. Alterar e enviar novamente atualiza a submissão existente.</em></p>
<?php endif; ?>

<?php if (!empty($preparo['formulario']['descricao'])): ?>
    <p><?php echo nl2br(htmlspecialchars($preparo['formulario']['descricao'], ENT_QUOTES, 'UTF-8')); ?></p>
<?php endif; ?>

<?php if ($erroGeral !== null): ?>
    <p style="color:red;"><?php echo htmlspecialchars($erroGeral, ENT_QUOTES, 'UTF-8'); ?></p>
<?php endif; ?>

<form method="post" action="<?php echo url('submissao/enviar/' . (int) $preparo['etapa']['id']); ?>" enctype="multipart/form-data">
    <?php foreach ($preparo['campos'] as $campo): ?>
        <?php
        $campoId = (int) $campo['id'];
        $config = $campo['config_json'] !== null ? json_decode($campo['config_json'], true) : [];
        $temErro = isset($erros[$campoId]);
        $valorAtual = isset($preparo['valoresExistentes'][(string) $campoId]) ? $preparo['valoresExistentes'][(string) $campoId] : null;
        ?>
        <fieldset style="margin-bottom:1em;">
            <label>
                <?php echo htmlspecialchars($campo['rotulo'], ENT_QUOTES, 'UTF-8'); ?>
                <?php echo $campo['obrigatorio'] ? '*' : ''; ?>

                <?php if ($campo['tipo'] === 'texto'): ?>
                    <input type="text" name="campos[<?php echo $campoId; ?>]" value="<?php echo htmlspecialchars((string) $valorAtual, ENT_QUOTES, 'UTF-8'); ?>" <?php echo $campo['obrigatorio'] ? 'required' : ''; ?>>

                <?php elseif ($campo['tipo'] === 'texto_longo'): ?>
                    <textarea name="campos[<?php echo $campoId; ?>]" rows="6" <?php echo $campo['obrigatorio'] ? 'required' : ''; ?>><?php echo htmlspecialchars((string) $valorAtual, ENT_QUOTES, 'UTF-8'); ?></textarea>

                <?php elseif ($campo['tipo'] === 'numero'): ?>
                    <input type="number" step="any" name="campos[<?php echo $campoId; ?>]" value="<?php echo htmlspecialchars((string) $valorAtual, ENT_QUOTES, 'UTF-8'); ?>" <?php echo $campo['obrigatorio'] ? 'required' : ''; ?>>

                <?php elseif ($campo['tipo'] === 'cpf'): ?>
                    <input type="text" name="campos[<?php echo $campoId; ?>]" class="campo-cpf-validar" placeholder="000.000.000-00" value="<?php echo htmlspecialchars((string) $valorAtual, ENT_QUOTES, 'UTF-8'); ?>" <?php echo $campo['obrigatorio'] ? 'required' : ''; ?>>

                <?php elseif ($campo['tipo'] === 'email'): ?>
                    <input type="email" name="campos[<?php echo $campoId; ?>]" value="<?php echo htmlspecialchars((string) $valorAtual, ENT_QUOTES, 'UTF-8'); ?>" <?php echo $campo['obrigatorio'] ? 'required' : ''; ?>>

                <?php elseif ($campo['tipo'] === 'telefone'): ?>
                    <input type="text" name="campos[<?php echo $campoId; ?>]" placeholder="(00) 00000-0000" value="<?php echo htmlspecialchars((string) $valorAtual, ENT_QUOTES, 'UTF-8'); ?>" <?php echo $campo['obrigatorio'] ? 'required' : ''; ?>>

                <?php elseif ($campo['tipo'] === 'link_youtube'): ?>
                    <input type="url" name="campos[<?php echo $campoId; ?>]" placeholder="https://www.youtube.com/watch?v=..." value="<?php echo htmlspecialchars((string) $valorAtual, ENT_QUOTES, 'UTF-8'); ?>" <?php echo $campo['obrigatorio'] ? 'required' : ''; ?>>

                <?php elseif ($campo['tipo'] === 'selecao_tema_desafio'): ?>
                    <select name="campos[<?php echo $campoId; ?>]" <?php echo $campo['obrigatorio'] ? 'required' : ''; ?>>
                        <option value="">Selecione...</option>
                        <?php $temaAberto = null; ?>
                        <?php foreach ($preparo['desafios'] as $desafio): ?>
                            <?php if ($desafio['tema_nome'] !== $temaAberto): ?>
                                <?php if ($temaAberto !== null): ?></optgroup><?php endif; ?>
                                <optgroup label="<?php echo htmlspecialchars($desafio['tema_nome'], ENT_QUOTES, 'UTF-8'); ?>">
                                <?php $temaAberto = $desafio['tema_nome']; ?>
                            <?php endif; ?>
                            <option value="<?php echo (int) $desafio['id']; ?>" <?php echo (string) $valorAtual === (string) $desafio['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($desafio['pergunta'], ENT_QUOTES, 'UTF-8'); ?>
                            </option>
                        <?php endforeach; ?>
                        <?php if ($temaAberto !== null): ?></optgroup><?php endif; ?>
                    </select>

                <?php elseif ($campo['tipo'] === 'upload_pdf'): ?>
                    <?php if (is_array($valorAtual) && isset($valorAtual['nome_original'])): ?>
                        <p>Arquivo já enviado: <strong><?php echo htmlspecialchars($valorAtual['nome_original'], ENT_QUOTES, 'UTF-8'); ?></strong> — escolha outro arquivo abaixo só se quiser substituí-lo.</p>
                    <?php endif; ?>
                    <input type="file" name="campos[<?php echo $campoId; ?>]" accept="application/pdf" <?php echo ($campo['obrigatorio'] && $valorAtual === null) ? 'required' : ''; ?>>
                    <br><small>PDF, até 15MB.</small>

                <?php elseif ($campo['tipo'] === 'grupo_participantes'): ?>
                    <?php
                    $minimo = isset($config['minimo_repeticoes']) ? (int) $config['minimo_repeticoes'] : 1;
                    $maximo = isset($config['maximo_repeticoes']) ? (int) $config['maximo_repeticoes'] : 10;
                    $linhasExistentes = is_array($valorAtual) ? $valorAtual : [];
                    $totalLinhasIniciais = max($minimo, count($linhasExistentes));
                    ?>
                    <div class="grupo-participantes" data-campo-id="<?php echo $campoId; ?>" data-maximo="<?php echo $maximo; ?>" data-proximo-indice="<?php echo $totalLinhasIniciais; ?>">
                        <div class="grupo-participantes-linhas">
                            <?php for ($indice = 0; $indice < $totalLinhasIniciais; $indice++): ?>
                                <?php $participanteExistente = isset($linhasExistentes[$indice]) ? $linhasExistentes[$indice] : null; ?>
                                <?php include __DIR__ . '/_grupo_participante_linha.php'; ?>
                            <?php endfor; ?>
                        </div>
                        <button type="button" class="grupo-participantes-adicionar">+ Adicionar participante</button>
                    </div>

                <?php endif; ?>
            </label>

            <?php if ($temErro): ?>
                <br><span style="color:red;"><?php echo htmlspecialchars($erros[$campoId], ENT_QUOTES, 'UTF-8'); ?></span>
            <?php endif; ?>
        </fieldset>
    <?php endforeach; ?>

    <button type="submit" class="btn btn-bordered">Enviar</button>
</form>

<template id="template-grupo-participante-linha">
    <?php $campoId = '__ID__'; $indice = '__INDICE__'; ?>
    <?php include __DIR__ . '/_grupo_participante_linha.php'; ?>
</template>

<script src="<?php echo config('base_path'); ?>/assets/js/formulario-publico.js"></script>
<script src="<?php echo config('base_path'); ?>/assets/js/cpf-validador.js"></script>
<script src="<?php echo config('base_path'); ?>/assets/js/manter-sessao-viva.js" data-url="<?php echo url('sessao/manterViva'); ?>"></script>
    </div>
</div>
