<?php

/**
 * Importa as inscricoes de equipe (1o formulario, ja rodado no Google Forms)
 * a partir dos CSVs em database/dados_importacao/, gerados das abas
 * EXT_homologado e INT_homologado do arquivo curado pelo NPI.
 *
 * Idempotente: pode ser rodado mais de uma vez (equipe ja importada e pulada).
 * Nao cria trilha/etapa automaticamente - precisam existir antes (Admin configura o motor).
 */

if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    exit('Acesso negado: este script so pode ser executado via linha de comando.');
}

define('SI_BOOT', true);

require __DIR__ . '/../vendor/autoload.php';

use App\Core\Database;
use App\Repositories\EquipeRepository;
use App\Repositories\EtapaRepository;
use App\Repositories\ImportacaoPendenciaRepository;
use App\Repositories\ParticipanteRepository;
use App\Repositories\SubmissaoRepository;
use App\Repositories\TrilhaRepository;
use App\Validation\CpfValidador;

const COL_CARIMBO = 0;
const COL_EMAIL_RESPONDENTE = 1;
const COL_NOME_EQUIPE = 2;

// [nome, cpf, telefone, email, local, profissao] - so P1 tem telefone/email.
const PARTICIPANTES = [
    ['nome' => 3, 'cpf' => 4, 'telefone' => 5, 'email' => 6, 'local' => 7, 'profissao' => 8, 'papel' => 'lider'],
    ['nome' => 9, 'cpf' => 10, 'telefone' => null, 'email' => null, 'local' => 11, 'profissao' => 12, 'papel' => 'integrante'],
    ['nome' => 13, 'cpf' => 14, 'telefone' => null, 'email' => null, 'local' => 15, 'profissao' => 16, 'papel' => 'integrante'],
    ['nome' => 17, 'cpf' => 18, 'telefone' => null, 'email' => null, 'local' => 19, 'profissao' => 20, 'papel' => 'integrante'],
    ['nome' => 21, 'cpf' => 22, 'telefone' => null, 'email' => null, 'local' => 23, 'profissao' => 24, 'papel' => 'integrante'],
];

$fontes = [
    [
        'aba' => 'EXT_homologado',
        'csv' => __DIR__ . '/dados_importacao/ext_homologado.csv',
        'trilha_nome' => 'Trilha Externa',
        'col_observacoes' => 25,
        'col_vinculo_institucional' => null,
    ],
    [
        'aba' => 'INT_homologado',
        'csv' => __DIR__ . '/dados_importacao/int_homologado.csv',
        'trilha_nome' => 'Trilha Interna',
        'col_observacoes' => null,
        'col_vinculo_institucional' => 26,
    ],
];

const ETAPA_CADASTRO_EQUIPE = 'Cadastro de Equipe';

function normalizarCpf($valorBruto)
{
    return preg_replace('/\D/', '', (string) $valorBruto);
}

function sugerirCorrecaoCpf($digitos)
{
    if ($digitos === '' || strlen($digitos) >= 11) {
        return null;
    }

    $candidato = str_pad($digitos, 11, '0', STR_PAD_LEFT);

    return CpfValidador::valido($candidato) ? $candidato : null;
}

function lerLinha($linha, $indice)
{
    return isset($linha[$indice]) ? trim($linha[$indice]) : '';
}

$trilhas = new TrilhaRepository();
$etapas = new EtapaRepository();
$equipes = new EquipeRepository();
$participantes = new ParticipanteRepository();
$submissoes = new SubmissaoRepository();
$pendencias = new ImportacaoPendenciaRepository();
$pdo = Database::conexao();

$totais = ['equipes_criadas' => 0, 'equipes_puladas' => 0, 'pendencias' => 0];

foreach ($fontes as $fonte) {
    echo "\n=== {$fonte['aba']} ===\n";

    $trilha = $trilhas->buscarPorNome($fonte['trilha_nome']);

    if ($trilha === null) {
        echo "ERRO: trilha '{$fonte['trilha_nome']}' nao encontrada. Cadastre-a antes (tela de Trilhas) e rode de novo.\n";
        continue;
    }

    $etapa = $etapas->buscarPorTrilhaENome($trilha['id'], ETAPA_CADASTRO_EQUIPE);

    if ($etapa === null) {
        echo "ERRO: etapa '" . ETAPA_CADASTRO_EQUIPE . "' nao encontrada na trilha '{$fonte['trilha_nome']}'. Cadastre-a antes (tela de Etapas) e rode de novo.\n";
        continue;
    }

    if (!file_exists($fonte['csv'])) {
        echo "ERRO: arquivo {$fonte['csv']} nao encontrado.\n";
        continue;
    }

    $handle = fopen($fonte['csv'], 'r');
    fgetcsv($handle); // descarta cabecalho
    $numeroLinha = 1;

    while (($linha = fgetcsv($handle)) !== false) {
        $numeroLinha++;

        $nomeEquipe = lerLinha($linha, COL_NOME_EQUIPE);

        if ($nomeEquipe === '') {
            continue; // linha em branco no fim do arquivo
        }

        $existente = $equipes->buscarPorTrilhaENome($trilha['id'], $nomeEquipe);

        if ($existente !== null) {
            echo "  [pulada] '{$nomeEquipe}' ja importada (linha {$numeroLinha}).\n";
            $totais['equipes_puladas']++;
            continue;
        }

        $observacoes = $fonte['col_observacoes'] !== null ? lerLinha($linha, $fonte['col_observacoes']) : '';
        $vinculoInstitucional = $fonte['col_vinculo_institucional'] !== null ? lerLinha($linha, $fonte['col_vinculo_institucional']) : '';

        $pdo->beginTransaction();

        try {
            $equipeId = $equipes->criar($trilha['id'], $nomeEquipe, $vinculoInstitucional, $observacoes);

            $participantesBrutos = [];
            $cpfsValidosUnicos = [];

            foreach (PARTICIPANTES as $indiceP => $colunas) {
                $nome = lerLinha($linha, $colunas['nome']);

                if ($nome === '') {
                    continue; // slot de participante vazio (equipe com menos de 5 integrantes)
                }

                $cpfBruto = lerLinha($linha, $colunas['cpf']);
                $cpfDigitos = normalizarCpf($cpfBruto);
                $cpfValido = CpfValidador::valido($cpfDigitos);
                $telefone = $colunas['telefone'] !== null ? preg_replace('/\D/', '', lerLinha($linha, $colunas['telefone'])) : '';
                $email = $colunas['email'] !== null ? lerLinha($linha, $colunas['email']) : '';
                $profissao = lerLinha($linha, $colunas['profissao']);
                $local = lerLinha($linha, $colunas['local']);
                $vinculoProfissao = trim($local . ($local !== '' && $profissao !== '' ? ' - ' : '') . $profissao);

                $participantesBrutos[] = [
                    'papel' => $colunas['papel'],
                    'nome' => $nome,
                    'cpf_bruto' => $cpfBruto,
                    'local_trabalho_estudo' => $local,
                    'profissao' => $profissao,
                ];

                $cpfParaGravar = $cpfValido ? $cpfDigitos : ($cpfDigitos !== '' ? $cpfDigitos : null);

                $participante = $cpfValido ? $participantes->buscarPorCpf($cpfDigitos) : null;

                if ($participante === null) {
                    $participanteId = $participantes->criar($nome, (string) $cpfParaGravar, $email, $telefone, $vinculoProfissao);
                } else {
                    $participanteId = $participante['id'];
                }

                if (!$cpfValido) {
                    $sugestao = sugerirCorrecaoCpf($cpfDigitos);
                    $pendencias->criar(
                        $trilha['id'],
                        $equipeId,
                        $participanteId,
                        'cpf_invalido',
                        $fonte['aba'],
                        $numeroLinha,
                        "CPF invalido para {$nome} (equipe '{$nomeEquipe}'): valor original '{$cpfBruto}'.",
                        ['valor_original' => $cpfBruto, 'sugestao' => $sugestao]
                    );
                    $totais['pendencias']++;
                }

                try {
                    $equipes->vincularParticipante($equipeId, $participanteId, $colunas['papel']);
                } catch (\PDOException $e) {
                    if ((int) $e->getCode() === 23000) {
                        $pendencias->criar(
                            $trilha['id'],
                            $equipeId,
                            $participanteId,
                            'cpf_duplicado_na_equipe',
                            $fonte['aba'],
                            $numeroLinha,
                            "CPF de {$nome} repetido dentro da propria equipe '{$nomeEquipe}'.",
                            ['valor_original' => $cpfBruto]
                        );
                        $totais['pendencias']++;
                    } else {
                        throw $e;
                    }
                }

                if ($cpfValido && !in_array($cpfDigitos, $cpfsValidosUnicos, true)) {
                    $cpfsValidosUnicos[] = $cpfDigitos;
                }
            }

            $submissaoId = $submissoes->criar($etapa['id'], null, [
                'importado_de' => 'google_forms',
                'aba' => $fonte['aba'],
                'linha_planilha' => $numeroLinha,
                'carimbo_data_hora' => lerLinha($linha, COL_CARIMBO),
                'email_respondente' => lerLinha($linha, COL_EMAIL_RESPONDENTE),
                'nome_equipe' => $nomeEquipe,
                'observacoes' => $observacoes,
                'vinculo_institucional' => $vinculoInstitucional,
                'participantes' => $participantesBrutos,
            ]);

            $submissoes->vincularEquipe($submissaoId, $equipeId);

            foreach ($cpfsValidosUnicos as $cpf) {
                try {
                    $submissoes->inserirCpf($submissaoId, $trilha['id'], $cpf);
                } catch (\PDOException $e) {
                    if ((int) $e->getCode() === 23000) {
                        $pendencias->criar(
                            $trilha['id'],
                            $equipeId,
                            null,
                            'cpf_duplicado_entre_equipes',
                            $fonte['aba'],
                            $numeroLinha,
                            "CPF {$cpf} (equipe '{$nomeEquipe}') ja usado em outra equipe desta trilha.",
                            ['cpf' => $cpf]
                        );
                        $totais['pendencias']++;
                    } else {
                        throw $e;
                    }
                }
            }

            $pdo->commit();
            echo "  [ok] '{$nomeEquipe}' importada (equipe #{$equipeId}, linha {$numeroLinha}).\n";
            $totais['equipes_criadas']++;
        } catch (\Exception $e) {
            $pdo->rollBack();
            echo "  [ERRO] linha {$numeroLinha} ('{$nomeEquipe}'): " . $e->getMessage() . "\n";

            $pendencias->criar(
                $trilha['id'],
                null,
                null,
                'erro_processamento',
                $fonte['aba'],
                $numeroLinha,
                "Falha ao processar a linha: " . $e->getMessage(),
                ['nome_equipe' => $nomeEquipe]
            );
            $totais['pendencias']++;
        }
    }

    fclose($handle);
}

echo "\n=== Resumo ===\n";
echo "Equipes criadas: {$totais['equipes_criadas']}\n";
echo "Equipes puladas (ja importadas): {$totais['equipes_puladas']}\n";
echo "Pendencias geradas: {$totais['pendencias']}\n";
