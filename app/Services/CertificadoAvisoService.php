<?php

namespace App\Services;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Repositories\CertificadoAvisoRepository;
use App\Repositories\EventoComunicacaoRepository;
use App\Repositories\NotificacaoPainelRepository;

/**
 * Aviso de que ha certificado disponivel para retirada, no molde de
 * EventoAnaisAvisoService: sino para cada pessoa e uma campanha de correio
 * eletronico pela fila.
 *
 * So' sai quando o certificado pode ser retirado de fato (evento terminado,
 * modulo ligado e emissao aberta), conferido aqui e nao so' na tela. Cada
 * documento avisado fica registrado, e o envio seguinte considera so' os
 * documentos ainda sem registro: fechar e reabrir a emissao, ou mudar a
 * regua, nunca reenvia o que ja foi avisado. Coautor sem conta nao recebe,
 * porque so' alcanca o documento pela tela administrativa.
 */
class CertificadoAvisoService
{
    private $elegibilidade;
    private $avisos;

    public function __construct()
    {
        $this->elegibilidade = new CertificadoElegibilidadeService();
        $this->avisos = new CertificadoAvisoRepository();
    }

    public function podeAvisar(array $evento, array $config)
    {
        return $this->elegibilidade->emissaoAbertaAoParticipante($evento, $config);
    }

    /**
     * Pessoas com documento elegivel ainda nao avisado, indexadas pelo
     * usuario: ['nome', 'email', 'documentos' => [['chave', 'rotulo']]].
     */
    public function pendentes(array $evento, array $config)
    {
        $avisadas = $this->avisos->chavesAvisadas((int) $evento['id']);
        $pessoas = [];

        foreach ($this->elegibilidade->dossiesDoEvento($evento, $config) as $dossie) {
            if ($dossie['usuario_id'] === null || empty($dossie['email'])) {
                continue;
            }

            foreach ($dossie['itens'] as $item) {
                if (isset($avisadas[$item['chave']])) {
                    continue;
                }

                $usuarioId = (int) $dossie['usuario_id'];

                if (!isset($pessoas[$usuarioId])) {
                    $pessoas[$usuarioId] = ['nome' => $dossie['nome'], 'email' => $dossie['email'], 'documentos' => []];
                }

                $pessoas[$usuarioId]['documentos'][] = ['chave' => $item['chave'], 'rotulo' => $item['rotulo']];
            }
        }

        return $pessoas;
    }

    public function contarPendentes(array $evento, array $config)
    {
        $total = 0;

        foreach ($this->pendentes($evento, $config) as $pessoa) {
            $total += count($pessoa['documentos']);
        }

        return $total;
    }

    /**
     * Devolve ['pessoas' => n, 'documentos' => n].
     */
    public function avisar(array $evento, array $config, $adminUsuarioId)
    {
        if (!$this->podeAvisar($evento, $config)) {
            throw new \RuntimeException('O aviso só pode sair depois do último dia do evento, com o módulo ligado e a emissão aberta para quem participou.');
        }

        $eventoId = (int) $evento['id'];
        $pessoas = $this->pendentes($evento, $config);

        if ($pessoas === []) {
            return ['pessoas' => 0, 'documentos' => 0];
        }

        $painel = new NotificacaoPainelRepository();
        $destinatarios = [];
        $documentos = [];
        $endereco = url('eventoApp/certificados/' . $eventoId);

        foreach ($pessoas as $usuarioId => $pessoa) {
            $rotulos = array_map(function ($documento) {
                return $documento['rotulo'];
            }, $pessoa['documentos']);

            $painel->criar(
                $usuarioId,
                'certificado_disponivel',
                count($rotulos) === 1 ? 'Certificado disponível' : 'Certificados disponíveis',
                'Já pode retirar no aplicativo: ' . implode('; ', $rotulos) . '.',
                ['url' => $endereco]
            );

            $destinatarios[] = ['usuario_id' => $usuarioId, 'email' => $pessoa['email'], 'nome' => $pessoa['nome']];

            foreach ($pessoa['documentos'] as $documento) {
                $documentos[] = ['usuario_id' => $usuarioId, 'chave' => $documento['chave']];
            }
        }

        (new EventoComunicacaoRepository())->criarCampanhaAvulsa([
            'evento_id' => $eventoId,
            'autor_usuario_id' => $adminUsuarioId,
            'tipo' => 'comunicado',
            'assunto' => 'Certificado disponível: ' . $evento['nome'],
            'corpo_html' => $this->montarCorpo($evento),
        ], $destinatarios);

        $this->avisos->registrar($eventoId, $documentos, $adminUsuarioId);

        return ['pessoas' => count($pessoas), 'documentos' => count($documentos)];
    }

    private function montarCorpo(array $evento)
    {
        $endereco = urlAbsoluta('eventoApp/certificados/' . (int) $evento['id']);

        return '<p>Olá,</p>'
            . '<p>Há certificado disponível para você no evento <strong>' . htmlspecialchars($evento['nome'], ENT_QUOTES, 'UTF-8') . '</strong>.</p>'
            . '<p>Para retirar, entre no aplicativo do evento com o seu e-mail e a sua senha (ou com a sua conta Google) e abra a tela de certificados:<br>'
            . '<a href="' . htmlspecialchars($endereco, ENT_QUOTES, 'UTF-8') . '">' . htmlspecialchars($endereco, ENT_QUOTES, 'UTF-8') . '</a></p>'
            . '<p>Cada certificado traz um código de conferência, que qualquer pessoa pode usar para confirmar que o documento é verdadeiro.</p>';
    }
}
