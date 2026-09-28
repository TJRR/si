<?php

/**
 * Fase 54: gera o volume dos Anais pedido na tela "Montagem dos Anais". Uso:
 *   php database/gerar_anais.php
 *
 * Chamado a cada minuto pelo agendador do servidor, como o usuario do
 * servidor web (os arquivos gerados precisam ser lidos e apagados pelo
 * sistema depois). Juntar os PDFs leva minutos e centenas de megabytes, por
 * isso roda aqui, fora do servidor web.
 *
 * Processa no maximo UM pedido por execucao; sem pedido pendente, termina
 * em silencio. A trava por flock impede duas execucoes ao mesmo tempo
 * neste servidor (nao cobriria dois servidores com o mesmo agendamento).
 * Pedido que ficou "em geracao" de uma execucao que morreu no meio e'
 * marcado como falhou no inicio da execucao seguinte.
 */

if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    exit('Acesso negado: este script so pode ser executado via linha de comando.');
}

define('SI_BOOT', true);

require __DIR__ . '/../vendor/autoload.php';

use App\Repositories\EventoAnaisGeracaoRepository;
use App\Services\EventoAnaisGeradorService;

date_default_timezone_set(config('timezone'));

function registrar($mensagem)
{
    return '[' . date('Y-m-d H:i:s') . '] ' . $mensagem . "\n";
}

$arquivoTrava = sys_get_temp_dir() . '/si_gerar_anais.lock';
$trava = fopen($arquivoTrava, 'c');

if ($trava === false) {
    fwrite(STDERR, registrar('Nao foi possivel abrir o arquivo de trava: ' . $arquivoTrava));
    exit(1);
}

if (!flock($trava, LOCK_EX | LOCK_NB)) {
    fwrite(STDOUT, registrar('Geracao anterior ainda em andamento - abortando esta.'));
    exit(0);
}

$geracoes = new EventoAnaisGeracaoRepository();
$gerador = new EventoAnaisGeradorService();

try {
    $interrompidos = $geracoes->marcarInterrompidos(
        'A geração foi interrompida antes de terminar (falta de memória, tempo esgotado ou reinício do servidor). Peça de novo; se repetir, avise o suporte técnico.'
    );

    if ($interrompidos > 0) {
        fwrite(STDOUT, registrar($interrompidos . ' pedido(s) interrompido(s) marcado(s) como falha.'));
    }

    $gerador->limparSobrasTemporarias();
    $pedido = $geracoes->reservarProximo();
} catch (\Throwable $e) {
    fwrite(STDERR, registrar('ERRO ao consultar a fila de geracao: ' . $e->getMessage()));
    flock($trava, LOCK_UN);
    fclose($trava);
    exit(1);
}

if ($pedido === null) {
    flock($trava, LOCK_UN);
    fclose($trava);
    exit(0);
}

ini_set('memory_limit', '512M');
set_time_limit(0);

fwrite(STDOUT, registrar('Inicio - pedido #' . (int) $pedido['id'] . ' do evento #' . (int) $pedido['evento_id'] . '.'));

$codigoSaida = 0;

try {
    $versaoId = $gerador->gerar($pedido);
    $paginas = $gerador->totalDePaginas();

    $geracoes->concluir(
        $pedido['id'],
        $versaoId,
        'Volume gerado com ' . ($paginas === 1 ? '1 página' : $paginas . ' páginas') . '.'
    );

    fwrite(STDOUT, registrar('Fim - pedido #' . (int) $pedido['id'] . ' concluido, versao #' . (int) $versaoId . ', ' . $paginas . ' pagina(s).'));
} catch (\Throwable $e) {
    // Regra de negocio (RuntimeException) vai para a tela como esta; falha
    // tecnica fica so' no registro, com o texto generico na tela.
    $paraTela = ($e instanceof \RuntimeException && !($e instanceof \PDOException))
        ? $e->getMessage()
        : 'Falha inesperada ao gerar o volume. Avise o suporte técnico.';

    fwrite(STDERR, registrar('Pedido #' . (int) $pedido['id'] . ' FALHOU: ' . get_class($e) . ': ' . $e->getMessage()));

    try {
        $geracoes->falhar($pedido['id'], $paraTela);
    } catch (\Throwable $erroAoMarcar) {
        fwrite(STDERR, registrar('ERRO ao marcar o pedido #' . (int) $pedido['id'] . ' como falho: ' . $erroAoMarcar->getMessage()));
    }

    $codigoSaida = 1;
}

flock($trava, LOCK_UN);
fclose($trava);
exit($codigoSaida);
