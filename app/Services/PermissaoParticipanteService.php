<?php

namespace App\Services;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Repositories\EquipeRepository;

/**
 * Fase 36: ponto unico de checagem do estado de homologacao do
 * participante - antes desta fase, cada controlador reimplementava (ou
 * esquecia de implementar) essa checagem do seu jeito, o que deixou 4 dos 6
 * controladores do participante sem nenhuma trava contra quem foi
 * homologado e depois rejeitado. Os controladores chamam este servico;
 * nenhum le status_homologacao direto.
 */
class PermissaoParticipanteService
{
    const HOMOLOGADO = 'homologado';
    const REJEITADO = 'rejeitado';
    const PENDENTE_NUNCA_HOMOLOGADO = 'pendente_nunca_homologado';
    const PENDENTE_POS_CORRECAO = 'pendente_pos_correcao';

    private $equipes;

    public function __construct()
    {
        $this->equipes = new EquipeRepository();
    }

    /**
     * Um dos 4 rotulos de estado, ou null se o participante nao tem
     * vinculo com nenhuma equipe. pendente-nunca-homologado nao tem conta
     * de acesso (so' nasce na homologacao) - na pratica nunca chega a ser
     * checado por um controlador de participante autenticado, mas o
     * calculo cobre o caso mesmo assim.
     */
    public function estadoDoParticipante($participanteId)
    {
        $equipe = $this->equipes->buscarPorParticipante($participanteId);

        if ($equipe === null) {
            return null;
        }

        $vinculo = $this->equipes->buscarVinculo($equipe['id'], $participanteId);

        if ($vinculo === null) {
            return null;
        }

        return $this->estadoDoVinculo($vinculo);
    }

    private function estadoDoVinculo(array $vinculo)
    {
        $temHistorico = isset($vinculo['tem_historico'])
            ? (bool) $vinculo['tem_historico']
            : $this->equipes->buscarUltimaTransicao($vinculo['id']) !== null;

        return self::estadoDoStatusETemHistorico($vinculo['status_homologacao'], $temHistorico);
    }

    /**
     * $acao in ('reservar_mentoria', 'inscrever_oficina', 'gerar_requerimento', 'submeter') -
     * so' permitido quando o participante esta' homologado agora (Fase 36,
     * Parte A.2: rejeitado e pendente-pos-correcao tem o mesmo conjunto de
     * permissoes, por simetria decidida com o usuario).
     */
    public function podeExecutar($participanteId, $acao)
    {
        return $this->estadoDoParticipante($participanteId) === self::HOMOLOGADO;
    }

    /**
     * Fase 36, Parte A.2 - matriz completa: lider homologado remove
     * qualquer integrante (comportamento atual, sem mudanca); lider
     * rejeitado ou pendente-pos-correcao so' remove integrante rejeitado.
     * As travas de "nao excluir o lider" e "minimo de integrantes" sao
     * checadas antes desta, no proprio controller, e nao mudam.
     */
    public function podeRemoverIntegrante($liderParticipanteId, $alvoParticipanteId)
    {
        if ($this->estadoDoParticipante($liderParticipanteId) === self::HOMOLOGADO) {
            return true;
        }

        return $this->estadoDoParticipante($alvoParticipanteId) === self::REJEITADO;
    }

    /**
     * Mesma regra de estadoDoVinculo(), exposta como estatico pra telas que
     * ja tem status_homologacao/tem_historico em maos (ex.: a tabela de
     * Inscritos, que traz os dois campos numa unica query em lote via
     * EquipeRepository::listarTodosPorTrilha() - evita 1 consulta por
     * linha so' pra calcular o selo).
     */
    public static function estadoDoStatusETemHistorico($status, $temHistorico)
    {
        if ($status === 'homologado') {
            return self::HOMOLOGADO;
        }

        if ($status === 'rejeitado') {
            return self::REJEITADO;
        }

        return $temHistorico ? self::PENDENTE_POS_CORRECAO : self::PENDENTE_NUNCA_HOMOLOGADO;
    }

    /**
     * Fase 36 (Parte D): texto de contexto ("de onde veio"), em texto
     * puro, a partir da ultima transicao registrada para um vinculo hoje
     * pendente - null quando o vinculo nunca tinha sido homologado/
     * rejeitado antes. Reaproveitado por HomologacaoController (tela de
     * Inscritos) e NotificacaoPainelController (check da notificacao de
     * CPF alterado, Fase 35) - os dois caminhos que homologam.
     */
    public static function contextoDeCorrecao($ultimaTransicao)
    {
        if ($ultimaTransicao === null || $ultimaTransicao['status_anterior'] === 'pendente') {
            return null;
        }

        if ($ultimaTransicao['status_anterior'] === 'rejeitado') {
            return 'Este participante havia sido rejeitado antes.'
                . (!empty($ultimaTransicao['motivo']) ? ' Motivo: ' . $ultimaTransicao['motivo'] : '');
        }

        return 'Este participante já havia sido homologado antes.';
    }

    public static function rotuloDoEstado($estado)
    {
        $rotulos = [
            self::HOMOLOGADO => 'Homologado',
            self::REJEITADO => 'Rejeitado',
            self::PENDENTE_NUNCA_HOMOLOGADO => 'Pendente',
            self::PENDENTE_POS_CORRECAO => 'Pendente (novamente)',
        ];

        return isset($rotulos[$estado]) ? $rotulos[$estado] : null;
    }

    public static function corDoEstado($estado)
    {
        $cores = [
            self::HOMOLOGADO => 'verde',
            self::REJEITADO => 'vermelho',
            self::PENDENTE_NUNCA_HOMOLOGADO => 'laranja',
            self::PENDENTE_POS_CORRECAO => 'laranja',
        ];

        return isset($cores[$estado]) ? $cores[$estado] : 'cinza';
    }
}
