<?php

/**
 * Fase 32: expurgo dos nomes de exibicao capturados na presenca do Meet. Uso:
 *   php database/expurgar_nomes_presenca.php            (dry-run, so' conta)
 *   php database/expurgar_nomes_presenca.php --confirmar
 *
 * O QUE FAZ: apaga (grava NULL) o nome_bruto das sessoes capturadas ha mais de
 * 30 dias. NAO apaga a linha - tipo_origem, participante_id,
 * participante_meet_ref, inicio, fim e capturado_em continuam intactos, entao
 * duracao, contagem de pessoas e deteccao de nao-identificados seguem
 * funcionando indefinidamente. So' o nome deixa de existir.
 *
 * POR QUE 30 DIAS:
 *  - Necessidade e' de janela, nao indefinida: o nome bruto so' serve enquanto
 *    alguem ainda pode revisar aquele horario (distinguir alguem de fora de uma
 *    falha de casamento por nome, investigar um caso concreto). Passada a
 *    janela, o que resta util - quantos entraram, por quanto tempo, se bateu ou
 *    nao com um convidado - nao depende de saber o nome.
 *  - Simetria com o Google: a origem ja e' tratada pelo fornecedor como
 *    transitoria e apagada em 30 dias. Reter a copia local por prazo indefinido
 *    faria a replica sobreviver a decisao de retencao do dado original sem que
 *    isso fosse escolha consciente.
 *  - LGPD arts. 6, 15 e 16 (finalidade, necessidade, retencao limitada) e art.
 *    23 (orgao do Judiciario - finalidade explicita e especifica).
 *  - Quem entrou sem casar com nenhum convidado e' o caso de maior exposicao:
 *    diferente de um integrante cadastrado, essa pessoa nunca teve vinculo,
 *    consentimento nem ciencia do registro.
 *
 * Seguro rodar quantas vezes quiser - so' toca em linhas que ainda tem nome e
 * ja passaram do prazo.
 */

if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    exit('Acesso negado: este script so pode ser executado via linha de comando.');
}

define('SI_BOOT', true);

require __DIR__ . '/../vendor/autoload.php';

use App\Repositories\GooglePresencaRepository;

const DIAS_RETENCAO_NOME = 30;

$confirmar = in_array('--confirmar', $argv, true);
$presenca = new GooglePresencaRepository();

$pendentes = $presenca->contarNomesExpiraveis(DIAS_RETENCAO_NOME);

echo 'Sessoes com nome capturado ha mais de ' . DIAS_RETENCAO_NOME . " dias: {$pendentes}\n";

if ($pendentes === 0) {
    echo "Nada a expurgar.\n";
    exit(0);
}

if (!$confirmar) {
    echo "\nDry-run: nada foi alterado. Rode de novo com --confirmar para aplicar.\n";
    echo "Lembre: a linha NAO e' apagada - some so' o nome. Duracao, contagem e vinculo\n";
    echo "        com participante cadastrado continuam disponiveis depois do expurgo.\n";
    exit(0);
}

$afetadas = $presenca->expurgarNomes(DIAS_RETENCAO_NOME);

echo "Concluido: {$afetadas} nome(s) removido(s).\n";
