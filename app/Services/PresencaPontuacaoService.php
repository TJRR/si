<?php

namespace App\Services;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Repositories\EventoAtividadeFacilitadorRepository;
use App\Repositories\EventoAtividadeTipoRepository;
use App\Repositories\GamificacaoConfigRepository;
use App\Repositories\PresencaCreditoRepository;

/**
 * Fase 58: pontos por presenca em atividade.
 *
 * O cadastro e' do Administrador: o tipo de atividade da' os pontos de
 * presenca e o extra de pontualidade, a atividade pode ter valor proprio, e
 * os minutos de antecedencia do extra ficam na configuracao da Gamificacao.
 * O que o codigo sabe fazer e' calcular e gravar o credito, congelado.
 *
 * Presenca pela internet (codigo de 5 caracteres) segue a mesma regra, por
 * decisao do dono: o facilitador informa o codigo ao abrir a sala virtual,
 * entao quem esta' remoto tambem pode chegar antes.
 *
 * Nada aqui roda com a gincana encerrada (GamificacaoService::encerrada()):
 * a presenca continua registrada, os pontos nao se movem.
 */
class PresencaPontuacaoService
{
    private $creditos;

    public function __construct()
    {
        $this->creditos = new PresencaCreditoRepository();
    }

    /**
     * Credita a presenca que acabou de ser registrada ou reativada por nova
     * leitura. Devolve ['pontos_presenca' => n, 'pontos_pontualidade' => n]
     * quando creditou agora, ['facilitador' => true] quando a pessoa e'
     * facilitadora da atividade (N1: presenca sem pontos), ['encerrada' =>
     * true] depois do encerramento, ou null quando nao havia o que creditar.
     *
     * $checkin e' a linha de evento_checkins como ficou depois da gravacao
     * (com o horario da leitura nova, no caso de reativacao).
     */
    public function creditar(array $evento, array $atividade, array $checkin, $usuarioId)
    {
        $eventoId = (int) $evento['id'];

        if (GamificacaoService::encerrada($eventoId)) {
            return ['encerrada' => true];
        }

        $facilitacao = (new EventoAtividadeFacilitadorRepository())->buscarPorAtividadeEUsuario((int) $atividade['id'], (int) $usuarioId);

        if ($facilitacao !== null && $facilitacao['removido_em'] === null) {
            return ['facilitador' => true];
        }

        $valores = $this->calcular($evento, $atividade, $checkin['checkin_em']);

        if ($valores['pontos_presenca'] + $valores['pontos_pontualidade'] <= 0) {
            return null;
        }

        $existente = $this->creditos->buscarPorAtividadeEInscricao((int) $atividade['id'], (int) $checkin['evento_inscricao_id']);

        if ($existente !== null) {
            // So' a anulacao PELO SISTEMA (presenca removida) volta por aqui,
            // recalculada pela leitura nova. Credito valido nao muda, e a
            // anulacao feita por pessoa nunca volta sozinha.
            if ($existente['anulado_em'] === null || $existente['anulado_por'] !== null) {
                return null;
            }

            $alterou = $this->creditos->recalcularAposNovaLeitura(
                (int) $existente['id'],
                (int) $checkin['id'],
                $valores['pontos_presenca'],
                $valores['pontos_pontualidade'],
                $valores['minutos_antes_do_inicio'],
                $valores['antecedencia_exigida']
            );

            return $alterou ? $valores : null;
        }

        $inseriu = $this->creditos->creditar(
            $eventoId,
            (int) $checkin['id'],
            $valores['pontos_presenca'],
            $valores['pontos_pontualidade'],
            $valores['minutos_antes_do_inicio'],
            $valores['antecedencia_exigida']
        );

        return $inseriu ? $valores : null;
    }

    /**
     * Chamado depois que o Administrador remove uma presenca. Anula PELO
     * SISTEMA o credito valido dela e devolve a linha anulada (para o aviso
     * ao participante), ou null.
     */
    public function anularPorRemocao(array $evento, array $checkin, $motivo)
    {
        if (GamificacaoService::encerrada((int) $evento['id'])) {
            return null;
        }

        $credito = $this->creditos->buscarPorAtividadeEInscricao((int) $checkin['atividade_id'], (int) $checkin['evento_inscricao_id']);

        if ($credito === null || $credito['anulado_em'] !== null) {
            return null;
        }

        return $this->creditos->anularPeloSistema((int) $credito['id'], $motivo) ? $credito : null;
    }

    /**
     * Chamado depois que o Administrador restaura uma presenca (horario
     * original preservado). O credito anulado pelo sistema volta como era;
     * sem credito anterior, credita pelo horario original, como a
     * reconferencia faria.
     */
    public function restaurar(array $evento, array $atividade, array $checkin, $usuarioId)
    {
        if (GamificacaoService::encerrada((int) $evento['id'])) {
            return null;
        }

        $credito = $this->creditos->buscarPorAtividadeEInscricao((int) $atividade['id'], (int) $checkin['evento_inscricao_id']);

        if ($credito !== null) {
            return $this->creditos->reverterAnulacaoAutomatica((int) $credito['id']) ? $credito : null;
        }

        return $this->creditar($evento, $atividade, $checkin, $usuarioId);
    }

    /**
     * Reconferencia do evento inteiro: cria o credito que falta para
     * presencas validas ja' existentes, pelo horario gravado da leitura.
     * Devolve quantos creditos criou.
     */
    public function reapurarEvento(array $evento)
    {
        $eventoId = (int) $evento['id'];

        if (GamificacaoService::encerrada($eventoId)) {
            return 0;
        }

        $minutos = (new GamificacaoConfigRepository())->vigente($eventoId)['minutos_pontualidade'];
        $total = 0;

        foreach ($this->creditos->presencasSemCredito($eventoId) as $linha) {
            if ((int) $linha['eh_facilitador'] === 1) {
                continue;
            }

            $atividade = [
                'data_inicio' => $linha['data_inicio'],
                'pontos_presenca' => $linha['atividade_pontos_presenca'],
                'pontos_pontualidade' => $linha['atividade_pontos_pontualidade'],
            ];
            $tipo = [
                'pontos_presenca' => $linha['tipo_pontos_presenca'],
                'pontos_pontualidade' => $linha['tipo_pontos_pontualidade'],
            ];

            $valores = $this->calcularComValores($atividade, $tipo, $minutos, $linha['checkin_em']);

            if ($valores['pontos_presenca'] + $valores['pontos_pontualidade'] <= 0) {
                continue;
            }

            $inseriu = $this->creditos->creditar(
                $eventoId,
                (int) $linha['checkin_id'],
                $valores['pontos_presenca'],
                $valores['pontos_pontualidade'],
                $valores['minutos_antes_do_inicio'],
                $valores['antecedencia_exigida']
            );

            if ($inseriu) {
                $total++;
            }
        }

        return $total;
    }

    /**
     * Pontos que a atividade vale hoje, para as telas (Regras do jogo).
     * Mesmo calculo de creditar(), sem o horario da leitura.
     */
    public static function valoresVigentes(array $atividade, $tipo = null)
    {
        $presenca = $atividade['pontos_presenca'] !== null && $atividade['pontos_presenca'] !== ''
            ? (int) $atividade['pontos_presenca']
            : ($tipo !== null ? (int) $tipo['pontos_presenca'] : 0);
        $pontualidade = $atividade['pontos_pontualidade'] !== null && $atividade['pontos_pontualidade'] !== ''
            ? (int) $atividade['pontos_pontualidade']
            : ($tipo !== null ? (int) $tipo['pontos_pontualidade'] : 0);

        return ['pontos_presenca' => $presenca, 'pontos_pontualidade' => $pontualidade];
    }

    private function calcular(array $evento, array $atividade, $checkinEm)
    {
        $tipo = null;

        if (!empty($atividade['tipo_id'])) {
            $tipo = (new EventoAtividadeTipoRepository())->buscarDoEvento((int) $evento['id'], (int) $atividade['tipo_id']);
        }

        $minutos = (new GamificacaoConfigRepository())->vigente((int) $evento['id'])['minutos_pontualidade'];

        return $this->calcularComValores($atividade, $tipo, $minutos, $checkinEm);
    }

    /**
     * O extra de pontualidade vale quando a leitura aconteceu ate' N minutos
     * antes do inicio da atividade (dinamica de pontos v2: "ao chegar com 5
     * minutos de antecedencia, recebe 5 pontos extras"). Sem minutos
     * configurados, o extra nao se aplica. minutos_antes_do_inicio fica
     * negativo quando a leitura foi depois do inicio.
     */
    private function calcularComValores(array $atividade, $tipo, $minutosExigidos, $checkinEm)
    {
        $valores = self::valoresVigentes($atividade, $tipo);
        $segundosAntes = strtotime($atividade['data_inicio']) - strtotime($checkinEm);
        $minutosAntes = (int) floor($segundosAntes / 60);

        $ganhaExtra = $minutosExigidos !== null && $segundosAntes >= ((int) $minutosExigidos * 60);

        return [
            'pontos_presenca' => $valores['pontos_presenca'],
            'pontos_pontualidade' => $ganhaExtra ? $valores['pontos_pontualidade'] : 0,
            'minutos_antes_do_inicio' => $minutosAntes,
            'antecedencia_exigida' => $minutosExigidos,
        ];
    }
}
