<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<?php
/**
 * Fase 34: campo de vínculo do compromisso (Mentoria/Oficina) com uma etapa.
 * Compartilhado pelos dois formulários — a regra é a mesma nos dois.
 *
 * Um único select com <optgroup> por trilha, em vez de cascata Trilha →
 * Etapa: a cascata exigiria recarregar o formulário a cada troca (perdendo
 * o que já foi digitado) ou um <script>, que não roda sob a navegação em
 * árvore do painel (Fase 16). O aviso usa atributo onchange inline, que
 * funciona nesse cenário.
 *
 * Todas as etapas são listadas, inclusive as que não restringem ninguém —
 * escondê-las faria o admin achar que a etapa sumiu do sistema. As que não
 * restringem vêm marcadas, e escolher uma delas dispara aviso na tela e
 * também no salvamento.
 *
 * Espera em escopo: $entrada, $trilhas, $etapasPorTrilha.
 */
$etapaSelecionada = $entrada['etapa_id'];
$selecionadaRestringe = true;
foreach ($etapasPorTrilha as $etapasDaTrilha) {
    foreach ($etapasDaTrilha as $etapaItem) {
        if ($etapaSelecionada !== null && (int) $etapaItem['id'] === (int) $etapaSelecionada) {
            $selecionadaRestringe = !empty($etapaItem['restringe']);
        }
    }
}
?>
<label>Restringir a quem está habilitado à etapa:
    <select name="etapa_id" onchange="var op=this.options[this.selectedIndex]; document.getElementById('aviso-etapa-sem-restricao').style.display = op.getAttribute('data-restringe')==='0' ? 'block' : 'none';">
        <option value="" data-restringe="1" <?php echo $etapaSelecionada === null ? 'selected' : ''; ?>>Aberto a todos</option>
        <?php foreach ($trilhas as $trilha): ?>
            <?php $etapasDaTrilha = isset($etapasPorTrilha[(int) $trilha['id']]) ? $etapasPorTrilha[(int) $trilha['id']] : []; ?>
            <?php if (empty($etapasDaTrilha)) { continue; } ?>
            <optgroup label="<?php echo htmlspecialchars($trilha['nome'], ENT_QUOTES, 'UTF-8'); ?>">
                <?php foreach ($etapasDaTrilha as $etapaItem): ?>
                    <option value="<?php echo (int) $etapaItem['id']; ?>"
                            data-restringe="<?php echo !empty($etapaItem['restringe']) ? '1' : '0'; ?>"
                            <?php echo $etapaSelecionada !== null && (int) $etapaItem['id'] === (int) $etapaSelecionada ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($etapaItem['nome'], ENT_QUOTES, 'UTF-8'); ?><?php echo empty($etapaItem['restringe']) ? ' (não restringe)' : ''; ?>
                    </option>
                <?php endforeach; ?>
            </optgroup>
        <?php endforeach; ?>
    </select>
</label>
<p style="color:#666;">
    Com uma etapa escolhida, só enxerga e se inscreve neste compromisso a equipe habilitada àquela etapa —
    o mesmo critério que libera a submissão: estar classificada na etapa anterior. Como etapa pertence a uma
    trilha, escolher uma etapa restringe o compromisso àquela trilha.
    <strong>Enquanto o resultado da etapa anterior não for publicado, ninguém verá este compromisso.</strong>
</p>
<p id="aviso-etapa-sem-restricao" class="flash-mensagem laranja" style="<?php echo $selecionadaRestringe ? 'display:none;' : ''; ?>">
    Esta etapa <strong>não restringe ninguém</strong>: ou é a primeira da trilha, ou a etapa anterior não é
    avaliada por avaliadores. Na prática, o compromisso ficará aberto a todos.
</p>
<br>
