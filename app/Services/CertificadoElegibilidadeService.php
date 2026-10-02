<?php

namespace App\Services;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Repositories\CredenciamentoLocalRepository;
use App\Repositories\EventoAtividadeFacilitadorRepository;
use App\Repositories\EventoCheckinResumoRepository;
use App\Repositories\EventoInscricaoRepository;
use App\Repositories\TrabalhoApresentacaoRepository;
use App\Repositories\TrabalhoAvaliadorRepository;
use App\Repositories\TrabalhoDesignacaoRepository;
use App\Repositories\UsuarioPerfilRepository;
use App\Repositories\UsuarioRepository;

/**
 * Fase 59: quem tem direito a qual certificado, e com que carga horaria.
 * So' APURA: nao gera documento nem grava nada (isso e'
 * CertificadoEmissaoService).
 *
 * O resultado de cada pessoa e' um "dossie": as condicoes que a
 * qualificaram, os numeros apurados e a lista de itens a que ela tem
 * direito, cada item ja' com a chave de unicidade da migration 199. A tela
 * do participante mostra essa lista, e a emissao percorre a mesma lista -
 * entao nunca ha' um caminho que ofereca o que o outro recusaria.
 */
class CertificadoElegibilidadeService
{
    const CONDICAO_PARTICIPANTE = 'participante';

    const CONDICAO_FACILITADOR = 'facilitador';

    const CONDICAO_AVALIADOR = 'avaliador';

    const TIPO_EVENTO = 'evento';

    const TIPO_ATIVIDADE = 'atividade';

    const TIPO_APRESENTACAO = 'apresentacao';

    /**
     * Rotulos das condicoes, na ordem em que aparecem no documento.
     */
    const ROTULO_CONDICAO = [
        self::CONDICAO_PARTICIPANTE => 'participante',
        self::CONDICAO_FACILITADOR => 'facilitador',
        self::CONDICAO_AVALIADOR => 'avaliador',
    ];

    private $presencas;
    private $facilitacoes;
    private $credenciamentos;
    private $apresentacoes;
    private $designacoes;

    public function __construct()
    {
        $this->presencas = new EventoCheckinResumoRepository();
        $this->facilitacoes = new EventoAtividadeFacilitadorRepository();
        $this->credenciamentos = new CredenciamentoLocalRepository();
        $this->apresentacoes = new TrabalhoApresentacaoRepository();
        $this->designacoes = new TrabalhoDesignacaoRepository();
    }

    /**
     * Soma, em minutos, a UNIAO dos intervalos recebidos: duas atividades no
     * mesmo horario contam uma vez. Metodo puro, sem banco, no espirito de
     * EventoCheckinRepository::presencaEfetiva().
     *
     * Existe porque agenda de evento se sobrepoe: uma sessao solene e uma
     * palestra no mesmo horario, uma oficina e uma atividade de campo na
     * mesma tarde. Somando duracao a duracao, quem leu o codigo de todas
     * sairia com quase o dobro das horas que o evento teve.
     *
     * Intervalo com fim menor ou igual ao inicio (cadastro errado) contribui
     * zero, nunca valor negativo.
     */
    public static function minutosDaUniao(array $intervalos)
    {
        $janelas = [];

        foreach ($intervalos as $intervalo) {
            $inicio = strtotime((string) $intervalo['data_inicio']);
            $fim = strtotime((string) $intervalo['data_fim']);

            if ($inicio === false || $fim === false || $fim <= $inicio) {
                continue;
            }

            $janelas[] = [$inicio, $fim];
        }

        if ($janelas === []) {
            return 0;
        }

        usort($janelas, function (array $a, array $b) {
            return $a[0] <=> $b[0];
        });

        $segundos = 0;
        $inicioCorrente = $janelas[0][0];
        $fimCorrente = $janelas[0][1];

        foreach (array_slice($janelas, 1) as $janela) {
            if ($janela[0] <= $fimCorrente) {
                $fimCorrente = max($fimCorrente, $janela[1]);
                continue;
            }

            $segundos += $fimCorrente - $inicioCorrente;
            $inicioCorrente = $janela[0];
            $fimCorrente = $janela[1];
        }

        $segundos += $fimCorrente - $inicioCorrente;

        return (int) floor($segundos / 60);
    }

    /**
     * Quantos DIAS diferentes os intervalos alcancam. O dia que conta e' o da
     * ATIVIDADE, nao o do registro da presenca, pelo mesmo motivo escrito em
     * EventoCheckinResumoRepository: a leitura abre antes do inicio e pode
     * cair no dia anterior.
     */
    public static function diasDistintos(array $intervalos)
    {
        $dias = [];

        foreach ($intervalos as $intervalo) {
            $dia = substr((string) $intervalo['data_inicio'], 0, 10);

            if ($dia !== '' && !in_array($dia, $dias, true)) {
                $dias[] = $dia;
            }
        }

        return count($dias);
    }

    /**
     * Carga horaria por escrito, sempre para baixo: 150 minutos viram "2h30"
     * e 59 minutos viram "59min". Nunca arredonda para cima, porque o numero
     * vai para um documento que declara tempo de participacao.
     */
    public static function formatarCargaHoraria($minutos)
    {
        $minutos = (int) $minutos;

        if ($minutos <= 0) {
            return '0h';
        }

        $horas = intdiv($minutos, 60);
        $resto = $minutos % 60;

        if ($horas === 0) {
            return $resto . 'min';
        }

        return $resto === 0 ? $horas . 'h' : $horas . 'h' . str_pad((string) $resto, 2, '0', STR_PAD_LEFT);
    }

    /**
     * Metade da trava que vale PARA TODOS, inclusive para o Administrador: o
     * ultimo dia conta inteiro, como nas conexoes e nas visitas a estandes.
     * Emitir antes do fim congelaria carga horaria incompleta num documento
     * que, por decisao do dono, e' guardado e nunca mais muda.
     */
    public function eventoTerminou(array $evento)
    {
        if (empty($evento['data_fim'])) {
            return false;
        }

        return strtotime(substr((string) $evento['data_fim'], 0, 10) . ' 23:59:59') < time();
    }

    /**
     * A outra metade da trava, que vale SO' PARA O PARTICIPANTE: o modulo
     * ligado e a liberacao do Administrador ja' alcancada. Assim a
     * organizacao pode ter os documentos prontos para a cerimonia antes de
     * abrir a emissao a quem participou.
     */
    public function emissaoAbertaAoParticipante(array $evento, array $config)
    {
        if ((int) $config['ativo'] !== 1 || !$this->eventoTerminou($evento)) {
            return false;
        }

        return $config['emissao_liberada_em'] !== null
            && strtotime((string) $config['emissao_liberada_em']) <= time();
    }

    /**
     * Quais exigencias da regua estao em vigor, na forma [chave => numero].
     * Lista vazia significa que todo inscrito e' elegivel, e a tela diz isso
     * por escrito em vez de mostrar numero inventado.
     */
    public static function exigenciasEmVigor(array $config)
    {
        $exigencias = [];

        foreach (['min_atividades', 'min_dias', 'min_horas'] as $campo) {
            if ($config[$campo] !== null && (int) $config[$campo] > 0) {
                $exigencias[$campo] = (int) $config[$campo];
            }
        }

        if ((int) $config['exige_credenciamento'] === 1) {
            $exigencias['exige_credenciamento'] = 1;
        }

        return $exigencias;
    }

    /**
     * O dossie de UMA pessoa, para a tela dela e para a emissao individual.
     *
     * $inscricao pode ser nula: facilitador e avaliador participam do evento
     * sem inscricao, e nesse caso nao ha' presenca nenhuma a somar (a tabela
     * evento_checkins e' chaveada por evento_inscricao_id).
     */
    public function dossieDoUsuario(array $evento, array $config, $usuarioId, array $inscricao = null)
    {
        $eventoId = (int) $evento['id'];
        $usuarioId = (int) $usuarioId;

        $presencas = $inscricao !== null
            ? $this->presencas->intervalosDaInscricao($eventoId, $inscricao['id'])
            : [];
        $conducoes = $this->facilitacoes->intervalosDoUsuarioNoEvento($usuarioId, $eventoId);
        $credenciado = $inscricao !== null
            && $this->credenciamentos->buscarDaInscricao($inscricao['id']) !== null;
        $notados = (new TrabalhoAvaliadorRepository())->estaAtivo($eventoId, $usuarioId)
            ? $this->designacoes->contarNotadosNoEventoPorUsuario($eventoId, $usuarioId)
            : 0;
        $autorias = $this->apresentacoes->listarAutoriasApresentadasDoUsuario($eventoId, $usuarioId);

        $pessoa = $this->dadosDaPessoa($usuarioId, $inscricao);

        return $this->montarDossie(
            $evento,
            $config,
            $pessoa,
            $inscricao,
            $presencas,
            $conducoes,
            $credenciado,
            $notados,
            $autorias
        );
    }

    /**
     * Os dossies de TODAS as pessoas do evento, para a tela administrativa e
     * para a emissao em lote. Carrega cada agregado uma unica vez, em vez de
     * uma consulta por pessoa.
     *
     * Devolve [chave_da_pessoa => dossie], em que a chave e' "u<usuario_id>"
     * para quem tem conta e "a<trabalho_autor_id>" para o coautor sem conta,
     * que existe de verdade (trabalho_autores.usuario_id so' e' preenchido
     * para o autor principal) e so' alcanca o documento dele por aqui.
     */
    public function dossiesDoEvento(array $evento, array $config)
    {
        $eventoId = (int) $evento['id'];
        $presencasPorInscricao = $this->presencas->intervalosDoEvento($eventoId);
        $conducoesPorUsuario = $this->facilitacoes->intervalosDoEvento($eventoId);
        $notadosPorUsuario = $this->designacoes->contarNotadosNoEvento($eventoId);
        $autoresApresentados = $this->apresentacoes->listarAutoresApresentadosDoEvento($eventoId);
        $credenciadas = [];

        foreach ($this->credenciamentos->listarPorEvento($eventoId) as $linha) {
            $credenciadas[(int) $linha['evento_inscricao_id']] = true;
        }

        // listarPorEvento() ja' devolve so' designacao ativa (removido_em IS
        // NULL no proprio comando), entao nao ha' filtro a repetir aqui.
        $avaliadoresAtivos = [];

        foreach ((new TrabalhoAvaliadorRepository())->listarPorEvento($eventoId) as $avaliador) {
            $avaliadoresAtivos[(int) $avaliador['usuario_id']] = true;
        }

        $autoriasPorUsuario = [];
        $autoriasSemConta = [];

        foreach ($autoresApresentados as $autor) {
            if ($autor['usuario_id'] !== null) {
                $autoriasPorUsuario[(int) $autor['usuario_id']][] = $autor;
                continue;
            }

            $autoriasSemConta[] = $autor;
        }

        $dossies = [];

        // Quem tem inscricao no evento e' a base: a regua da configuracao
        // vale para essas pessoas.
        foreach ((new EventoInscricaoRepository())->listarPorEvento($eventoId) as $inscricao) {
            $usuarioId = (int) $inscricao['usuario_id'];
            $dossies['u' . $usuarioId] = $this->montarDossie(
                $evento,
                $config,
                [
                    'usuario_id' => $usuarioId,
                    'nome' => $inscricao['usuario_nome'],
                    'email' => $inscricao['usuario_email'],
                    'documento' => $inscricao['perfil_documento'],
                    'tipo_documento' => $inscricao['perfil_tipo_documento'],
                ],
                $inscricao,
                isset($presencasPorInscricao[(int) $inscricao['id']]) ? $presencasPorInscricao[(int) $inscricao['id']] : [],
                isset($conducoesPorUsuario[$usuarioId]) ? $conducoesPorUsuario[$usuarioId] : [],
                isset($credenciadas[(int) $inscricao['id']]),
                isset($avaliadoresAtivos[$usuarioId]) && isset($notadosPorUsuario[$usuarioId])
                    ? $notadosPorUsuario[$usuarioId]
                    : 0,
                isset($autoriasPorUsuario[$usuarioId]) ? $autoriasPorUsuario[$usuarioId] : []
            );
        }

        // Quem conduziu atividade, avaliou trabalho ou e' autor de trabalho
        // apresentado SEM ter inscricao no evento: entra pela designacao, que
        // e' a propria prova de participacao, e nao passa pela regua.
        $semInscricao = array_keys($conducoesPorUsuario);

        foreach (array_keys($notadosPorUsuario) as $usuarioId) {
            if (isset($avaliadoresAtivos[$usuarioId])) {
                $semInscricao[] = $usuarioId;
            }
        }

        $semInscricao = array_merge($semInscricao, array_keys($autoriasPorUsuario));

        foreach (array_unique($semInscricao) as $usuarioId) {
            $usuarioId = (int) $usuarioId;

            if (isset($dossies['u' . $usuarioId])) {
                continue;
            }

            $dossies['u' . $usuarioId] = $this->montarDossie(
                $evento,
                $config,
                $this->dadosDaPessoa($usuarioId, null),
                null,
                [],
                isset($conducoesPorUsuario[$usuarioId]) ? $conducoesPorUsuario[$usuarioId] : [],
                false,
                isset($avaliadoresAtivos[$usuarioId]) && isset($notadosPorUsuario[$usuarioId])
                    ? $notadosPorUsuario[$usuarioId]
                    : 0,
                isset($autoriasPorUsuario[$usuarioId]) ? $autoriasPorUsuario[$usuarioId] : []
            );
        }

        foreach ($autoriasSemConta as $autor) {
            $dossies['a' . (int) $autor['trabalho_autor_id']] = $this->montarDossie(
                $evento,
                $config,
                [
                    'usuario_id' => null,
                    'nome' => $autor['nome'],
                    'email' => null,
                    'documento' => $autor['cpf'],
                    'tipo_documento' => 'CPF',
                ],
                null,
                [],
                [],
                false,
                0,
                [$autor]
            );
        }

        return $dossies;
    }

    /**
     * Nome e documento de quem nao vem de uma inscricao (facilitador,
     * avaliador). usuarios_perfil e' a fonte unica dos dados de pessoa desde
     * a Fase 49B.
     */
    private function dadosDaPessoa($usuarioId, array $inscricao = null)
    {
        if ($inscricao !== null) {
            return [
                'usuario_id' => (int) $usuarioId,
                'nome' => $inscricao['usuario_nome'],
                'email' => $inscricao['usuario_email'],
                'documento' => $inscricao['perfil_documento'],
                'tipo_documento' => $inscricao['perfil_tipo_documento'],
            ];
        }

        $usuario = (new UsuarioRepository())->buscarPorId($usuarioId);
        $perfil = (new UsuarioPerfilRepository())->buscarPorUsuarioId($usuarioId);

        return [
            'usuario_id' => (int) $usuarioId,
            'nome' => $usuario !== null ? $usuario['nome'] : '',
            'email' => $usuario !== null ? $usuario['email'] : null,
            'documento' => $perfil !== null ? $perfil['documento'] : null,
            'tipo_documento' => $perfil !== null ? $perfil['tipo_documento'] : null,
        ];
    }

    /**
     * O coracao da apuracao. Monta as condicoes, os numeros e a lista de
     * itens a partir dos agregados que quem chama ja' carregou.
     */
    private function montarDossie(
        array $evento,
        array $config,
        array $pessoa,
        array $inscricao = null,
        array $presencas = [],
        array $conducoes = [],
        $credenciado = false,
        $notados = 0,
        array $autorias = []
    ) {
        $eventoId = (int) $evento['id'];
        $usuarioId = $pessoa['usuario_id'] !== null ? (int) $pessoa['usuario_id'] : null;

        // A atividade conduzida que a pessoa tambem assistiu aparece nas duas
        // listas. Para a carga horaria isso nao muda nada (a uniao de
        // intervalos iguais e' o mesmo intervalo), mas para a contagem de
        // atividades distintas e para os itens por atividade, sim.
        $porAtividade = [];

        foreach ($presencas as $linha) {
            $porAtividade[(int) $linha['atividade_id']] = $linha + ['condicao' => self::CONDICAO_PARTICIPANTE];
        }

        foreach ($conducoes as $linha) {
            $porAtividade[(int) $linha['atividade_id']] = $linha + ['condicao' => self::CONDICAO_FACILITADOR];
        }

        $minutosPresenca = self::minutosDaUniao($porAtividade);
        // A configuracao pede HORAS, que e' a unidade do documento; a conta
        // daqui e' em minutos, e e' este ponto que converte.
        $minutosAvaliador = $notados > 0 && $config['carga_horaria_avaliador_horas'] !== null
            ? (int) $config['carga_horaria_avaliador_horas'] * 60
            : 0;

        $numeros = [
            'atividades' => count($porAtividade),
            'dias' => self::diasDistintos($porAtividade),
            'minutos_presenca' => $minutosPresenca,
            'minutos_total' => $minutosPresenca + $minutosAvaliador,
            'credenciado' => (bool) $credenciado,
            'trabalhos_notados' => (int) $notados,
        ];

        $reguaCumprida = $inscricao !== null && $this->cumpreARegua($config, $numeros);
        $condicoes = [];

        if ($reguaCumprida) {
            $condicoes[] = self::CONDICAO_PARTICIPANTE;
        }

        if ($conducoes !== []) {
            $condicoes[] = self::CONDICAO_FACILITADOR;
        }

        if ($notados > 0) {
            $condicoes[] = self::CONDICAO_AVALIADOR;
        }

        $dossie = [
            'usuario_id' => $usuarioId,
            'inscricao_id' => $inscricao !== null ? (int) $inscricao['id'] : null,
            'nome' => $pessoa['nome'],
            'email' => $pessoa['email'],
            'documento' => $pessoa['documento'],
            'tipo_documento' => $pessoa['tipo_documento'],
            'condicoes' => $condicoes,
            'tem_inscricao' => $inscricao !== null,
            'regua_cumprida' => $reguaCumprida,
            'itens' => [],
        ] + $numeros;

        // 1. Certificado do evento: um por pessoa, com as condicoes que de
        // fato a qualificaram. Coautor sem conta nao entra, porque a chave de
        // unicidade do tipo evento e' o usuario.
        if ($condicoes !== [] && $usuarioId !== null) {
            $dossie['itens'][] = [
                'tipo' => self::TIPO_EVENTO,
                'chave' => self::TIPO_EVENTO . ':' . $usuarioId,
                'rotulo' => 'Participação no evento',
                'detalhe' => $evento['nome'],
                'atividade_id' => null,
                'trabalho_id' => null,
                'trabalho_autor_id' => null,
                'carga_horaria_minutos' => $numeros['minutos_total'],
                'fundo_url' => $config['fundo_url'],
                'fundo_cor' => $config['fundo_cor'],
            ];
        }

        // 2. Certificado por atividade: so' nas atividades com "Emite
        // certificado" marcada, que e' a marca gravada desde a Fase 46 e sem
        // uso ate' agora. A carga horaria e' a duracao da propria atividade.
        foreach ($porAtividade as $atividadeId => $linha) {
            if (empty($linha['emite_certificado']) || $usuarioId === null) {
                continue;
            }

            $fundoProprio = !empty($linha['certificado_fundo_url']) || !empty($linha['certificado_fundo_cor']);

            $dossie['itens'][] = [
                'tipo' => self::TIPO_ATIVIDADE,
                'chave' => self::TIPO_ATIVIDADE . ':' . (int) $atividadeId . ':' . $usuarioId,
                'rotulo' => $linha['nome'],
                'detalhe' => $linha['local'],
                'atividade_id' => (int) $atividadeId,
                'trabalho_id' => null,
                'trabalho_autor_id' => null,
                'data_inicio' => $linha['data_inicio'],
                'data_fim' => $linha['data_fim'],
                'carga_horaria_minutos' => self::minutosDaUniao([$linha]),
                // A atividade decide o PAR imagem e cor, nunca metade dele:
                // escolheu qualquer um dos dois, vale o dela inteiro (e um
                // fundo limpo na atividade e' uma escolha, nao uma falta);
                // nao escolheu nada, herda o par da configuracao do evento.
                'fundo_url' => $fundoProprio ? $linha['certificado_fundo_url'] : $config['fundo_atividade_url'],
                'fundo_cor' => $fundoProprio ? $linha['certificado_fundo_cor'] : $config['fundo_atividade_cor'],
                'condicao' => $linha['condicao'],
            ];
        }

        // 3. Certificado de apresentacao: um por autoria, para TODOS os
        // autores do trabalho apresentado, e nao apenas para quem ficou junto
        // ao cartaz. Sem carga horaria: o que ele atesta e' a apresentacao,
        // nao tempo de aula.
        foreach ($autorias as $autoria) {
            $dossie['itens'][] = [
                'tipo' => self::TIPO_APRESENTACAO,
                'chave' => self::TIPO_APRESENTACAO . ':' . (int) $autoria['trabalho_autor_id'],
                'rotulo' => 'Apresentação de trabalho',
                'detalhe' => $autoria['trabalho_titulo'],
                'atividade_id' => null,
                'trabalho_id' => (int) $autoria['trabalho_id'],
                'trabalho_autor_id' => (int) $autoria['trabalho_autor_id'],
                'carga_horaria_minutos' => null,
                'fundo_url' => $config['fundo_apresentacao_url'],
                'fundo_cor' => $config['fundo_apresentacao_cor'],
                'eixo_nome' => isset($autoria['eixo_nome']) ? $autoria['eixo_nome'] : null,
            ];
        }

        return $dossie;
    }

    /**
     * Decisao do dono: TODOS os criterios preenchidos precisam ser
     * cumpridos. Criterio em branco nao conta, e com os quatro em branco todo
     * inscrito cumpre.
     */
    private function cumpreARegua(array $config, array $numeros)
    {
        foreach (self::exigenciasEmVigor($config) as $chave => $exigido) {
            if ($chave === 'min_atividades' && $numeros['atividades'] < $exigido) {
                return false;
            }

            if ($chave === 'min_dias' && $numeros['dias'] < $exigido) {
                return false;
            }

            if ($chave === 'min_horas' && $numeros['minutos_presenca'] < $exigido * 60) {
                return false;
            }

            if ($chave === 'exige_credenciamento' && !$numeros['credenciado']) {
                return false;
            }
        }

        return true;
    }
}
