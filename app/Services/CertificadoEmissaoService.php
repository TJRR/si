<?php

namespace App\Services;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Repositories\CertificadoRepository;

/**
 * Fase 59: emite o certificado, o grava e devolve a linha.
 *
 * Decisao do dono: o documento e' GUARDADO na primeira emissao e todo pedido
 * seguinte entrega o mesmo arquivo. Presenca removida depois nao altera o que
 * ja' foi entregue, e o caminho de correcao e' o cancelamento na tela de
 * Certificados.
 *
 * A primeira das duas vias que acontecer e' a que congela: o pedido do
 * proprio interessado no aplicativo ou a emissao pela organizacao. As duas
 * passam por aqui, e a chave unica da migration 199 garante um documento so'
 * mesmo quando as duas acontecem no mesmo instante.
 */
class CertificadoEmissaoService
{
    /**
     * Teto de itens por chamada. Cada certificado e' uma renderizacao de PDF,
     * e acima disso o tempo de resposta do servidor fica imprevisivel. A tela
     * avisa o teto antes do envio.
     */
    const TETO_LOTE = 50;

    private $certificados;
    private $textos;
    private $pdf;

    public function __construct()
    {
        $this->certificados = new CertificadoRepository();
        $this->textos = new CertificadoTextoService();
        $this->pdf = new CertificadoPdfService();
    }

    /**
     * O texto configurado para um tipo, ou null. Sem texto escrito nao ha'
     * emissao: o sistema nao inventa o conteudo de um documento assinado pela
     * instituicao.
     */
    public static function textoDoTipo(array $config, $tipo)
    {
        $campos = [
            CertificadoElegibilidadeService::TIPO_EVENTO => 'texto_evento_html',
            CertificadoElegibilidadeService::TIPO_ATIVIDADE => 'texto_atividade_html',
            CertificadoElegibilidadeService::TIPO_APRESENTACAO => 'texto_apresentacao_html',
        ];

        if (!isset($campos[$tipo]) || empty($config[$campos[$tipo]])) {
            return null;
        }

        $texto = (string) $config[$campos[$tipo]];

        // A imagem conta como conteudo, e nao so' o texto: um certificado
        // pode ser uma arte unica com o dizer ja' embutido, inserida pelo
        // editor rico. Mesmo criterio da gravacao, em
        // CertificadoAdminController::textoRicoOuNulo() - sem ele, a tela
        // aceitaria o texto e a emissao o recusaria.
        if (trim(strip_tags($texto, '<img>')) !== '' || strpos($texto, '<img') !== false) {
            return $texto;
        }

        return null;
    }

    /**
     * Emite UM item do dossie. Devolve ['certificado' => linha, 'novo' =>
     * bool] ou lanca RuntimeException com a mensagem pronta para a tela.
     *
     * $emitidoPor nulo significa emitido pelo proprio interessado.
     */
    public function emitirItem(array $evento, array $config, array $dossie, array $item, $emitidoPor = null)
    {
        $eventoId = (int) $evento['id'];
        $existente = $this->certificados->buscarPorChave($eventoId, $item['chave']);

        if ($existente !== null) {
            return ['certificado' => $existente, 'novo' => false];
        }

        $texto = self::textoDoTipo($config, $item['tipo']);

        if ($texto === null) {
            throw new \RuntimeException('O texto deste tipo de certificado ainda não foi escrito em Certificados, Configurações.');
        }

        $codigo = CodigoUnicoService::gerar('evento_certificados', 'codigo_verificacao', 10);
        $condicoes = $this->condicoesDoItem($dossie, $item);
        $dados = $this->textos->montarDados($evento, $dossie, $item, $codigo, date('Y-m-d'), $condicoes);
        $corpo = CertificadoPdfService::imagensLocais($this->textos->resolver($texto, $dados));

        $conteudo = $this->pdf->renderizar([
            'corpoHtml' => $corpo,
            'fundoCaminho' => CertificadoPdfService::caminhoLocalDoFundo($item['fundo_url']),
            // A cor vale quando nao ha' imagem: o seletor da tela nunca deixa
            // as duas escolhidas, e aqui a imagem venceria de todo modo.
            'fundoCor' => isset($item['fundo_cor']) ? $item['fundo_cor'] : null,
            'codigo' => $codigo,
            'enderecoConferencia' => urlAbsoluta('certificadoPublico/index'),
        ]);

        $arquivo = $this->pdf->gravar($eventoId, $conteudo);

        $linha = $this->certificados->criar([
            'evento_id' => $eventoId,
            'tipo' => $item['tipo'],
            'chave_unicidade' => $item['chave'],
            'usuario_id' => $dossie['usuario_id'],
            'evento_inscricao_id' => $dossie['inscricao_id'],
            'atividade_id' => $item['atividade_id'],
            'trabalho_id' => $item['trabalho_id'],
            'trabalho_autor_id' => $item['trabalho_autor_id'],
            'condicoes' => implode(', ', $condicoes),
            'nome' => $dossie['nome'],
            'documento' => $dossie['documento'],
            'tipo_documento' => $dossie['tipo_documento'],
            'carga_horaria_minutos' => $item['carga_horaria_minutos'],
            'periodo_inicio' => substr((string) $evento['data_inicio'], 0, 10),
            'periodo_fim' => substr((string) $evento['data_fim'], 0, 10),
            'codigo_verificacao' => $codigo,
            'arquivo_path' => $arquivo['arquivo_path'],
            'tamanho_bytes' => $arquivo['tamanho_bytes'],
            'sha256' => $arquivo['sha256'],
            'emitido_por' => $emitidoPor,
        ]);

        // Quem perdeu a corrida da chave unica recebe de volta a linha que a
        // outra requisicao gravou. O PDF que este caminho acabou de gerar nao
        // serve para ninguem, entao e' apagado: sem isso, cada duplo clique
        // deixaria um arquivo orfao na area privada.
        $novo = $linha['arquivo_path'] === $arquivo['arquivo_path'];

        if (!$novo) {
            $this->pdf->descartar($arquivo['arquivo_path']);
        }

        return ['certificado' => $linha, 'novo' => $novo];
    }

    /**
     * Emite TODOS os itens de um dossie, parando no teto. Devolve
     * ['emitidos' => n, 'repetidos' => n, 'falhas' => [mensagem, ...]].
     *
     * Falha de um item nao impede os outros: um texto que falte ou um fundo
     * ilegivel num tipo nao pode travar a entrega dos demais.
     */
    public function emitirDossie(array $evento, array $config, array $dossie, $emitidoPor = null, $teto = self::TETO_LOTE)
    {
        $resultado = ['emitidos' => 0, 'repetidos' => 0, 'falhas' => []];

        foreach ($dossie['itens'] as $item) {
            if ($resultado['emitidos'] >= $teto) {
                break;
            }

            try {
                $saida = $this->emitirItem($evento, $config, $dossie, $item, $emitidoPor);
                $resultado[$saida['novo'] ? 'emitidos' : 'repetidos']++;
            } catch (\Throwable $e) {
                error_log('[Certificado] Falha ao emitir ' . $item['chave'] . ': ' . $e->getMessage());
                $resultado['falhas'][] = $item['rotulo'] . ': ' . $e->getMessage();
            }
        }

        return $resultado;
    }

    /**
     * Emite os itens de varios dossies de uma vez, respeitando o teto total
     * da chamada. Usado pela tela administrativa.
     */
    public function emitirDossies(array $evento, array $config, array $dossies, $emitidoPor, $teto = self::TETO_LOTE)
    {
        $total = ['emitidos' => 0, 'repetidos' => 0, 'falhas' => [], 'alcancou_teto' => false];

        foreach ($dossies as $dossie) {
            if ($total['emitidos'] >= $teto) {
                $total['alcancou_teto'] = true;
                break;
            }

            $parcial = $this->emitirDossie($evento, $config, $dossie, $emitidoPor, $teto - $total['emitidos']);
            $total['emitidos'] += $parcial['emitidos'];
            $total['repetidos'] += $parcial['repetidos'];
            $total['falhas'] = array_merge($total['falhas'], $parcial['falhas']);
        }

        return $total;
    }

    /**
     * As condicoes do certificado, como lista de chaves. No do evento, todas
     * as que qualificaram a pessoa; no de atividade, a condicao daquela
     * atividade (quem conduziu recebe como facilitador, mesmo que tenha
     * assistido a outras como participante); no de apresentacao, "autor",
     * que nao e' condicao de participacao no evento e por isso nao entra na
     * lista do dossie.
     */
    private function condicoesDoItem(array $dossie, array $item)
    {
        if ($item['tipo'] === CertificadoElegibilidadeService::TIPO_ATIVIDADE) {
            return [isset($item['condicao']) ? $item['condicao'] : CertificadoElegibilidadeService::CONDICAO_PARTICIPANTE];
        }

        if ($item['tipo'] === CertificadoElegibilidadeService::TIPO_APRESENTACAO) {
            return ['autor'];
        }

        return $dossie['condicoes'];
    }
}
