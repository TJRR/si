<?php

namespace App\Services;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Repositories\CampoDinamicoRepository;
use App\Repositories\ConcursoRepository;
use App\Repositories\DesafioRepository;
use App\Repositories\EquipeRepository;
use App\Repositories\EtapaRepository;
use App\Repositories\SubmissaoRepository;
use App\Repositories\TemaRepository;

/**
 * Fase 30: dono unico da lista de palavras-chave disponiveis pra um modelo
 * de documento - a mesma constante alimenta o seletor "Inserir
 * palavra-chave" e o dicionario de referencia no editor rico, e a
 * resolucao real na hora de gerar o PDF, pra nunca sair de sincronia.
 * Colchete duplo (ex.: "[[lider.nome]]") passa incolume por todo o
 * mecanismo do editor rico hoje - confirmado antes de desenhar esta fase
 * que nao existe motor de modelo nenhum tentando interpretar essa marcacao
 * em nenhum outro lugar do sistema.
 */
class ModeloDocumentoService
{
    const PALAVRAS_CHAVE = [
        'lider.nome' => 'Nome completo do líder da equipe',
        'lider.cpf' => 'CPF do líder da equipe',
        'lider.email' => 'E-mail do líder da equipe',
        'lider.telefone' => 'Telefone/WhatsApp do líder da equipe',
        'equipe.nome' => 'Nome da equipe',
        'equipe.integrantes' => 'Lista com o nome de todos os integrantes homologados (e o papel de cada um)',
        'equipe.tema' => 'Tema do desafio vinculado à equipe',
        'equipe.desafio' => 'Desafio vinculado à equipe',
        'equipe.solucao_proposta' => 'Texto de "solução proposta" já enviado na submissão da equipe (quando existir)',
        'concurso.nome' => 'Nome do concurso',
        'trilha.nome' => 'Nome da trilha',
        'data_atual' => 'Data de hoje, por extenso',
        'necessidade' => 'Texto de "Necessidade" que o líder escreveu ao protocolar o pedido',
    ];

    private $equipes;
    private $temas;
    private $desafios;
    private $concursos;
    private $etapas;
    private $submissoes;
    private $campos;

    public function __construct()
    {
        $this->equipes = new EquipeRepository();
        $this->temas = new TemaRepository();
        $this->desafios = new DesafioRepository();
        $this->concursos = new ConcursoRepository();
        $this->etapas = new EtapaRepository();
        $this->submissoes = new SubmissaoRepository();
        $this->campos = new CampoDinamicoRepository();
    }

    /**
     * Monta o vetor associativo com as palavras-chave ja resolvidas (chave
     * SEM colchete, ex. "lider.nome") - resolver() faz a troca de verdade
     * no corpo do modelo. Nunca lanca excecao so por dado ausente (equipe
     * sem desafio escolhido, sem submissao ainda etc.) - vira texto vazio.
     */
    public function montarDados(array $equipe, array $liderParticipante, array $trilha, $necessidade)
    {
        $concurso = $this->concursos->buscarPorId($trilha['concurso_id']);

        $tema = null;
        $desafio = null;
        if ($equipe['desafio_id'] !== null) {
            $desafio = $this->desafios->buscarPorId($equipe['desafio_id']);
            $tema = $desafio !== null ? $this->temas->buscarPorId($desafio['tema_id']) : null;
        }

        return [
            'lider.nome' => $liderParticipante['nome'],
            'lider.cpf' => (string) $liderParticipante['cpf'],
            'lider.email' => (string) $liderParticipante['email'],
            'lider.telefone' => (string) $liderParticipante['telefone'],
            'equipe.nome' => $equipe['nome_equipe'],
            'equipe.integrantes' => $this->listaIntegrantes($equipe['id']),
            'equipe.tema' => $tema !== null ? $tema['nome'] : '',
            'equipe.desafio' => $desafio !== null ? $desafio['pergunta'] : '',
            'equipe.solucao_proposta' => $this->resolverSolucaoProposta($equipe, $trilha['id']),
            'concurso.nome' => $concurso !== null ? $concurso['nome'] : '',
            'trilha.nome' => $trilha['nome'],
            'data_atual' => $this->dataPorExtenso(),
            'necessidade' => (string) $necessidade,
        ];
    }

    /**
     * Troca cada [[chave]] pelo valor ja escapado - o corpo do modelo em si
     * (o que o Administrador escreveu no editor rico) segue o mesmo nivel
     * de confianca que o resto do sistema ja da' a esse perfil (sem
     * sanitizacao adicional, mesma decisao ja documentada em
     * editor-rico.js); so' o DADO substituido (nome, cpf, necessidade etc.)
     * e' escapado, por vir de participante/lider, que nao e' fonte
     * confiavel da mesma forma. "necessidade" e "equipe.solucao_proposta"
     * preservam quebra de linha (nl2br) - sao as duas com texto livre de
     * mais de uma linha.
     */
    public function resolver($corpoHtml, array $dados)
    {
        $comQuebraDeLinha = ['necessidade', 'equipe.solucao_proposta'];

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
     * Varre o corpo por qualquer [[...]] fora da lista conhecida - chamado
     * ao salvar um modelo (ModeloDocumentoAdminController), pra pegar erro
     * de digitacao (ex.: "[[lider.nomee]]") antes de virar um documento
     * juridico com a chave aparecendo literalmente no PDF final.
     */
    public function palavrasChaveDesconhecidas($corpoHtml)
    {
        preg_match_all('/\[\[([a-z0-9_\.]+)\]\]/i', $corpoHtml, $correspondencias);
        $encontradas = array_unique($correspondencias[1]);

        return array_values(array_diff($encontradas, array_keys(self::PALAVRAS_CHAVE)));
    }

    private function listaIntegrantes($equipeId)
    {
        $partes = [];

        foreach ($this->equipes->listarParticipantes($equipeId) as $participante) {
            if ($participante['status_homologacao'] !== 'homologado') {
                continue;
            }

            $partes[] = $participante['nome'] . ' (' . ($participante['papel'] === 'lider' ? 'líder' : 'integrante') . ')';
        }

        return implode('; ', $partes);
    }

    /**
     * Busca, entre as etapas da trilha, o campo dinamico marcado como
     * "_papel_documento" = "solucao_proposta" (marcacao feita pelo
     * Administrador em Campos, ver CampoDinamicoService::montarConfig()) e
     * devolve o valor respondido pela equipe na submissao daquela etapa.
     * Texto vazio se a marcacao ainda nao existir, ou se a equipe nao
     * tiver submetido.
     */
    private function resolverSolucaoProposta(array $equipe, $trilhaId)
    {
        foreach ($this->etapas->listarPorTrilha($trilhaId) as $etapa) {
            if ($etapa['formulario_dinamico_id'] === null) {
                continue;
            }

            foreach ($this->campos->listarPorFormulario($etapa['formulario_dinamico_id']) as $campo) {
                $config = $campo['config_json'] !== null ? json_decode($campo['config_json'], true) : [];

                if (!isset($config['_papel_documento']) || $config['_papel_documento'] !== 'solucao_proposta') {
                    continue;
                }

                $submissao = $this->submissoes->buscarPorEquipeEEtapa($equipe['id'], $etapa['id']);

                if ($submissao === null) {
                    return '';
                }

                $dados = json_decode((string) $submissao['dados_json'], true);
                $valores = isset($dados['campos']) && is_array($dados['campos']) ? $dados['campos'] : [];
                $valor = isset($valores[(string) $campo['id']]) ? $valores[(string) $campo['id']] : '';

                return is_string($valor) ? $valor : '';
            }
        }

        return '';
    }

    private function dataPorExtenso()
    {
        $meses = [
            1 => 'janeiro', 2 => 'fevereiro', 3 => 'março', 4 => 'abril', 5 => 'maio', 6 => 'junho',
            7 => 'julho', 8 => 'agosto', 9 => 'setembro', 10 => 'outubro', 11 => 'novembro', 12 => 'dezembro',
        ];

        return date('j') . ' de ' . $meses[(int) date('n')] . ' de ' . date('Y');
    }
}
