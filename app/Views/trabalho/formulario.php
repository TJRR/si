<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<?php
/**
 * Reabertura da Fase 51 (achados da equipe de Teste Cego): o formulario
 * volta preenchido depois de um erro ($valores; so' os arquivos precisam
 * ser escolhidos de novo, limitacao do navegador), marca o que e' obrigatorio
 * e o que e' opcional, mostra o erro numa caixa em destaque e junto do
 * campo que o causou ($campoErro/$indiceErro), so' oferece a escolha da
 * forma de envio quando ha' mais de uma habilitada e respeita o limite de
 * autores da configuracao. O comportamento em tela fica em
 * assets/js/trabalho-formulario.js.
 */
$valores = isset($valores) && is_array($valores) ? $valores : [];
$campoErro = isset($campoErro) ? $campoErro : null;
$indiceErro = isset($indiceErro) ? $indiceErro : null;

$e = function ($texto) {
    return htmlspecialchars((string) $texto, ENT_QUOTES, 'UTF-8');
};
$v = function (array $origem, $chave, $padrao = '') use ($e) {
    return $e(isset($origem[$chave]) && is_string($origem[$chave]) ? $origem[$chave] : $padrao);
};
$temErro = function ($nome, $indice = null) use ($campoErro, $indiceErro) {
    return $campoErro === $nome && ($indice === null || (string) $indiceErro === (string) $indice);
};
$classeErro = function ($nome, $indice = null) use ($temErro) {
    return $temErro($nome, $indice) ? 'campo-com-erro' : '';
};
$mensagemErro = function ($nome, $indice = null) use ($temErro, $erro, $e) {
    return $temErro($nome, $indice) ? '<span class="campo-erro-msg" role="alert">' . $e($erro) . '</span>' : '';
};
$focoErro = function ($nome, $indice = null) use ($temErro) {
    return $temErro($nome, $indice) ? ' data-foco-erro' : '';
};
$rotulo = function ($texto, $obrigatorio) use ($e) {
    return $e($texto) . ($obrigatorio
        ? ' <span class="obrigatorio" title="Campo obrigatório">*</span>'
        : ' <span class="opcional">(opcional)</span>');
};

$metodosHabilitados = array_values($metodosHabilitados);
$metodoAtual = isset($valores['metodo_submissao']) && in_array($valores['metodo_submissao'], $metodosHabilitados, true)
    ? $valores['metodo_submissao']
    : (isset($metodosHabilitados[0]) ? $metodosHabilitados[0] : '');
$rotulosMetodo = [
    'formulario' => 'Digitar o texto aqui',
    'link_externo' => 'Endereço eletrônico de documento externo',
    'documento_editavel' => 'Enviar documento editável',
    'documento_nao_editavel' => 'Enviar documento em PDF',
];
$metodoTemArquivo = in_array('documento_editavel', $metodosHabilitados, true) || in_array('documento_nao_editavel', $metodosHabilitados, true);
$sigiloCego = (int) $config['sigilo_cego'] === 1;
$tamanhoMaximoMb = (int) $config['tamanho_maximo_mb'];

$maxAutores = (int) $config['quantidade_maxima_autores'];
$maxCoautores = max(0, $maxAutores - 1);
$coautoresPreenchidos = [];

if (isset($valores['coautor_nome']) && is_array($valores['coautor_nome'])) {
    foreach ($valores['coautor_nome'] as $posicaoCoautor => $nomeCoautor) {
        $pegar = function ($campo) use ($valores, $posicaoCoautor) {
            return isset($valores[$campo][$posicaoCoautor]) && is_string($valores[$campo][$posicaoCoautor]) ? $valores[$campo][$posicaoCoautor] : '';
        };
        $coautoresPreenchidos[(int) $posicaoCoautor] = [
            'nome' => is_string($nomeCoautor) ? $nomeCoautor : '',
            'cpf' => $pegar('coautor_cpf'),
            'email' => $pegar('coautor_email'),
            'cargo' => $pegar('coautor_cargo'),
            'orgao' => $pegar('coautor_orgao_origem'),
        ];
    }
}

$coautoresPreenchidos = array_slice($coautoresPreenchidos, 0, $maxCoautores, true);
$termosMarcadosInt = array_map('intval', $termosMarcados);
$inscricaoAutomatica = !empty($config['inscrever_autores_ao_submeter']);
?>

<div class="site-page">
    <?php
    $eventoId = $evento['id'];
    $tituloTopo = $evento['nome'];
    $urlVoltar = url('eventoApp/index/' . (int) $eventoId);
    require __DIR__ . '/../eventoApp/_app_bar.php';
    ?>

    <div class="site-form-page">
        <?php require __DIR__ . '/../eventoApp/_ajuda_card.php'; ?>
        <h2>Submeter trabalho</h2>

        <?php if (!empty($erro)): ?>
            <div class="app-flash erro trabalho-erro-caixa" id="trabalho-erro-caixa" role="alert" tabindex="-1">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <circle cx="12" cy="12" r="10"></circle>
                    <line x1="15" y1="9" x2="9" y2="15"></line>
                    <line x1="9" y1="9" x2="15" y2="15"></line>
                </svg>
                <div>
                    <strong>Seu trabalho NÃO foi enviado.</strong>
                    <span class="trabalho-erro-texto"><?php echo $e($erro); ?></span>
                    <span class="trabalho-erro-orientacao">
                        Corrija o que está indicado e envie de novo. O que você preencheu foi mantido<?php echo ($metodoTemArquivo && in_array($metodoAtual, ['documento_editavel', 'documento_nao_editavel'], true)) ? ', mas o navegador não guarda arquivos: escolha o(s) arquivo(s) outra vez' : ''; ?>.
                    </span>
                </div>
            </div>
        <?php endif; ?>

        <p class="trabalho-legenda"><span class="obrigatorio">*</span> campo obrigatório</p>

        <form method="post" id="trabalho-formulario" action="<?php echo url('trabalho/formulario/' . (int) $evento['id']); ?>" enctype="multipart/form-data"><?= campoCsrf() ?>

            <div class="admin-card">
                <h3>Dados do trabalho</h3>
                <label class="<?php echo $classeErro('titulo'); ?>"><?php echo $rotulo('Título', true); ?>
                    <input type="text" name="titulo" required maxlength="255" value="<?php echo $v($valores, 'titulo'); ?>"<?php echo $focoErro('titulo'); ?>>
                    <?php echo $mensagemErro('titulo'); ?>
                </label>

                <?php if (!empty($eixos)): ?>
                    <label><?php echo $rotulo('Eixo temático', true); ?>
                        <select name="eixo_tematico_id" required>
                            <?php foreach ($eixos as $eixo): ?>
                                <option value="<?php echo (int) $eixo['id']; ?>"<?php echo (isset($valores['eixo_tematico_id']) && (int) $valores['eixo_tematico_id'] === (int) $eixo['id']) ? ' selected' : ''; ?>><?php echo $e($eixo['nome']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                <?php endif; ?>

                <?php if (!empty($naturezas)): ?>
                    <label><?php echo $rotulo('Natureza', true); ?>
                        <select name="natureza_id" required>
                            <?php foreach ($naturezas as $natureza): ?>
                                <option value="<?php echo (int) $natureza['id']; ?>"<?php echo (isset($valores['natureza_id']) && (int) $valores['natureza_id'] === (int) $natureza['id']) ? ' selected' : ''; ?>><?php echo $e($natureza['nome']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                <?php endif; ?>

                <?php if ((int) $config['exige_telefone_contato'] === 1): ?>
                    <label class="<?php echo $classeErro('telefone_contato'); ?>"><?php echo $rotulo('Telefone para contato', true); ?>
                        <input type="tel" name="telefone_contato" required maxlength="15" inputmode="tel" class="campo-telefone" placeholder="(00) 00000-0000" value="<?php echo $v($valores, 'telefone_contato'); ?>"<?php echo $focoErro('telefone_contato'); ?>>
                        <?php echo $mensagemErro('telefone_contato'); ?>
                    </label>
                <?php endif; ?>
            </div>

            <div class="admin-card">
                <h3>Autor principal</h3>
                <label class="<?php echo $classeErro('autor_nome'); ?>"><?php echo $rotulo('Nome completo', true); ?>
                    <input type="text" name="autor_nome" required maxlength="150" value="<?php echo $v($valores, 'autor_nome', $usuario['nome']); ?>"<?php echo $focoErro('autor_nome'); ?>>
                    <?php echo $mensagemErro('autor_nome'); ?>
                </label>
                <?php $cpfPreenchido = ($perfilPessoa !== null && $perfilPessoa['tipo_documento'] === 'CPF') ? (string) $perfilPessoa['documento'] : ''; ?>
                <label class="<?php echo $classeErro('autor_cpf'); ?>"><?php echo $rotulo('CPF', true); ?>
                    <input type="text" name="autor_cpf" required maxlength="14" class="campo-cpf-validar" value="<?php echo $v($valores, 'autor_cpf', $cpfPreenchido); ?>"<?php echo $focoErro('autor_cpf'); ?>>
                    <?php echo $mensagemErro('autor_cpf'); ?>
                </label>
                <label class="<?php echo $classeErro('autor_email'); ?>"><?php echo $rotulo('E-mail', true); ?>
                    <input type="email" name="autor_email" required maxlength="150" value="<?php echo $v($valores, 'autor_email', $usuario['email']); ?>"<?php echo $focoErro('autor_email'); ?>>
                    <?php echo $mensagemErro('autor_email'); ?>
                </label>
                <label><?php echo $rotulo('Cargo', false); ?>
                    <input type="text" name="autor_cargo" maxlength="150" value="<?php echo $v($valores, 'autor_cargo', $perfilPessoa !== null ? (string) $perfilPessoa['cargo'] : ''); ?>">
                </label>
                <label><?php echo $rotulo('Órgão de origem', false); ?>
                    <input type="text" name="autor_orgao_origem" maxlength="150" value="<?php echo $v($valores, 'autor_orgao_origem', $perfilPessoa !== null ? (string) $perfilPessoa['orgao_origem'] : ''); ?>">
                </label>
            </div>

            <?php if ($maxCoautores > 0): ?>
                <div class="admin-card <?php echo $classeErro('coautores'); ?>" id="trabalho-coautores" data-max-coautores="<?php echo $maxCoautores; ?>"<?php echo $temErro('coautores') ? ' tabindex="-1" data-foco-erro' : ''; ?>>
                    <h3>Coautores</h3>
                    <p class="trabalho-coautores-info">
                        Este evento aceita até <?php echo $maxAutores; ?> <?php echo $maxAutores === 1 ? 'autor' : 'autores'; ?>, incluindo o autor principal
                        (até <?php echo $maxCoautores; ?> <?php echo $maxCoautores === 1 ? 'coautor' : 'coautores'; ?>). Coautor é opcional.
                    </p>
                    <?php echo $mensagemErro('coautores'); ?>
                    <div id="trabalho-coautores-lista">
                        <?php foreach ($coautoresPreenchidos as $posicao => $coautorValores): ?>
                            <?php require __DIR__ . '/_coautor_bloco.php'; ?>
                        <?php endforeach; ?>
                    </div>
                    <p class="trabalho-coautores-limite" id="trabalho-coautores-limite" hidden>Limite de autores atingido para este evento.</p>
                    <button type="button" id="trabalho-coautor-adicionar" class="btn">Adicionar coautor</button>
                </div>
            <?php endif; ?>

            <div class="admin-card">
                <h3>Conteúdo do trabalho</h3>

                <?php if (count($metodosHabilitados) > 1): ?>
                    <label class="<?php echo $classeErro('metodo_submissao'); ?>"><?php echo $rotulo('Forma de envio', true); ?>
                        <select name="metodo_submissao" id="trabalho-metodo"<?php echo $focoErro('metodo_submissao'); ?>>
                            <?php foreach ($metodosHabilitados as $metodo): ?>
                                <option value="<?php echo $e($metodo); ?>"<?php echo $metodo === $metodoAtual ? ' selected' : ''; ?>><?php echo $e($rotulosMetodo[$metodo]); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <?php echo $mensagemErro('metodo_submissao'); ?>
                    </label>
                <?php elseif (count($metodosHabilitados) === 1): ?>
                    <input type="hidden" name="metodo_submissao" id="trabalho-metodo" value="<?php echo $e($metodoAtual); ?>">
                <?php endif; ?>

                <?php if (in_array('formulario', $metodosHabilitados, true)): ?>
                    <div data-metodo-secao="formulario">
                        <p>Não escreva seu nome nem outra forma de identificação dentro do texto: o avaliador não deve conseguir identificar o autor.</p>
                        <label class="<?php echo $classeErro('conteudo_html'); ?>"><?php echo $rotulo('Texto do trabalho', true); ?>
                            <textarea name="conteudo_html" rows="16" cols="80"<?php echo $focoErro('conteudo_html'); ?>><?php echo $v($valores, 'conteudo_html'); ?></textarea>
                            <?php echo $mensagemErro('conteudo_html'); ?>
                        </label>
                    </div>
                <?php endif; ?>

                <?php if (in_array('link_externo', $metodosHabilitados, true)): ?>
                    <div data-metodo-secao="link_externo">
                        <label class="<?php echo $classeErro('link_avaliacao'); ?>"><?php echo $rotulo('Endereço eletrônico sem identificação (o avaliador vê este)', true); ?>
                            <input type="url" name="link_avaliacao" placeholder="https://" value="<?php echo $v($valores, 'link_avaliacao'); ?>"<?php echo $focoErro('link_avaliacao'); ?>>
                            <?php echo $mensagemErro('link_avaliacao'); ?>
                        </label>
                        <?php if ($sigiloCego): ?>
                            <label class="<?php echo $classeErro('link_publicacao'); ?>"><?php echo $rotulo('Endereço eletrônico da versão completa (identificada, usada se aprovado)', true); ?>
                                <input type="url" name="link_publicacao" placeholder="https://" value="<?php echo $v($valores, 'link_publicacao'); ?>"<?php echo $focoErro('link_publicacao'); ?>>
                                <?php echo $mensagemErro('link_publicacao'); ?>
                            </label>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <?php if ($sigiloCego): ?>
                    <p>Como a avaliação deste evento é às cegas, são <strong>dois arquivos obrigatórios</strong>, com o mesmo conteúdo, diferindo só na identificação:
                    (1) uma versão <strong>sem qualquer identificação</strong> de autor, orientador ou instituição (nem no corpo do texto, nem nas propriedades do arquivo, nem em agradecimentos ou notas de rodapé), que é a única que o avaliador chega a ver;
                    (2) uma versão <strong>completa</strong>, com nome e vínculo institucional de todos os autores, usada só se o trabalho for aprovado. Sem os dois arquivos preenchidos, a submissão não é enviada.</p>
                <?php endif; ?>

                <?php if (in_array('documento_editavel', $metodosHabilitados, true)): ?>
                    <?php
                    $acceptEditavel = '';
                    foreach ($extensoesHabilitadas as $extensaoHabilitada) {
                        $acceptEditavel .= ($acceptEditavel !== '' ? ',' : '') . '.' . $extensaoHabilitada;
                    }
                    ?>
                    <div data-metodo-secao="documento_editavel">
                        <p>Extensões aceitas: <?php echo $e(implode(', ', $extensoesHabilitadas)); ?>. Tamanho máximo: <?php echo $tamanhoMaximoMb; ?>MB.</p>
                        <label class="<?php echo $classeErro('arquivo_avaliacao'); ?>"><?php echo $rotulo('Arquivo sem identificação (é este que o avaliador vê)', true); ?>
                            <input type="file" name="arquivo_avaliacao" accept="<?php echo $e($acceptEditavel); ?>" data-extensoes="<?php echo $e(implode(',', $extensoesHabilitadas)); ?>" data-tamanho-max-mb="<?php echo $tamanhoMaximoMb; ?>"<?php echo $metodoAtual === 'documento_editavel' ? $focoErro('arquivo_avaliacao') : ''; ?>>
                            <?php echo $metodoAtual === 'documento_editavel' ? $mensagemErro('arquivo_avaliacao') : ''; ?>
                        </label>
                        <?php if ($sigiloCego): ?>
                            <label class="<?php echo $classeErro('arquivo_publicacao'); ?>"><?php echo $rotulo('Arquivo da versão completa, com identificação (usado só se aprovado)', true); ?>
                                <input type="file" name="arquivo_publicacao" accept="<?php echo $e($acceptEditavel); ?>" data-extensoes="<?php echo $e(implode(',', $extensoesHabilitadas)); ?>" data-tamanho-max-mb="<?php echo $tamanhoMaximoMb; ?>"<?php echo $metodoAtual === 'documento_editavel' ? $focoErro('arquivo_publicacao') : ''; ?>>
                                <?php echo $metodoAtual === 'documento_editavel' ? $mensagemErro('arquivo_publicacao') : ''; ?>
                            </label>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <?php if (in_array('documento_nao_editavel', $metodosHabilitados, true)): ?>
                    <div data-metodo-secao="documento_nao_editavel">
                        <p>Tamanho máximo: <?php echo $tamanhoMaximoMb; ?>MB.</p>
                        <label class="<?php echo $classeErro('arquivo_avaliacao'); ?>"><?php echo $rotulo('Arquivo em PDF sem identificação (é este que o avaliador vê)', true); ?>
                            <input type="file" name="arquivo_avaliacao" accept=".pdf,application/pdf" data-extensoes="pdf" data-tamanho-max-mb="<?php echo $tamanhoMaximoMb; ?>"<?php echo $metodoAtual === 'documento_nao_editavel' ? $focoErro('arquivo_avaliacao') : ''; ?>>
                            <?php echo $metodoAtual === 'documento_nao_editavel' ? $mensagemErro('arquivo_avaliacao') : ''; ?>
                        </label>
                        <?php if ($sigiloCego): ?>
                            <label class="<?php echo $classeErro('arquivo_publicacao'); ?>"><?php echo $rotulo('Arquivo em PDF da versão completa, com identificação (usado só se aprovado)', true); ?>
                                <input type="file" name="arquivo_publicacao" accept=".pdf,application/pdf" data-extensoes="pdf" data-tamanho-max-mb="<?php echo $tamanhoMaximoMb; ?>"<?php echo $metodoAtual === 'documento_nao_editavel' ? $focoErro('arquivo_publicacao') : ''; ?>>
                                <?php echo $metodoAtual === 'documento_nao_editavel' ? $mensagemErro('arquivo_publicacao') : ''; ?>
                            </label>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>

            <?php if (!empty($termos)): ?>
                <div class="trabalho-termos <?php echo $classeErro('termos'); ?>"<?php echo $temErro('termos') ? ' tabindex="-1" data-foco-erro' : ''; ?>>
                    <h3>Declarações</h3>
                    <?php echo $mensagemErro('termos'); ?>
                    <?php foreach ($termos as $termo): ?>
                        <label class="trabalho-termo">
                            <input type="checkbox" name="termos_aceitos[]" value="<?php echo (int) $termo['id']; ?>"
                                <?php echo in_array((int) $termo['id'], $termosMarcadosInt, true) ? 'checked' : ''; ?>
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

            <?php if ($inscricaoAutomatica): ?>
                <p class="trabalho-aviso-inscricao">
                    Ao enviar o trabalho, <?php echo $maxCoautores > 0 ? 'você e os coautores indicados serão inscritos' : 'você será inscrito(a)'; ?>
                    automaticamente neste evento e <?php echo $maxCoautores > 0 ? 'receberão' : 'receberá'; ?> um e-mail com o número do protocolo.
                </p>
            <?php endif; ?>

            <button type="submit" class="btn">Enviar trabalho</button>
        </form>
    </div>
</div>

<?php if ($maxCoautores > 0): ?>
    <template id="trabalho-coautor-modelo">
        <?php
        $posicao = null;
        $coautorValores = [];
        require __DIR__ . '/_coautor_bloco.php';
        ?>
    </template>
<?php endif; ?>

<script src="<?php echo config('base_path'); ?>/assets/js/cpf-validador.js"></script>
<script src="<?php echo config('base_path'); ?>/assets/js/telefone-mascara.js"></script>
<script src="<?php echo config('base_path'); ?>/assets/js/trabalho-formulario.js"></script>
