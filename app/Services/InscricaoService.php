<?php

namespace App\Services;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Repositories\CampoDinamicoRepository;
use App\Repositories\EquipeRepository;
use App\Repositories\EtapaRepository;
use App\Repositories\FormularioDinamicoRepository;
use App\Repositories\ParticipanteRepository;
use App\Repositories\TrilhaRepository;
use App\Validation\CpfValidador;

/**
 * Fluxo real de inscricao de equipe (formulario publico, sem login) dos
 * editais 2026 - grava direto em equipes/participantes/equipe_participante
 * (status_homologacao = 'pendente'), diferente de SubmissaoService (que
 * grava em submissoes.dados_json). Interpreta os campos do Formulario
 * Dinamico pela marca semantica "_papel" no config_json (ver
 * database/seed_formularios_inscricao.php), nao pelo texto do rotulo.
 */
class InscricaoService
{
    private $etapas;
    private $trilhas;
    private $formularios;
    private $campos;
    private $equipes;
    private $participantes;

    public function __construct()
    {
        $this->etapas = new EtapaRepository();
        $this->trilhas = new TrilhaRepository();
        $this->formularios = new FormularioDinamicoRepository();
        $this->campos = new CampoDinamicoRepository();
        $this->equipes = new EquipeRepository();
        $this->participantes = new ParticipanteRepository();
    }

    public function preparar($etapaId)
    {
        $etapa = $this->etapas->buscarPorId($etapaId);

        if ($etapa === null || $etapa['formulario_dinamico_id'] === null) {
            return ['sucesso' => false, 'mensagem' => 'Não há formulário de inscrição disponível para esta trilha.'];
        }

        if (!$etapa['captura_ativa']) {
            return ['sucesso' => false, 'mensagem' => 'As inscrições para esta trilha não estão abertas no momento.'];
        }

        $formulario = $this->formularios->buscarPorId($etapa['formulario_dinamico_id']);

        if ($formulario === null || $formulario['status'] !== 'publicado') {
            return ['sucesso' => false, 'mensagem' => 'Este formulário não está disponível no momento.'];
        }

        $hoje = date('Y-m-d');

        if ($etapa['data_inicio'] !== null && $hoje < $etapa['data_inicio']) {
            return ['sucesso' => false, 'mensagem' => 'O período de inscrição ainda não começou.'];
        }

        if ($etapa['data_fim'] !== null && $hoje > $etapa['data_fim']) {
            return ['sucesso' => false, 'mensagem' => 'O período de inscrição já foi encerrado.'];
        }

        $trilha = $this->trilhas->buscarPorId($etapa['trilha_id']);

        return [
            'sucesso' => true,
            'etapa' => $etapa,
            'formulario' => $formulario,
            'trilha' => $trilha,
            'campos' => $this->campos->listarPorFormulario($formulario['id']),
        ];
    }

    public function processar($etapaId, array $post)
    {
        $preparo = $this->preparar($etapaId);

        if (!$preparo['sucesso']) {
            return $preparo;
        }

        $postCampos = isset($post['campos']) && is_array($post['campos']) ? $post['campos'] : [];
        $erros = [];
        $emailContato = null;
        $nomeEquipe = null;
        $participantesPorIndice = [];

        foreach ($preparo['campos'] as $campo) {
            $campoId = (int) $campo['id'];
            $config = $campo['config_json'] !== null ? json_decode($campo['config_json'], true) : [];
            $valorPost = isset($postCampos[$campoId]) ? $postCampos[$campoId] : null;

            $resultado = $this->validarCampo($campo, $valorPost);

            if (!$resultado['valido']) {
                $erros[$campoId] = $resultado['mensagem'];
                continue;
            }

            $valor = $resultado['valor'];
            $papel = isset($config['_papel']) ? $config['_papel'] : null;

            if ($papel === 'email_contato') {
                $emailContato = $valor;
            } elseif ($papel === 'nome_equipe') {
                $nomeEquipe = $valor;
            } elseif ($papel === 'participante') {
                $indice = (int) $config['indice'];
                $participantesPorIndice[$indice][$config['campo']] = $valor;
            }
        }

        if (!empty($erros)) {
            return ['sucesso' => false, 'mensagem' => 'Corrija os campos indicados.', 'erros' => $erros];
        }

        if ($nomeEquipe === null || $nomeEquipe === '') {
            return ['sucesso' => false, 'mensagem' => 'Informe o nome da equipe.', 'erros' => $erros];
        }

        if ($this->equipes->buscarPorTrilhaENome($preparo['trilha']['id'], $nomeEquipe) !== null) {
            return ['sucesso' => false, 'mensagem' => 'Já existe uma equipe inscrita com este nome nesta trilha.', 'erros' => $erros];
        }

        ksort($participantesPorIndice);
        $participantesValidos = [];
        $cpfsNaInscricao = [];

        foreach ($participantesPorIndice as $indice => $dados) {
            $preenchido = array_filter($dados, function ($valor) {
                return $valor !== null && $valor !== '';
            });

            if (empty($preenchido)) {
                continue;
            }

            if (empty($dados['nome']) || empty($dados['cpf'])) {
                return [
                    'sucesso' => false,
                    'mensagem' => "Participante {$indice}: preencha ao menos nome e CPF, ou deixe todos os campos dele em branco.",
                    'erros' => $erros,
                ];
            }

            $cpfsNaInscricao[] = $dados['cpf'];
            $participantesValidos[$indice] = $dados;
        }

        if (empty($participantesValidos)) {
            return ['sucesso' => false, 'mensagem' => 'Informe ao menos o participante 1 (líder da equipe).', 'erros' => $erros];
        }

        $cpfsDuplicados = array_diff_assoc($cpfsNaInscricao, array_unique($cpfsNaInscricao));

        if (!empty($cpfsDuplicados)) {
            return [
                'sucesso' => false,
                'mensagem' => 'Há CPFs repetidos dentro da própria inscrição: ' . implode(', ', array_unique($cpfsDuplicados)),
                'erros' => $erros,
            ];
        }

        foreach (array_unique($cpfsNaInscricao) as $cpf) {
            if ($this->equipes->cpfJaInscritoNaTrilha($preparo['trilha']['id'], $cpf)) {
                return [
                    'sucesso' => false,
                    'mensagem' => "O CPF {$cpf} já está inscrito em outra equipe desta trilha.",
                    'erros' => $erros,
                ];
            }
        }

        $equipeId = $this->gravar($preparo['trilha']['id'], $nomeEquipe, $participantesValidos);

        return ['sucesso' => true, 'equipe_id' => $equipeId, 'email_contato' => $emailContato];
    }

    private function gravar($trilhaId, $nomeEquipe, array $participantesValidos)
    {
        $equipeId = $this->equipes->criar($trilhaId, $nomeEquipe, '', '');
        $primeiro = true;

        foreach ($participantesValidos as $dados) {
            $vinculoProfissao = trim(
                (isset($dados['local_trabalho']) ? $dados['local_trabalho'] : '')
                . (!empty($dados['local_trabalho']) && !empty($dados['profissao']) ? ' - ' : '')
                . (isset($dados['profissao']) ? $dados['profissao'] : '')
            );

            $participanteExistente = $this->participantes->buscarPorCpf($dados['cpf']);

            if ($participanteExistente !== null) {
                $participanteId = $participanteExistente['id'];
            } else {
                $participanteId = $this->participantes->criar(
                    $dados['nome'],
                    $dados['cpf'],
                    isset($dados['email']) ? $dados['email'] : '',
                    isset($dados['telefone']) ? $dados['telefone'] : '',
                    $vinculoProfissao
                );
            }

            $this->equipes->vincularParticipante($equipeId, $participanteId, $primeiro ? 'lider' : 'integrante');
            $primeiro = false;
        }

        return $equipeId;
    }

    private function validarCampo(array $campo, $valorPost)
    {
        $tipo = $campo['tipo'];
        $obrigatorio = (bool) $campo['obrigatorio'];
        $valor = is_string($valorPost) ? trim($valorPost) : $valorPost;

        if ($obrigatorio && ($valor === null || $valor === '')) {
            return ['valido' => false, 'mensagem' => 'Campo obrigatório.'];
        }

        if ($valor === null || $valor === '') {
            return ['valido' => true, 'valor' => ''];
        }

        switch ($tipo) {
            case 'texto':
                return ['valido' => true, 'valor' => $valor];

            case 'cpf':
                if (!CpfValidador::valido($valor)) {
                    return ['valido' => false, 'mensagem' => 'CPF inválido.'];
                }

                return ['valido' => true, 'valor' => CpfValidador::apenasDigitos($valor)];

            case 'email':
                if (filter_var($valor, FILTER_VALIDATE_EMAIL) === false) {
                    return ['valido' => false, 'mensagem' => 'E-mail inválido.'];
                }

                return ['valido' => true, 'valor' => $valor];

            case 'telefone':
                $digitos = preg_replace('/\D/', '', $valor);

                if (strlen($digitos) < 10 || strlen($digitos) > 11) {
                    return ['valido' => false, 'mensagem' => 'Telefone inválido.'];
                }

                return ['valido' => true, 'valor' => $digitos];

            default:
                return ['valido' => false, 'mensagem' => 'Tipo de campo desconhecido.'];
        }
    }
}
