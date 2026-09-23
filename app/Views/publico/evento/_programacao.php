<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<?php
// Fase 51: programacao com uma aba por dia. No modo vinculado, os itens sao
// Atividades do evento e o agrupamento (dia e turno) sai da data de inicio
// de cada uma, sem o Admin precisar repetir nada; no modo digitado, sai do
// que foi cadastrado na propria secao.
$vinculado = $dadosSecao['fonte'] === 'atividades';
$porDia = [];

foreach ($itensSecao as $item) {
    if ($vinculado) {
        $dia = substr($item['data_inicio'], 0, 10);
        $hora = (int) substr($item['data_inicio'], 11, 2);
        $turno = $hora < 12 ? 'manha' : ($hora < 18 ? 'tarde' : 'noite');
        $linha = [
            'horario' => date('H\hi', strtotime($item['data_inicio'])),
            'tipo' => (string) $item['tipo_nome'],
            'cor_tipo' => $item['tipo_cor'],
            'titulo' => $item['nome'],
            'local' => (string) $item['local'],
            'descricao' => '',
        ];
    } else {
        $dia = $item['dia'] !== null ? $item['dia'] : '';
        $turno = $item['turno'];
        $linha = [
            'horario' => (string) $item['horario_texto'],
            'tipo' => (string) $item['tipo_texto'],
            'cor_tipo' => null,
            'titulo' => (string) $item['titulo'],
            'local' => (string) $item['local'],
            'descricao' => (string) $item['descricao'],
        ];
    }

    $porDia[$dia]['manha'] = isset($porDia[$dia]['manha']) ? $porDia[$dia]['manha'] : [];
    $porDia[$dia]['tarde'] = isset($porDia[$dia]['tarde']) ? $porDia[$dia]['tarde'] : [];
    $porDia[$dia]['noite'] = isset($porDia[$dia]['noite']) ? $porDia[$dia]['noite'] : [];
    $porDia[$dia][$turno][] = $linha;
}

ksort($porDia);
$rotulosTurno = ['manha' => 'Manhã', 'tarde' => 'Tarde', 'noite' => 'Noite'];
?>
<?php include __DIR__ . '/_secao_abre.php'; ?>
        <?php if (!empty($porDia)): ?>
            <div class="evento-programacao" data-programacao>
                <div class="evento-programacao-abas" role="tablist">
                    <?php $numeroDia = 0; ?>
                    <?php foreach ($porDia as $dia => $turnos): ?>
                        <?php $numeroDia++; ?>
                        <button type="button" class="evento-programacao-aba" role="tab" aria-selected="false" data-programacao-aba="<?php echo htmlspecialchars($dia, ENT_QUOTES, 'UTF-8'); ?>">
                            Dia <?php echo $numeroDia; ?><?php echo $dia !== '' ? ' · ' . htmlspecialchars(formatarData($dia), ENT_QUOTES, 'UTF-8') : ''; ?>
                        </button>
                    <?php endforeach; ?>
                </div>

                <?php foreach ($porDia as $dia => $turnos): ?>
                    <div class="evento-programacao-painel" role="tabpanel" data-programacao-painel="<?php echo htmlspecialchars($dia, ENT_QUOTES, 'UTF-8'); ?>" hidden>
                        <?php foreach ($rotulosTurno as $chaveTurno => $rotuloTurno): ?>
                            <?php if (empty($turnos[$chaveTurno])): ?>
                                <?php continue; ?>
                            <?php endif; ?>
                            <h3 class="evento-programacao-turno"><?php echo $rotuloTurno; ?></h3>
                            <ul class="evento-programacao-lista">
                                <?php foreach ($turnos[$chaveTurno] as $linha): ?>
                                    <li>
                                        <?php if ($linha['tipo'] !== ''): ?>
                                            <span class="evento-etiqueta-tipo" style="<?php echo $linha['cor_tipo'] !== null ? 'background:' . htmlspecialchars($linha['cor_tipo'], ENT_QUOTES, 'UTF-8') . ';' : ''; ?>"><?php echo htmlspecialchars($linha['tipo'], ENT_QUOTES, 'UTF-8'); ?></span>
                                        <?php endif; ?>
                                        <?php if ($linha['horario'] !== ''): ?>
                                            <span class="evento-programacao-hora"><?php echo htmlspecialchars($linha['horario'], ENT_QUOTES, 'UTF-8'); ?></span>
                                        <?php endif; ?>
                                        <strong><?php echo htmlspecialchars($linha['titulo'], ENT_QUOTES, 'UTF-8'); ?></strong>
                                        <?php if ((int) $dadosSecao['mostrar_local'] === 1 && $linha['local'] !== ''): ?>
                                            <span class="evento-programacao-local"><?php echo htmlspecialchars($linha['local'], ENT_QUOTES, 'UTF-8'); ?></span>
                                        <?php endif; ?>
                                        <?php if ($linha['descricao'] !== ''): ?>
                                            <span class="evento-programacao-descricao"><?php echo htmlspecialchars($linha['descricao'], ENT_QUOTES, 'UTF-8'); ?></span>
                                        <?php endif; ?>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endforeach; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
<?php include __DIR__ . '/_secao_fecha.php'; ?>
