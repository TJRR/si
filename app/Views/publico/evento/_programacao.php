<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<?php
// Fase 51, refeita na reabertura: programacao com uma aba por dia e, dentro
// de cada dia, uma coluna por turno (Manha, Tarde e, se houver, Noite). No
// modo vinculado, os itens sao Atividades do evento e o dia e o turno saem
// da data de inicio de cada uma; no modo digitado, saem do que foi
// cadastrado na secao.
//
// A faixa de horario do cabecalho de cada turno ("MANHA · 08h30 as 12h30")
// sai sozinha dos horarios dos itens daquele turno naquele dia: do mais cedo
// ao mais tarde. Quando todos os itens do turno tem o mesmo horario, ele vai
// so' para o cabecalho e nao se repete em cada cartao.
$vinculado = $dadosSecao['fonte'] === 'atividades';
$porDia = [];

$paraMinutos = function ($texto) {
    preg_match_all('/(\d{1,2})h(\d{2})?/', (string) $texto, $achados, PREG_SET_ORDER);

    return array_map(function ($achado) {
        return ((int) $achado[1]) * 60 + (isset($achado[2]) && $achado[2] !== '' ? (int) $achado[2] : 0);
    }, $achados);
};

$paraTexto = function ($minutos) {
    $horas = intdiv($minutos, 60);
    $resto = $minutos % 60;

    return str_pad((string) $horas, 2, '0', STR_PAD_LEFT) . 'h' . ($resto > 0 ? str_pad((string) $resto, 2, '0', STR_PAD_LEFT) : '');
};

foreach ($itensSecao as $item) {
    if ($vinculado) {
        $dia = substr($item['data_inicio'], 0, 10);
        $hora = (int) substr($item['data_inicio'], 11, 2);
        $turno = $hora < 12 ? 'manha' : ($hora < 18 ? 'tarde' : 'noite');
        $inicio = $paraTexto((int) substr($item['data_inicio'], 11, 2) * 60 + (int) substr($item['data_inicio'], 14, 2));
        $horario = $inicio;

        if (!empty($item['data_fim']) && substr($item['data_fim'], 0, 10) === $dia) {
            $horario .= ' às ' . $paraTexto((int) substr($item['data_fim'], 11, 2) * 60 + (int) substr($item['data_fim'], 14, 2));
        }

        $linha = [
            'horario' => $horario,
            'tipo' => (string) $item['tipo_nome'],
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
            'titulo' => (string) $item['titulo'],
            'local' => (string) $item['local'],
            'descricao' => (string) $item['descricao'],
        ];
    }

    if (!isset($porDia[$dia])) {
        $porDia[$dia] = ['manha' => [], 'tarde' => [], 'noite' => []];
    }

    $porDia[$dia][$turno][] = $linha;
}

ksort($porDia);
$rotulosTurno = ['manha' => 'Manhã', 'tarde' => 'Tarde', 'noite' => 'Noite'];

$faixaDoTurno = function (array $linhas) use ($paraMinutos, $paraTexto) {
    $horarios = array_values(array_unique(array_map(function ($linha) {
        return trim($linha['horario']);
    }, $linhas)));

    if (count($horarios) === 1) {
        return ['faixa' => $horarios[0], 'repetir' => false];
    }

    $todos = [];

    foreach ($horarios as $horario) {
        $todos = array_merge($todos, $paraMinutos($horario));
    }

    if (count($todos) < 2) {
        return ['faixa' => '', 'repetir' => true];
    }

    return ['faixa' => $paraTexto(min($todos)) . ' às ' . $paraTexto(max($todos)), 'repetir' => true];
};
?>
<?php include __DIR__ . '/_secao_abre.php'; ?>
        <?php if (!empty($porDia)): ?>
            <div class="evento-programacao" data-programacao>
                <div class="evento-programacao-abas" role="tablist" aria-label="Dias do evento">
                    <?php $numeroDia = 0; ?>
                    <?php foreach ($porDia as $dia => $turnos): ?>
                        <?php $numeroDia++; ?>
                        <button type="button" class="evento-programacao-aba" role="tab" aria-selected="false" data-programacao-aba="<?php echo htmlspecialchars($dia, ENT_QUOTES, 'UTF-8'); ?>">
                            Dia <?php echo $numeroDia; ?><?php echo $dia !== '' ? ' · ' . htmlspecialchars(date('d/m', strtotime($dia)), ENT_QUOTES, 'UTF-8') : ''; ?>
                        </button>
                    <?php endforeach; ?>
                </div>

                <?php foreach ($porDia as $dia => $turnos): ?>
                    <?php $turnosComItens = array_filter($turnos); ?>
                    <div class="evento-programacao-painel evento-programacao-colunas-<?php echo count($turnosComItens); ?>" role="tabpanel" data-programacao-painel="<?php echo htmlspecialchars($dia, ENT_QUOTES, 'UTF-8'); ?>" hidden>
                        <?php foreach ($turnosComItens as $chaveTurno => $linhas): ?>
                            <?php $faixa = $faixaDoTurno($linhas); ?>
                            <div class="evento-programacao-turno evento-turno-<?php echo $chaveTurno; ?>">
                                <h3 class="evento-programacao-turno-titulo">
                                    <?php echo $rotulosTurno[$chaveTurno]; ?><?php echo $faixa['faixa'] !== '' ? ' · ' . htmlspecialchars($faixa['faixa'], ENT_QUOTES, 'UTF-8') : ''; ?>
                                </h3>
                                <?php foreach ($linhas as $linha): ?>
                                    <?php
                                    $rotuloCartao = implode(' · ', array_filter([
                                        $linha['tipo'],
                                        $faixa['repetir'] ? $linha['horario'] : '',
                                    ], function ($parte) {
                                        return trim((string) $parte) !== '';
                                    }));
                                    ?>
                                    <article class="evento-programacao-cartao">
                                        <?php if ($rotuloCartao !== ''): ?>
                                            <p class="evento-programacao-rotulo"><?php echo htmlspecialchars($rotuloCartao, ENT_QUOTES, 'UTF-8'); ?></p>
                                        <?php endif; ?>
                                        <h4><?php echo htmlspecialchars($linha['titulo'], ENT_QUOTES, 'UTF-8'); ?></h4>
                                        <?php if ((int) $dadosSecao['mostrar_local'] === 1 && $linha['local'] !== ''): ?>
                                            <p class="evento-programacao-local"><?php echo htmlspecialchars($linha['local'], ENT_QUOTES, 'UTF-8'); ?></p>
                                        <?php endif; ?>
                                        <?php if ($linha['descricao'] !== ''): ?>
                                            <p class="evento-programacao-descricao"><?php echo htmlspecialchars($linha['descricao'], ENT_QUOTES, 'UTF-8'); ?></p>
                                        <?php endif; ?>
                                    </article>
                                <?php endforeach; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
<?php include __DIR__ . '/_secao_fecha.php'; ?>
