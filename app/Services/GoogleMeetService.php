<?php

namespace App\Services;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use DateTime;
use DateTimeZone;

/**
 * Fase 32: chamadas REST cruas a Google Meet API v2, sempre com um access
 * token ja obtido (App\Core\GoogleServiceAccountAuth) impersonando o
 * organizador do horario. Mesmo estilo curl cru de GoogleCalendarService -
 * fail-soft (qualquer falha devolve null, nunca lanca excecao), porque quem
 * chama e' um cron rodando sem supervisao humana.
 *
 * IMPORTANTE - a Meet API NAO devolve e-mail de quem entrou na sala. Cada
 * participante vem como signedinUser (id opaco do Google + nome de exibicao),
 * anonymousUser (so' nome digitado) ou phoneUser (telefone parcial). Por isso
 * o casamento com participantes cadastrados e' feito por NOME normalizado la'
 * no script de captura, nunca por e-mail.
 *
 * Caminho de resolucao da sala (2 etapas, robusto a formato): o
 * conferenceData.conferenceId que a Calendar API devolve pode ser tanto o
 * space id quanto o meeting code - spaces.get aceita os dois como path param,
 * entao pegamos o `name` canonico dele e so' depois filtramos os
 * conferenceRecords por space.name. Assim nao dependemos de adivinhar o
 * formato.
 *
 * Retencao: o Google apaga conferenceRecord (e participants/sessions junto)
 * 30 dias depois do fim da conferencia. Depois disso nao ha o que buscar.
 */
class GoogleMeetService
{
    const URL_BASE = 'https://meet.googleapis.com/v2';
    const ESCOPO_LEITURA = 'https://www.googleapis.com/auth/meetings.space.readonly';
    const TIMEOUT_SEGUNDOS = 8;

    /**
     * Resolve o `name` canonico do space (spaces/{id}) a partir do
     * conferenceId guardado no horario. Aceita os dois formatos possiveis
     * sem precisar saber de antemao qual e'.
     */
    public static function buscarSpace($accessToken, $conferenceId)
    {
        $resposta = self::requisitar(
            self::URL_BASE . '/spaces/' . rawurlencode($conferenceId),
            $accessToken
        );

        return isset($resposta['name']) ? $resposta['name'] : null;
    }

    /**
     * Ultimo conferenceRecord da sala. Devolve ['name', 'fim'] - 'fim' e' o
     * endTime do proprio registro: quando vem null a conferencia AINDA ESTA
     * EM ANDAMENTO, e quem chama nao pode tratar os dados como definitivos
     * (ver a guarda contra captura prematura em capturar_presenca_google_meet).
     * Devolve null se nao houver registro nenhum (reuniao nunca aconteceu, ou
     * o Google ainda nao processou).
     */
    public static function buscarConferenceRecord($accessToken, $spaceName)
    {
        $filtro = 'space.name="' . $spaceName . '"';
        $url = self::URL_BASE . '/conferenceRecords?filter=' . rawurlencode($filtro);

        $resposta = self::requisitar($url, $accessToken);

        if ($resposta === null || empty($resposta['conferenceRecords'])) {
            return null;
        }

        // A API devolve do mais recente pro mais antigo; uma sala reutilizada
        // pode ter varios registros, e o que interessa e' o ultimo encontro.
        $registro = $resposta['conferenceRecords'][0];

        if (!isset($registro['name'])) {
            return null;
        }

        return [
            'name' => $registro['name'],
            'fim' => isset($registro['endTime']) ? self::paraHorarioLocal($registro['endTime']) : null,
        ];
    }

    /**
     * Participantes de um conferenceRecord, paginado. Cada item traz o `name`
     * (referencia opaca do participante, usada como chave estavel de
     * agrupamento mesmo depois do expurgo do nome), o nome de exibicao cru e
     * de qual dos tres tipos de entrada ele veio.
     */
    public static function listarParticipantes($accessToken, $conferenceRecordName)
    {
        $itens = self::listarPaginado(
            $accessToken,
            self::URL_BASE . '/' . $conferenceRecordName . '/participants',
            'participants'
        );

        if ($itens === null) {
            return null;
        }

        $participantes = [];

        foreach ($itens as $item) {
            if (!isset($item['name'])) {
                continue;
            }

            $identificacao = self::identificarParticipante($item);

            $participantes[] = [
                'meet_ref' => $item['name'],
                'nome_bruto' => $identificacao['nome_bruto'],
                'tipo_origem' => $identificacao['tipo_origem'],
            ];
        }

        return $participantes;
    }

    /**
     * Sessoes (trechos de entrada/saida) de um participante. Uma pessoa que
     * cai da chamada e volta aparece aqui com varias sessoes - a duracao final
     * dela e' a SOMA de todas, nunca so' a ultima.
     */
    public static function listarSessoes($accessToken, $participantName)
    {
        $itens = self::listarPaginado(
            $accessToken,
            self::URL_BASE . '/' . $participantName . '/participantSessions',
            'participantSessions'
        );

        if ($itens === null) {
            return null;
        }

        $sessoes = [];

        foreach ($itens as $item) {
            if (!isset($item['startTime'])) {
                continue;
            }

            $sessoes[] = [
                'inicio' => self::paraHorarioLocal($item['startTime']),
                'fim' => isset($item['endTime']) ? self::paraHorarioLocal($item['endTime']) : null,
            ];
        }

        return $sessoes;
    }

    /**
     * Extrai nome de exibicao e origem do campo union do participante -
     * exatamente um dos tres esta presente em cada resposta.
     */
    private static function identificarParticipante(array $item)
    {
        if (isset($item['signedinUser'])) {
            return [
                'tipo_origem' => 'signedinUser',
                'nome_bruto' => isset($item['signedinUser']['displayName']) ? $item['signedinUser']['displayName'] : null,
            ];
        }

        if (isset($item['phoneUser'])) {
            return [
                'tipo_origem' => 'phoneUser',
                'nome_bruto' => isset($item['phoneUser']['displayName']) ? $item['phoneUser']['displayName'] : null,
            ];
        }

        return [
            'tipo_origem' => 'anonymousUser',
            'nome_bruto' => isset($item['anonymousUser']['displayName']) ? $item['anonymousUser']['displayName'] : null,
        ];
    }

    /**
     * Percorre todas as paginas de uma listagem. Uma pagina que falha aborta
     * tudo (devolve null) em vez de entregar resultado parcial - presenca
     * incompleta seria pior que nenhuma, porque seria gravada como definitiva.
     */
    private static function listarPaginado($accessToken, $url, $chaveItens)
    {
        $itens = [];
        $pagina = null;

        do {
            $urlPagina = $url . ($pagina !== null
                ? (strpos($url, '?') !== false ? '&' : '?') . 'pageToken=' . urlencode($pagina)
                : '');

            $resposta = self::requisitar($urlPagina, $accessToken);

            if ($resposta === null) {
                return null;
            }

            if (!empty($resposta[$chaveItens]) && is_array($resposta[$chaveItens])) {
                $itens = array_merge($itens, $resposta[$chaveItens]);
            }

            $pagina = isset($resposta['nextPageToken']) ? $resposta['nextPageToken'] : null;
        } while ($pagina !== null);

        return $itens;
    }

    /**
     * A Meet API devolve RFC3339 em UTC; o banco guarda DATETIME no fuso do
     * sistema, igual a todo o resto do projeto. Isso nao muda a DURACAO (as
     * duas pontas mudam junto), mas sem converter o horario exibido na tela
     * sairia 4h errado e qualquer comparacao futura contra data_inicio/
     * data_fim do horario erraria em silencio.
     */
    private static function paraHorarioLocal($iso)
    {
        try {
            $data = new DateTime($iso, new DateTimeZone('UTC'));
            $data->setTimezone(new DateTimeZone(GoogleCalendarService::TIMEZONE));

            return $data->format('Y-m-d H:i:s');
        } catch (\Exception $e) {
            return null;
        }
    }

    private static function requisitar($url, $accessToken)
    {
        $ch = curl_init($url);

        curl_setopt_array($ch, [
            CURLOPT_HTTPGET => true,
            CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $accessToken],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => self::TIMEOUT_SEGUNDOS,
            CURLOPT_CONNECTTIMEOUT => 5,
        ]);

        $corpo = curl_exec($ch);
        $erroCurl = curl_error($ch);
        $codigoHttp = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($corpo === false || $erroCurl !== '') {
            return null;
        }

        if ($codigoHttp < 200 || $codigoHttp >= 300) {
            return null;
        }

        if ($corpo === '') {
            return [];
        }

        $dados = json_decode($corpo, true);

        return is_array($dados) ? $dados : null;
    }
}
