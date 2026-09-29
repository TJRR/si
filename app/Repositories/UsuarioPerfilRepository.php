<?php

namespace App\Repositories;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Core\Auditoria;
use App\Core\Database;

/**
 * Fase 48 (correcao pos-teste de fumaca): documento/cargo/categoria
 * profissional/orgao de origem/minicurriculo da PESSOA - 1:1 com usuarios,
 * reaproveitavel por qualquer contexto (hoje so' o cadastro de Facilitador
 * usa isso, ver AtividadeAdminController::vincularFacilitador()). Foto
 * continua em usuarios.foto_path (UsuarioRepository::atualizarFoto()),
 * nunca duplicada aqui.
 *
 * Fase 55: entram o telefone (com a marca de WhatsApp), as redes sociais e
 * as seis marcas de visibilidade (migration 182). Com as Conexoes, o que
 * esta' aqui pode ser mostrado a outro participante, entao cada campo so'
 * sai se a propria pessoa tiver liberado - a decisao de o que exibir e' de
 * PerfilVisibilidadeService, nunca deste repositorio.
 */
class UsuarioPerfilRepository
{
    /**
     * Fase 48 (correcao pos-teste de fumaca): lista fechada usada tanto na
     * tela "Meu Perfil" (o proprio usuario edita) quanto na tela de
     * vincular Facilitador (Admin edita quando ainda nao estiver
     * preenchido) - so' um lugar de verdade para essa lista.
     */
    const CATEGORIAS_PROFISSIONAIS = [
        'Magistrado', 'Promotor', 'Defensor', 'Advogado', 'Servidor do Judiciário',
        'Servidor Público', 'Terceirizado', 'Empresário', 'Estagiário', 'Estudante',
    ];

    const TIPOS_DOCUMENTO = ['CPF', 'RG', 'RNE', 'Passaporte'];

    /**
     * Fase 55: as mesmas cinco redes que o rodape do site ja desenha, com
     * os simbolos de app/Views/_icone_rede_social.php. A lista e' COPIADA de
     * ContatoConcursoRepository de proposito, nao importada: aquele
     * repositorio e' do Concurso, e o precedente do projeto (Fase 54,
     * EstandeRepresentanteConviteService) e' copiar em vez de acoplar
     * codigo de Evento a codigo do Concurso em uso. A unificacao das
     * duplicacoes propositais e' a pendencia 25.
     */
    const REDES_SUPORTADAS = ['instagram', 'facebook', 'youtube', 'linkedin', 'x'];

    const REDES_ROTULOS = [
        'instagram' => 'Instagram',
        'facebook' => 'Facebook',
        'youtube' => 'YouTube',
        'linkedin' => 'LinkedIn',
        'x' => 'X',
    ];

    /**
     * Fase 55 (correcao pedida pelo dono): a pessoa nao precisa saber o
     * formato do endereco. Ela informa o proprio nome de usuario, e o
     * sistema monta o endereco; quem preferir colar o endereco inteiro
     * tambem e' aceito, com ou sem "https://".
     *
     * - dominios: os unicos aceitos quando a pessoa cola um endereco. E' o
     *   que impede o campo "Instagram" de virar vitrine para um endereco
     *   qualquer mostrado a outros participantes.
     * - raiz: usada quando a pessoa digita um caminho conhecido da rede
     *   (por exemplo "company/tjrr" no LinkedIn).
     * - prefixo: usado quando ela digita so' o nome de usuario.
     * - caminhos: comecos que indicam caminho da rede, nao nome de usuario.
     */
    const REDES_ENDERECO = [
        'instagram' => [
            'dominios' => ['instagram.com'],
            'raiz' => 'https://www.instagram.com/',
            'prefixo' => 'https://www.instagram.com/',
            'caminhos' => [],
        ],
        'facebook' => [
            'dominios' => ['facebook.com', 'fb.com'],
            'raiz' => 'https://www.facebook.com/',
            'prefixo' => 'https://www.facebook.com/',
            'caminhos' => ['profile.php', 'people/', 'pages/'],
        ],
        'youtube' => [
            'dominios' => ['youtube.com', 'youtu.be'],
            'raiz' => 'https://www.youtube.com/',
            'prefixo' => 'https://www.youtube.com/@',
            'caminhos' => ['@', 'channel/', 'c/', 'user/'],
        ],
        'linkedin' => [
            'dominios' => ['linkedin.com'],
            'raiz' => 'https://www.linkedin.com/',
            'prefixo' => 'https://www.linkedin.com/in/',
            'caminhos' => ['in/', 'company/', 'school/', 'pub/'],
        ],
        'x' => [
            'dominios' => ['x.com', 'twitter.com'],
            'raiz' => 'https://x.com/',
            'prefixo' => 'https://x.com/',
            'caminhos' => [],
        ],
    ];

    /**
     * Fase 55: transforma o que a pessoa digitou no endereco completo da
     * rede. Aceita nome de usuario ("fulano", "@fulano"), caminho da rede
     * ("company/tjrr") e o endereco inteiro, com ou sem "https://".
     *
     * Devolve o endereco pronto, '' quando o campo ficou em branco, ou null
     * quando nao da' para interpretar com seguranca (endereco de outro
     * site, ou texto com espaco e caractere estranho). Quem chama decide a
     * mensagem.
     */
    public static function normalizarEnderecoRede($rede, $valor)
    {
        $valor = trim((string) $valor);

        if ($valor === '') {
            return '';
        }

        $definicoes = self::REDES_ENDERECO;

        if (!isset($definicoes[$rede])) {
            return null;
        }

        $definicao = $definicoes[$rede];
        $temEsquema = preg_match('#^https?://#i', $valor) === 1;
        $inicio = $temEsquema ? preg_replace('#^https?://#i', '', $valor) : $valor;
        $host = strtolower(explode('/', $inicio, 2)[0]);
        $hostSemWww = strpos($host, 'www.') === 0 ? substr($host, 4) : $host;
        $ehEnderecoDaRede = in_array($hostSemWww, $definicao['dominios'], true);

        // Endereco colado: so' passa se o dominio for mesmo o da rede.
        if ($temEsquema || $ehEnderecoDaRede) {
            if (!$ehEnderecoDaRede) {
                return null;
            }

            $endereco = $temEsquema ? $valor : 'https://' . ltrim($valor, '/');

            return linkHttpValido($endereco) ? $endereco : null;
        }

        // Nome de usuario ou caminho da rede.
        $identificador = ltrim($valor, '@/');

        if ($identificador === '' || preg_match('#^[A-Za-z0-9._%\-/]+$#', $identificador) !== 1) {
            return null;
        }

        foreach ($definicao['caminhos'] as $caminho) {
            $caminhoLimpo = ltrim($caminho, '@');

            if ($caminhoLimpo !== '' && stripos($identificador, $caminhoLimpo) === 0) {
                return $definicao['raiz'] . $identificador;
            }
        }

        return $definicao['prefixo'] . $identificador;
    }

    /**
     * Fase 55: marcas de visibilidade, na ordem em que aparecem na tela
     * "Meu Perfil" do aplicativo. Nome e e-mail nao entram aqui: aparecem
     * sempre para quem se conectou (decisao do dono).
     */
    const MARCAS_VISIBILIDADE = [
        'mostrar_foto',
        'mostrar_cargo',
        'mostrar_orgao_origem',
        'mostrar_minicurriculo',
        'mostrar_telefone',
        'mostrar_redes_sociais',
    ];

    public function buscarPorUsuarioId($usuarioId)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('SELECT * FROM usuarios_perfil WHERE usuario_id = :usuario_id LIMIT 1');
        $stmt->execute(['usuario_id' => $usuarioId]);

        $perfil = $stmt->fetch();

        return $perfil !== false ? $perfil : null;
    }

    /**
     * Upsert: quem chama sempre manda o conjunto completo dos campos da
     * Fase 48 - um segundo cadastro (outra atividade, mesma pessoa)
     * atualiza o mesmo registro, nunca cria um segundo.
     *
     * Fase 55: os campos novos (telefone, marca de WhatsApp, redes sociais
     * e as seis marcas de visibilidade) sao OPCIONAIS na entrada. Chave
     * ausente preserva o que ja' estava gravado, em vez de apagar - sem
     * isso, os chamadores antigos, que mandam so' os seis campos da Fase
     * 48, limpariam telefone, redes e visibilidade a cada gravacao.
     */
    public function salvar($usuarioId, array $dados)
    {
        $antes = $this->buscarPorUsuarioId($usuarioId);
        $campos = [
            'usuario_id' => $usuarioId,
            'documento' => $dados['documento'] !== '' ? $dados['documento'] : null,
            'tipo_documento' => $dados['tipo_documento'],
            'cargo' => $dados['cargo'] !== '' ? $dados['cargo'] : null,
            'categoria_profissional' => $dados['categoria_profissional'] !== '' ? $dados['categoria_profissional'] : null,
            'orgao_origem' => $dados['orgao_origem'] !== '' ? $dados['orgao_origem'] : null,
            'minicurriculo' => $dados['minicurriculo'] !== '' ? $dados['minicurriculo'] : null,
            'telefone' => $this->telefoneParaGravar($dados, $antes),
            'telefone_whatsapp' => $this->marcaParaGravar($dados, $antes, 'telefone_whatsapp'),
            'redes_sociais' => $this->redesParaGravar($dados, $antes),
        ];

        foreach (self::MARCAS_VISIBILIDADE as $marca) {
            $campos[$marca] = $this->marcaParaGravar($dados, $antes, $marca);
        }

        $pdo = Database::conexao();

        if ($antes !== null) {
            $stmt = $pdo->prepare(
                'UPDATE usuarios_perfil
                 SET documento = :documento, tipo_documento = :tipo_documento, cargo = :cargo,
                     categoria_profissional = :categoria_profissional, orgao_origem = :orgao_origem,
                     minicurriculo = :minicurriculo, telefone = :telefone,
                     telefone_whatsapp = :telefone_whatsapp, redes_sociais = :redes_sociais,
                     mostrar_foto = :mostrar_foto, mostrar_cargo = :mostrar_cargo,
                     mostrar_orgao_origem = :mostrar_orgao_origem,
                     mostrar_minicurriculo = :mostrar_minicurriculo,
                     mostrar_telefone = :mostrar_telefone,
                     mostrar_redes_sociais = :mostrar_redes_sociais
                 WHERE usuario_id = :usuario_id'
            );
            $stmt->execute($campos);
        } else {
            $stmt = $pdo->prepare(
                'INSERT INTO usuarios_perfil
                    (usuario_id, documento, tipo_documento, cargo, categoria_profissional, orgao_origem,
                     minicurriculo, telefone, telefone_whatsapp, redes_sociais, mostrar_foto, mostrar_cargo,
                     mostrar_orgao_origem, mostrar_minicurriculo, mostrar_telefone, mostrar_redes_sociais)
                 VALUES
                    (:usuario_id, :documento, :tipo_documento, :cargo, :categoria_profissional, :orgao_origem,
                     :minicurriculo, :telefone, :telefone_whatsapp, :redes_sociais, :mostrar_foto, :mostrar_cargo,
                     :mostrar_orgao_origem, :mostrar_minicurriculo, :mostrar_telefone, :mostrar_redes_sociais)'
            );
            $stmt->execute($campos);
        }

        // O registro de auditoria dos dados da pessoa segue igual ao da Fase
        // 48: telefone e redes sociais NAO entram, para a auditoria nao virar
        // uma segunda copia do contato da pessoa. A mudanca das marcas de
        // visibilidade e' registro de consentimento e tem evento proprio.
        $camposAuditoria = [
            'documento' => $campos['documento'],
            'tipo_documento' => $campos['tipo_documento'],
            'cargo' => $campos['cargo'],
            'categoria_profissional' => $campos['categoria_profissional'],
            'orgao_origem' => $campos['orgao_origem'],
            'minicurriculo' => $campos['minicurriculo'],
        ];

        Auditoria::registrar('salvar', 'usuarios_perfil', $usuarioId, $antes, $camposAuditoria);
        $this->auditarVisibilidade($usuarioId, $antes, $campos);
    }

    /**
     * Fase 49B: upsert PARCIAL - so' sobrescreve os campos passados em
     * $camposParciais, preservando os demais que ja existirem (diferente
     * de salvar(), que sempre espera o conjunto completo). Usado por todo
     * ponto do sistema que captura só um pedaço do perfil da pessoa
     * (documento na inscrição do evento, CPF na submissão de Trabalho),
     * para nunca apagar dado que outro fluxo já tenha preenchido -
     * exatamente o problema que motivou esta correção (ver
     * feedback_investigacao_duplicacao_precisa_ser_no_codigo, memória do
     * projeto).
     *
     * Fase 55: os campos novos nao precisam entrar na base porque salvar()
     * ja' preserva o que nao vier na entrada. Continua valendo a mesma
     * regra: quem chama manda so' o pedaco que conhece.
     */
    public function atualizarParcial($usuarioId, array $camposParciais)
    {
        $atual = $this->buscarPorUsuarioId($usuarioId);
        $base = [
            'documento' => $atual !== null ? (string) $atual['documento'] : '',
            'tipo_documento' => $atual !== null && $atual['tipo_documento'] !== null ? $atual['tipo_documento'] : 'CPF',
            'cargo' => $atual !== null ? (string) $atual['cargo'] : '',
            'categoria_profissional' => $atual !== null ? (string) $atual['categoria_profissional'] : '',
            'orgao_origem' => $atual !== null ? (string) $atual['orgao_origem'] : '',
            'minicurriculo' => $atual !== null ? (string) $atual['minicurriculo'] : '',
        ];

        $this->salvar($usuarioId, array_merge($base, $camposParciais));
    }

    /**
     * Fase 55: mapa rede -> endereco ja' decodificado, so' com as redes
     * suportadas e sem endereco vazio. Linha antiga (anterior a migration
     * 182) ou JSON invalido devolve lista vazia, nunca erro.
     */
    public function redesSociais(array $perfil = null)
    {
        if ($perfil === null || empty($perfil['redes_sociais'])) {
            return [];
        }

        $mapa = json_decode((string) $perfil['redes_sociais'], true);

        if (!is_array($mapa)) {
            return [];
        }

        $redes = [];

        foreach (self::REDES_SUPORTADAS as $rede) {
            if (!empty($mapa[$rede])) {
                $redes[$rede] = (string) $mapa[$rede];
            }
        }

        return $redes;
    }

    private function telefoneParaGravar(array $dados, array $antes = null)
    {
        if (!array_key_exists('telefone', $dados)) {
            return $antes !== null ? $antes['telefone'] : null;
        }

        $telefone = trim((string) $dados['telefone']);

        return $telefone !== '' ? $telefone : null;
    }

    private function redesParaGravar(array $dados, array $antes = null)
    {
        if (!array_key_exists('redes_sociais', $dados)) {
            return $antes !== null ? $antes['redes_sociais'] : null;
        }

        $entrada = $dados['redes_sociais'];

        if (!is_array($entrada)) {
            $entrada = json_decode((string) $entrada, true);
        }

        if (!is_array($entrada)) {
            return null;
        }

        $mapa = [];

        foreach (self::REDES_SUPORTADAS as $rede) {
            if (isset($entrada[$rede]) && trim((string) $entrada[$rede]) !== '') {
                $mapa[$rede] = trim((string) $entrada[$rede]);
            }
        }

        return !empty($mapa) ? json_encode($mapa) : null;
    }

    private function marcaParaGravar(array $dados, array $antes = null, $chave = null)
    {
        if (!array_key_exists($chave, $dados)) {
            return $antes !== null ? (int) $antes[$chave] : 0;
        }

        return !empty($dados[$chave]) ? 1 : 0;
    }

    /**
     * Fase 55: as marcas de visibilidade sao controle de consentimento sobre
     * dado pessoal, entao ligar ou desligar qualquer uma delas deixa rastro
     * proprio. Registra SO' as marcas, nunca o telefone, o e-mail ou os
     * enderecos de rede social - auditoria e' metadado de decisao, nao uma
     * segunda copia do dado.
     */
    private function auditarVisibilidade($usuarioId, array $antes = null, array $campos = [])
    {
        $marcasAntes = [];
        $marcasDepois = [];
        $mudou = false;

        foreach (self::MARCAS_VISIBILIDADE as $marca) {
            $valorAntes = $antes !== null && isset($antes[$marca]) ? (int) $antes[$marca] : 0;
            $valorDepois = (int) $campos[$marca];

            $marcasAntes[$marca] = $valorAntes;
            $marcasDepois[$marca] = $valorDepois;

            if ($valorAntes !== $valorDepois) {
                $mudou = true;
            }
        }

        if (!$mudou) {
            return;
        }

        Auditoria::registrar(
            'alterar_visibilidade_perfil',
            'usuarios_perfil',
            $usuarioId,
            $antes !== null ? $marcasAntes : null,
            $marcasDepois
        );
    }
}
