<?php

namespace App\Services;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Repositories\EventoAtividadeFacilitadorRepository;
use App\Repositories\TrabalhoAutorRepository;
use App\Validation\CpfValidador;

/**
 * Fase 59: o texto do certificado, escrito pelo Administrador no editor rico,
 * com palavras chave substituidas na geracao.
 *
 * Copia isolada do mecanismo de ModeloDocumentoService (os requerimentos do
 * Concurso): a constante alimenta o dicionario de referencia da tela E a
 * substituicao real, para nunca sair de sincronia, e o colchete duplo passa
 * incolume pelo editor rico. Nao se reaproveita aquele servico porque ele e'
 * codigo ativo do Concurso e o dicionario dele e' de equipe, trilha e
 * necessidade. A duplicacao proposital esta' registrada na pendencia 25.
 *
 * Diferenca em relacao ao precedente: aqui o dicionario e' POR TIPO de
 * certificado. Marcador de atividade no texto do evento nao teria valor para
 * substituir, e sairia em branco num documento assinado - entao ele e'
 * recusado na gravacao, com o nome do marcador na mensagem.
 */
class CertificadoTextoService
{
    const PALAVRAS_CHAVE_COMUNS = [
        'pessoa.nome' => 'Nome completo de quem recebe o certificado',
        'pessoa.documento' => 'Documento de identificação de quem recebe (CPF já formatado, quando for CPF)',
        'pessoa.condicoes' => 'Em que condição a pessoa participou (participante, facilitador, avaliador)',
        'evento.nome' => 'Nome do evento',
        'evento.periodo' => 'Período do evento, por extenso',
        'certificado.carga_horaria' => 'Carga horária declarada, por exemplo "22h30"',
        'certificado.codigo' => 'Código de conferência deste certificado',
        'certificado.data_emissao' => 'Data em que o certificado foi emitido, por extenso',
        'data_atual' => 'Data de hoje, por extenso',
    ];

    const PALAVRAS_CHAVE_ATIVIDADE = [
        'atividade.nome' => 'Nome da atividade',
        'atividade.periodo' => 'Dia e horário da atividade',
        'atividade.local' => 'Local da atividade',
        'atividade.facilitadores' => 'Quem conduziu a atividade, com o perfil de cada um',
    ];

    const PALAVRAS_CHAVE_APRESENTACAO = [
        'trabalho.titulo' => 'Título do trabalho apresentado',
        'trabalho.eixo' => 'Eixo temático do trabalho',
        'trabalho.autores' => 'Nome de todos os autores do trabalho',
    ];

    const MESES = [
        1 => 'janeiro', 2 => 'fevereiro', 3 => 'março', 4 => 'abril', 5 => 'maio', 6 => 'junho',
        7 => 'julho', 8 => 'agosto', 9 => 'setembro', 10 => 'outubro', 11 => 'novembro', 12 => 'dezembro',
    ];

    /**
     * Fase 59: texto modelo de cada tipo, oferecido no editor quando o
     * Administrador ainda nao escreveu o dele. E' ponto de partida, nao
     * valor assumido: aparece na tela, pode ser alterado antes de salvar, e
     * nada e' gravado sem o botao de salvar.
     *
     * Os tamanhos sao em pontos, pensados para a folha A4 na horizontal que
     * CertificadoPdfService usa, e o recuo da primeira linha e' o de um
     * documento oficial. NENHUM modelo cita instituicao, cidade, edital nem
     * regra de uma edicao especifica (premissa 1): tudo o que varia entra por
     * palavra chave ou e' escrito pela organizacao.
     */
    const MODELOS = [
        CertificadoElegibilidadeService::TIPO_EVENTO =>
            '<p style="text-align:center;font-size:26pt;"><strong>CERTIFICADO</strong></p>'
            . '<p style="text-align:justify;text-indent:40px;font-size:14pt;">Certificamos que '
            . '<strong>[[pessoa.nome]]</strong>, documento [[pessoa.documento]], participou do evento '
            . '<strong>[[evento.nome]]</strong>, realizado no período de [[evento.periodo]], na condição de '
            . '[[pessoa.condicoes]], com carga horária de [[certificado.carga_horaria]].</p>'
            . '<p style="text-align:center;font-size:12pt;">[[certificado.data_emissao]]</p>',

        CertificadoElegibilidadeService::TIPO_ATIVIDADE =>
            '<p style="text-align:center;font-size:26pt;"><strong>CERTIFICADO</strong></p>'
            . '<p style="text-align:justify;text-indent:40px;font-size:14pt;">Certificamos que '
            . '<strong>[[pessoa.nome]]</strong>, documento [[pessoa.documento]], participou da atividade '
            . '<strong>[[atividade.nome]]</strong>, realizada em [[atividade.periodo]], em [[atividade.local]], '
            . 'durante o evento [[evento.nome]], com carga horária de [[certificado.carga_horaria]], na condição '
            . 'de [[pessoa.condicoes]].</p>'
            . '<p style="text-align:justify;font-size:11pt;">Atividade conduzida por: [[atividade.facilitadores]]</p>'
            . '<p style="text-align:center;font-size:12pt;">[[certificado.data_emissao]]</p>',

        CertificadoElegibilidadeService::TIPO_APRESENTACAO =>
            '<p style="text-align:center;font-size:26pt;"><strong>CERTIFICADO DE APRESENTAÇÃO</strong></p>'
            . '<p style="text-align:justify;text-indent:40px;font-size:14pt;">Certificamos que '
            . '<strong>[[pessoa.nome]]</strong>, documento [[pessoa.documento]], apresentou o trabalho '
            . '<strong>[[trabalho.titulo]]</strong>, do eixo temático [[trabalho.eixo]], durante o evento '
            . '[[evento.nome]], realizado no período de [[evento.periodo]].</p>'
            . '<p style="text-align:justify;font-size:11pt;">Autoria do trabalho: [[trabalho.autores]]</p>'
            . '<p style="text-align:center;font-size:12pt;">[[certificado.data_emissao]]</p>',
    ];

    /**
     * O texto modelo de um tipo, ou cadeia vazia quando o tipo nao tem
     * modelo.
     */
    public static function modeloDoTipo($tipo)
    {
        return isset(self::MODELOS[$tipo]) ? self::MODELOS[$tipo] : '';
    }

    /**
     * As palavras chave que valem para um tipo: as comuns mais as proprias
     * dele. Alimenta o dicionario da tela e a conferencia da gravacao.
     */
    public static function palavrasChaveDoTipo($tipo)
    {
        if ($tipo === CertificadoElegibilidadeService::TIPO_ATIVIDADE) {
            return array_merge(self::PALAVRAS_CHAVE_COMUNS, self::PALAVRAS_CHAVE_ATIVIDADE);
        }

        if ($tipo === CertificadoElegibilidadeService::TIPO_APRESENTACAO) {
            return array_merge(self::PALAVRAS_CHAVE_COMUNS, self::PALAVRAS_CHAVE_APRESENTACAO);
        }

        return self::PALAVRAS_CHAVE_COMUNS;
    }

    /**
     * Troca cada [[chave]] pelo valor ja escapado. Chave sem valor sai em
     * branco, nunca com o marcador literal: documento com "[[pessoa.nome]]"
     * impresso e' pior que documento com um espaco.
     */
    public function resolver($corpoHtml, array $dados)
    {
        $comQuebraDeLinha = ['atividade.facilitadores', 'trabalho.autores'];

        return preg_replace_callback('/\[\[([a-z0-9_\.]+)\]\]/i', function ($correspondencia) use ($dados, $comQuebraDeLinha) {
            $chave = $correspondencia[1];

            if (!array_key_exists($chave, $dados)) {
                return '';
            }

            $valor = htmlspecialchars((string) $dados[$chave], ENT_QUOTES, 'UTF-8');

            return in_array($chave, $comQuebraDeLinha, true) ? nl2br($valor) : $valor;
        }, $corpoHtml);
    }

    /**
     * Varre o corpo por qualquer [[...]] fora do dicionario daquele tipo -
     * chamado ao salvar a configuracao, para pegar erro de digitacao
     * ("[[pessoa.nomee]]") e marcador de outro tipo ("[[atividade.nome]]" no
     * texto do evento) antes de virar um certificado com lacuna.
     */
    public function palavrasChaveDesconhecidas($corpoHtml, $tipo)
    {
        preg_match_all('/\[\[([a-z0-9_\.]+)\]\]/i', (string) $corpoHtml, $correspondencias);
        $encontradas = array_unique($correspondencias[1]);

        return array_values(array_diff($encontradas, array_keys(self::palavrasChaveDoTipo($tipo))));
    }

    /**
     * Os valores de um certificado, prontos para resolver(). $dossie vem de
     * CertificadoElegibilidadeService e $item e' um dos itens dele.
     *
     * $codigo e $dataEmissao entram de fora porque sao decididos no instante
     * da emissao, nao na apuracao.
     */
    public function montarDados(array $evento, array $dossie, array $item, $codigo, $dataEmissao, array $condicoes = null)
    {
        $dados = [
            'pessoa.nome' => (string) $dossie['nome'],
            'pessoa.documento' => $this->documentoFormatado($dossie),
            'pessoa.condicoes' => $this->condicoesPorExtenso($condicoes !== null ? $condicoes : $dossie['condicoes']),
            'evento.nome' => (string) $evento['nome'],
            'evento.periodo' => $this->periodoPorExtenso($evento['data_inicio'], $evento['data_fim']),
            'certificado.carga_horaria' => $item['carga_horaria_minutos'] !== null
                ? CertificadoElegibilidadeService::formatarCargaHoraria($item['carga_horaria_minutos'])
                : '',
            'certificado.codigo' => (string) $codigo,
            'certificado.data_emissao' => $this->dataPorExtenso($dataEmissao),
            'data_atual' => $this->dataPorExtenso(date('Y-m-d')),
        ];

        if ($item['tipo'] === CertificadoElegibilidadeService::TIPO_ATIVIDADE) {
            $dados += $this->dadosDaAtividade($item);
        }

        if ($item['tipo'] === CertificadoElegibilidadeService::TIPO_APRESENTACAO) {
            $dados += $this->dadosDoTrabalho($item);
        }

        return $dados;
    }

    /**
     * "participante", "participante e facilitador", "participante,
     * facilitador e avaliador". Lista vazia devolve texto vazio, e nunca uma
     * condicao inventada.
     *
     * Condicao fora do mapa de rotulos sai como esta' - e' o caso de "autor",
     * usada no certificado de apresentacao, que nao e' uma condicao de
     * participacao no evento e por isso nao entra na lista do dossie.
     */
    public function condicoesPorExtenso(array $condicoes)
    {
        $rotulos = [];

        foreach ($condicoes as $condicao) {
            $rotulos[] = isset(CertificadoElegibilidadeService::ROTULO_CONDICAO[$condicao])
                ? CertificadoElegibilidadeService::ROTULO_CONDICAO[$condicao]
                : (string) $condicao;
        }

        if ($rotulos === []) {
            return '';
        }

        if (count($rotulos) === 1) {
            return $rotulos[0];
        }

        $ultimo = array_pop($rotulos);

        return implode(', ', $rotulos) . ' e ' . $ultimo;
    }

    /**
     * "4 a 6 de novembro de 2026", ou "6 de novembro de 2026" quando comeca e
     * termina no mesmo dia. Mes diferente sai por extenso nos dois lados
     * ("30 de outubro a 2 de novembro de 2026").
     */
    public function periodoPorExtenso($dataInicio, $dataFim)
    {
        $inicio = strtotime(substr((string) $dataInicio, 0, 10));
        $fim = strtotime(substr((string) $dataFim, 0, 10));

        if ($inicio === false || $fim === false) {
            return '';
        }

        if (date('Y-m-d', $inicio) === date('Y-m-d', $fim)) {
            return $this->dataPorExtenso(date('Y-m-d', $fim));
        }

        if (date('Y-m', $inicio) === date('Y-m', $fim)) {
            return (int) date('d', $inicio) . ' a ' . $this->dataPorExtenso(date('Y-m-d', $fim));
        }

        return (int) date('d', $inicio) . ' de ' . self::MESES[(int) date('n', $inicio)]
            . ' a ' . $this->dataPorExtenso(date('Y-m-d', $fim));
    }

    public function dataPorExtenso($data)
    {
        $instante = strtotime((string) $data);

        if ($instante === false) {
            return '';
        }

        return (int) date('d', $instante) . ' de ' . self::MESES[(int) date('n', $instante)] . ' de ' . date('Y', $instante);
    }

    /**
     * CPF sempre por CpfValidador::formatar(), como no resto do sistema.
     * Documento de outro tipo sai como foi gravado.
     */
    private function documentoFormatado(array $dossie)
    {
        $documento = (string) $dossie['documento'];

        if ($documento === '') {
            return '';
        }

        return (string) $dossie['tipo_documento'] === 'CPF' ? CpfValidador::formatar($documento) : $documento;
    }

    private function dadosDaAtividade(array $item)
    {
        $facilitadores = [];

        foreach ((new EventoAtividadeFacilitadorRepository())->listarPorAtividade((int) $item['atividade_id']) as $linha) {
            $facilitadores[] = $linha['perfil_nome'] !== null
                ? $linha['usuario_nome'] . ' (' . $linha['perfil_nome'] . ')'
                : $linha['usuario_nome'];
        }

        return [
            'atividade.nome' => (string) $item['rotulo'],
            'atividade.periodo' => $this->periodoDaAtividade($item['data_inicio'], $item['data_fim']),
            'atividade.local' => (string) $item['detalhe'],
            'atividade.facilitadores' => implode("\n", $facilitadores),
        ];
    }

    /**
     * "5 de novembro de 2026, das 14h as 18h". Atividade que atravessa a
     * meia noite traz as duas datas, em vez de um horario que nao fecha.
     */
    private function periodoDaAtividade($dataInicio, $dataFim)
    {
        $inicio = strtotime((string) $dataInicio);
        $fim = strtotime((string) $dataFim);

        if ($inicio === false || $fim === false) {
            return '';
        }

        if (date('Y-m-d', $inicio) !== date('Y-m-d', $fim)) {
            return 'de ' . $this->dataPorExtenso(date('Y-m-d', $inicio)) . ', ' . $this->hora($inicio)
                . ', a ' . $this->dataPorExtenso(date('Y-m-d', $fim)) . ', ' . $this->hora($fim);
        }

        return $this->dataPorExtenso(date('Y-m-d', $inicio)) . ', das ' . $this->hora($inicio) . ' às ' . $this->hora($fim);
    }

    private function hora($instante)
    {
        return (int) date('i', $instante) === 0 ? date('G', $instante) . 'h' : date('G\hi', $instante);
    }

    private function dadosDoTrabalho(array $item)
    {
        $autores = [];

        foreach ((new TrabalhoAutorRepository())->listarPorTrabalho((int) $item['trabalho_id']) as $autor) {
            $autores[] = $autor['nome'];
        }

        return [
            'trabalho.titulo' => (string) $item['detalhe'],
            'trabalho.eixo' => isset($item['eixo_nome']) ? (string) $item['eixo_nome'] : '',
            'trabalho.autores' => implode("\n", $autores),
        ];
    }
}
