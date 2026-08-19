<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<?php
/**
 * Fase 32: corpo do relatorio de presenca, compartilhado por
 * admin/mentorias/presenca.php e admin/oficinas/presenca.php - o cruzamento
 * convidado -> RSVP -> presenca e' identico nos dois; so' o cabecalho do
 * horario muda (mentoria tem equipe/mentor, oficina tem tema).
 *
 * Espera: $horario, $tipo, $rotaModulo, $convidados, $naoIdentificados,
 * $maxTentativas.
 */
$mapaRsvp = [
    'accepted' => ['Confirmou', 'verde'],
    'declined' => ['Recusou', 'vermelho'],
    'tentative' => ['Talvez', 'laranja'],
    'needsAction' => ['Sem resposta', 'laranja'],
];

/** Segundos -> "1h 05min" / "12min" / "—" quando nao ha o que apurar. */
$formatarDuracao = function ($segundos) {
    if ($segundos === null) {
        return '—';
    }

    $minutos = (int) round($segundos / 60);

    if ($minutos < 60) {
        return $minutos . 'min';
    }

    return intdiv($minutos, 60) . 'h ' . str_pad($minutos % 60, 2, '0', STR_PAD_LEFT) . 'min';
};
?>

<?php if ($horario['presenca_status'] === 'pendente'): ?>
    <p class="status-pill laranja">Presença ainda não capturada</p>
    <p>
        O relatório é gerado automaticamente algumas horas depois do fim do horário — o Google
        leva um tempo para fechar o registro da chamada.
        <?php if ((int) $horario['presenca_tentativas'] > 0): ?>
            Já foram feitas <strong><?php echo (int) $horario['presenca_tentativas']; ?> de <?php echo (int) $maxTentativas; ?></strong> tentativas
            (última em <?php echo htmlspecialchars(formatarDataHora($horario['presenca_ultima_tentativa_em']), ENT_QUOTES, 'UTF-8'); ?> <?php echo sufixoFusoHorario(); ?>).
        <?php else: ?>
            Nenhuma tentativa foi feita ainda.
        <?php endif; ?>
    </p>
<?php elseif ($horario['presenca_status'] === 'indisponivel'): ?>
    <p class="status-pill vermelho">Presença indisponível</p>
    <p>
        As tentativas de obter os dados desta sala se esgotaram. Se vários horários mostrarem
        este mesmo aviso, a causa provavelmente é única (autorização do escopo do Google Meet
        ou edição do Workspace sem rastreamento de presença) — verifique isso antes de tratar
        caso a caso. Depois de corrigida a causa, use o botão abaixo para tentar de novo.
    </p>
    <form method="post" action="<?php echo url($rotaModulo . '/reprocessarPresenca'); ?>"><?= campoCsrf() ?>
        <input type="hidden" name="id" value="<?php echo (int) $horario['id']; ?>">
        <button type="submit">Reprocessar presença</button>
    </form>
<?php else: ?>
    <p class="status-pill verde">Presença capturada em <?php echo htmlspecialchars(formatarDataHora($horario['presenca_capturada_em']), ENT_QUOTES, 'UTF-8'); ?> <?php echo sufixoFusoHorario(); ?></p>
<?php endif; ?>

<h3>Convidados</h3>

<?php if (empty($convidados)): ?>
    <p>Nenhum integrante convidado para este horário.</p>
<?php else: ?>
    <div class="tabela-scroll">
        <table>
            <tr>
                <th>Participante</th>
                <th>Equipe</th>
                <th>Convite</th>
                <th>Entrou</th>
                <th>Permanência</th>
                <th>Entrada</th>
            </tr>
            <?php foreach ($convidados as $convidado): ?>
                <?php
                $rotuloRsvp = isset($mapaRsvp[$convidado['rsvp']]) ? $mapaRsvp[$convidado['rsvp']] : null;
                $presenca = $convidado['presenca'];
                ?>
                <tr>
                    <td><?php echo htmlspecialchars($convidado['nome'], ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><?php echo htmlspecialchars($convidado['nome_equipe'], ENT_QUOTES, 'UTF-8'); ?></td>
                    <td>
                        <?php if ($rotuloRsvp !== null): ?>
                            <span class="status-pill <?php echo $rotuloRsvp[1]; ?>"><?php echo $rotuloRsvp[0]; ?></span>
                        <?php else: ?>
                            <span class="status-pill">Não convidado</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($presenca !== null): ?>
                            <span class="status-pill verde">Sim</span>
                        <?php elseif ($horario['presenca_status'] === 'capturada'): ?>
                            <span class="status-pill vermelho">Não</span>
                        <?php else: ?>
                            <span class="status-pill">—</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($presenca !== null): ?>
                            <?php echo htmlspecialchars($formatarDuracao($presenca['duracao_segundos']), ENT_QUOTES, 'UTF-8'); ?>
                            <?php if ((int) $presenca['total_sessoes'] > 1): ?>
                                <small>(<?php echo (int) $presenca['total_sessoes']; ?> entradas)</small>
                            <?php endif; ?>
                        <?php else: ?>
                            —
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php echo $presenca !== null
                            ? htmlspecialchars(formatarDataHora($presenca['primeira_entrada']), ENT_QUOTES, 'UTF-8')
                            : '—'; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>
    </div>
<?php endif; ?>

<?php if (!empty($naoIdentificados)): ?>
    <h3>Entraram sem identificação</h3>
    <p>
        Estas pessoas entraram na sala mas <strong>não foram reconhecidas como convidados deste horário</strong>.
        A identificação é feita pelo <strong>nome da conta Google usada para entrar</strong> — a API do Meet não
        informa o e-mail de quem participou. Por isso, esta lista pode conter tanto alguém realmente
        de fora quanto um integrante legítimo cujo nome na conta Google difere do nome cadastrado
        (apelido, nome social, conta pessoal). Confira antes de tratar como intruso.
    </p>
    <div class="tabela-scroll">
        <table>
            <tr>
                <th>Nome informado ao Google</th>
                <th>Forma de entrada</th>
                <th>Permanência</th>
                <th>Entrada</th>
            </tr>
            <?php foreach ($naoIdentificados as $pessoa): ?>
                <?php
                $mapaOrigem = [
                    'signedinUser' => 'Conta Google',
                    'anonymousUser' => 'Convidado (sem login)',
                    'phoneUser' => 'Telefone',
                ];
                ?>
                <tr>
                    <td>
                        <?php if ($pessoa['nome_bruto'] !== null): ?>
                            <?php echo htmlspecialchars($pessoa['nome_bruto'], ENT_QUOTES, 'UTF-8'); ?>
                        <?php else: ?>
                            <em>identificação expurgada (30 dias)</em>
                        <?php endif; ?>
                    </td>
                    <td><?php echo htmlspecialchars(isset($mapaOrigem[$pessoa['tipo_origem']]) ? $mapaOrigem[$pessoa['tipo_origem']] : $pessoa['tipo_origem'], ENT_QUOTES, 'UTF-8'); ?></td>
                    <td>
                        <?php echo htmlspecialchars($formatarDuracao($pessoa['duracao_segundos']), ENT_QUOTES, 'UTF-8'); ?>
                        <?php if ((int) $pessoa['total_sessoes'] > 1): ?>
                            <small>(<?php echo (int) $pessoa['total_sessoes']; ?> entradas)</small>
                        <?php endif; ?>
                    </td>
                    <td><?php echo htmlspecialchars(formatarDataHora($pessoa['primeira_entrada']), ENT_QUOTES, 'UTF-8'); ?></td>
                </tr>
            <?php endforeach; ?>
        </table>
    </div>
<?php endif; ?>

<?php if ($horario['presenca_status'] === 'capturada'): ?>
    <p>
        <small>
            Os nomes de quem entrou sem identificação são apagados 30 dias após a captura; a
            contagem e a permanência continuam disponíveis depois disso.
        </small>
    </p>
<?php endif; ?>
