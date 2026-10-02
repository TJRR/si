<?php

namespace App\Repositories;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Core\Auditoria;
use App\Core\Database;

/**
 * Fase 56: configuracao de Divulgacao por evento, no molde de
 * ConexaoConfigRepository (Fase 55). Sao duas tabelas: a geral do modulo
 * (ligado, janela) e uma linha por rede que vale no evento (valores, prova
 * aceita e tetos). As linhas nascem no primeiro salvamento da tela
 * Divulgacao, Configuracoes; evento sem linha equivale a modulo desligado,
 * resolvido aqui e nunca semeado em migration.
 */
class DivulgacaoConfigRepository
{
    /**
     * Configuracao de um evento que ainda nao teve a tela salva: modulo
     * desligado e sem limite de data (Fase 58: data em branco nao limita
     * nada, e quem libera e' a chave 'ativo' - o sistema nunca assume as
     * datas do evento por conta propria).
     */
    const PADRAO = [
        'ativo' => 0,
        'data_inicio' => null,
        'data_fim' => null,
        'dias_retencao_imagens' => null,
    ];

    /**
     * Linha de rede que ainda nao foi salva: nao incluida no evento, nada
     * ativo, nada pontuando.
     */
    const PADRAO_REDE = [
        'incluida' => 0,
        'publicacao_ativa' => 0,
        'publicacao_pontos' => 0,
        'publicacao_prova' => 'ambos',
        'publicacao_teto_dia' => 0,
        'publicacao_teto_evento' => 0,
        'acompanhar_ativa' => 0,
        'acompanhar_pontos' => 0,
        'acompanhar_prova' => 'imagem',
        'acompanhar_canal_nome' => null,
        'acompanhar_canal_endereco' => null,
    ];

    /**
     * Fase 58 (dinamica de pontos v2, secao 6): as redes da Divulgacao
     * deixaram de ser as cinco do perfil (UsuarioPerfilRepository::
     * REDES_SUPORTADAS) e passaram a ter lista propria. Cada rede diz:
     *
     *   rotulo / rotulo_no  nome na tela e na frase ("no Instagram");
     *   publicacao          aceita comprovacao de publicacao;
     *   acompanhar          aceita comprovacao de que passou a seguir o canal;
     *   conta               que conta a publicacao exige: 'perfil' (a rede
     *                       cadastrada em "Meu Perfil"), 'whatsapp' (telefone
     *                       do perfil marcado como WhatsApp) ou null. Seguir
     *                       nunca exige conta (decisao da fase: Spotify e
     *                       Flickr nem sao campos de perfil);
     *   so_imagem           a prova de publicacao e' so' a imagem da tela;
     *   dominios            dominios aceitos no endereco da publicacao:
     *                       'perfil' le a lista do perfil
     *                       (UsuarioPerfilRepository::REDES_ENDERECO); lista
     *                       vazia nao aceita endereco.
     *
     * 'qualquer' e' a forma "qualquer rede" pedida pelo dono: basta a imagem,
     * sem rede nem conta conferidas, com auditoria possivel; a pessoa pode
     * informar o nome da rede. A rede especifica continua valendo ao lado.
     */
    const REDES = [
        'instagram' => ['rotulo' => 'Instagram', 'rotulo_no' => 'no Instagram', 'publicacao' => true, 'acompanhar' => true, 'conta' => 'perfil', 'so_imagem' => false, 'dominios' => 'perfil'],
        'facebook' => ['rotulo' => 'Facebook', 'rotulo_no' => 'no Facebook', 'publicacao' => true, 'acompanhar' => true, 'conta' => 'perfil', 'so_imagem' => false, 'dominios' => 'perfil'],
        'youtube' => ['rotulo' => 'YouTube', 'rotulo_no' => 'no YouTube', 'publicacao' => true, 'acompanhar' => true, 'conta' => 'perfil', 'so_imagem' => false, 'dominios' => 'perfil'],
        'linkedin' => ['rotulo' => 'LinkedIn', 'rotulo_no' => 'no LinkedIn', 'publicacao' => true, 'acompanhar' => true, 'conta' => 'perfil', 'so_imagem' => false, 'dominios' => 'perfil'],
        'x' => ['rotulo' => 'X', 'rotulo_no' => 'no X', 'publicacao' => true, 'acompanhar' => true, 'conta' => 'perfil', 'so_imagem' => false, 'dominios' => 'perfil'],
        'tiktok' => ['rotulo' => 'TikTok', 'rotulo_no' => 'no TikTok', 'publicacao' => true, 'acompanhar' => true, 'conta' => null, 'so_imagem' => false, 'dominios' => ['tiktok.com']],
        'whatsapp' => ['rotulo' => 'WhatsApp', 'rotulo_no' => 'no WhatsApp', 'publicacao' => true, 'acompanhar' => false, 'conta' => 'whatsapp', 'so_imagem' => true, 'dominios' => []],
        'spotify' => ['rotulo' => 'Spotify', 'rotulo_no' => 'no Spotify', 'publicacao' => false, 'acompanhar' => true, 'conta' => null, 'so_imagem' => false, 'dominios' => ['spotify.com']],
        'flickr' => ['rotulo' => 'Flickr', 'rotulo_no' => 'no Flickr', 'publicacao' => false, 'acompanhar' => true, 'conta' => null, 'so_imagem' => false, 'dominios' => ['flickr.com', 'flic.kr']],
        'qualquer' => ['rotulo' => 'Qualquer rede', 'rotulo_no' => 'na rede em que você publicou', 'publicacao' => true, 'acompanhar' => false, 'conta' => null, 'so_imagem' => true, 'dominios' => []],
    ];

    public static function rotuloDaRede($rede)
    {
        return isset(self::REDES[$rede]) ? self::REDES[$rede]['rotulo'] : $rede;
    }

    /**
     * Dominios aceitos no endereco de uma rede: a lista do perfil
     * (UsuarioPerfilRepository::REDES_ENDERECO, lida e nunca alterada) nas
     * redes do perfil, a lista propria nas demais e lista vazia quando a
     * rede nao aceita endereco.
     */
    public static function dominiosDaRede($rede)
    {
        if (!isset(self::REDES[$rede])) {
            return [];
        }

        $dominios = self::REDES[$rede]['dominios'];

        if ($dominios === 'perfil') {
            return isset(UsuarioPerfilRepository::REDES_ENDERECO[$rede]) ? UsuarioPerfilRepository::REDES_ENDERECO[$rede]['dominios'] : [];
        }

        return $dominios;
    }

    const PROVAS = ['imagem', 'endereco', 'ambos'];

    public function buscarPorEvento($eventoId)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('SELECT * FROM evento_divulgacao_config WHERE evento_id = :evento_id LIMIT 1');
        $stmt->execute(['evento_id' => $eventoId]);

        $linha = $stmt->fetch();

        return $linha !== false ? $linha : null;
    }

    /**
     * Configuracao normalizada do modulo. Recurso opcional da tela do
     * participante: falha de banco (tabela ainda nao criada, por exemplo)
     * devolve o padrao desligado em vez de derrubar o painel de todo
     * inscrito, mesma protecao de Estandes e Conexoes.
     */
    public function vigente($eventoId)
    {
        try {
            $linha = $this->buscarPorEvento($eventoId);
        } catch (\PDOException $e) {
            error_log('[Divulgacao] Falha ao ler a configuracao do evento ' . (int) $eventoId . ': ' . $e->getMessage());

            return self::PADRAO;
        }

        if ($linha === null) {
            return self::PADRAO;
        }

        return [
            'ativo' => (int) $linha['ativo'],
            'data_inicio' => $linha['data_inicio'] !== null && $linha['data_inicio'] !== '' ? substr($linha['data_inicio'], 0, 10) : null,
            'data_fim' => $linha['data_fim'] !== null && $linha['data_fim'] !== '' ? substr($linha['data_fim'], 0, 10) : null,
            'dias_retencao_imagens' => isset($linha['dias_retencao_imagens']) ? (int) $linha['dias_retencao_imagens'] : null,
        ];
    }

    /**
     * Eventos com prazo de expurgo automatico preenchido, com a data final do
     * evento, para database/expurgar_imagens_divulgacao.php.
     */
    public function listarComRetencao()
    {
        $pdo = Database::conexao();
        $stmt = $pdo->query(
            'SELECT c.evento_id, c.dias_retencao_imagens, e.nome, e.data_fim
             FROM evento_divulgacao_config c
             JOIN eventos e ON e.id = c.evento_id
             WHERE c.dias_retencao_imagens IS NOT NULL'
        );

        return $stmt->fetchAll();
    }

    public function estaAtivo($eventoId)
    {
        $config = $this->vigente($eventoId);

        return $config['ativo'] === 1;
    }

    public function salvar($eventoId, array $dados)
    {
        $antes = $this->buscarPorEvento($eventoId);
        $campos = [
            'evento_id' => $eventoId,
            'ativo' => !empty($dados['ativo']) ? 1 : 0,
            'data_inicio' => $this->dataOuNulo(isset($dados['data_inicio']) ? $dados['data_inicio'] : null),
            'data_fim' => $this->dataOuNulo(isset($dados['data_fim']) ? $dados['data_fim'] : null),
            'dias_retencao_imagens' => isset($dados['dias_retencao_imagens']) && $dados['dias_retencao_imagens'] !== null && $dados['dias_retencao_imagens'] !== ''
                ? max(0, min(3650, (int) $dados['dias_retencao_imagens']))
                : null,
        ];

        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'INSERT INTO evento_divulgacao_config (evento_id, ativo, data_inicio, data_fim, dias_retencao_imagens)
             VALUES (:evento_id, :ativo, :data_inicio, :data_fim, :dias_retencao_imagens)
             ON DUPLICATE KEY UPDATE ativo = VALUES(ativo), data_inicio = VALUES(data_inicio),
                 data_fim = VALUES(data_fim), dias_retencao_imagens = VALUES(dias_retencao_imagens)'
        );
        $stmt->execute($campos);

        Auditoria::registrar('salvar', 'evento_divulgacao_config', (int) $eventoId, $antes, $campos);
    }

    /**
     * Todas as redes suportadas, com a linha salva do evento ou o padrao
     * quando ainda nao ha' linha. A lista vem sempre completa porque rede()
     * e os rotulos da auditoria dependem disso, inclusive para a rede que
     * foi retirada do evento depois de ja' ter recebido comprovacao.
     *
     * Quem desenha o formulario de Configuracoes e' redesIncluidas(), nunca
     * este metodo: a tela mostra so' o que o Administrador escolheu.
     */
    public function listarRedes($eventoId)
    {
        try {
            $pdo = Database::conexao();
            $stmt = $pdo->prepare('SELECT * FROM evento_divulgacao_redes WHERE evento_id = :evento_id');
            $stmt->execute(['evento_id' => $eventoId]);
            $linhas = $stmt->fetchAll();
        } catch (\PDOException $e) {
            error_log('[Divulgacao] Falha ao ler as redes do evento ' . (int) $eventoId . ': ' . $e->getMessage());
            $linhas = [];
        }

        $porRede = [];
        foreach ($linhas as $linha) {
            $porRede[$linha['rede']] = $linha;
        }

        $resultado = [];
        foreach (self::REDES as $rede => $capacidades) {
            $base = self::PADRAO_REDE;
            $base['rede'] = $rede;
            $base['rotulo'] = $capacidades['rotulo'];
            $base['rotulo_no'] = $capacidades['rotulo_no'];
            $base['aceita_publicacao'] = $capacidades['publicacao'];
            $base['aceita_acompanhar'] = $capacidades['acompanhar'];
            $base['conta'] = $capacidades['conta'];
            $base['so_imagem'] = $capacidades['so_imagem'];

            if (isset($porRede[$rede])) {
                $linha = $porRede[$rede];
                $base['incluida'] = isset($linha['incluida']) ? (int) $linha['incluida'] : 0;
                $base['publicacao_ativa'] = $capacidades['publicacao'] ? (int) $linha['publicacao_ativa'] : 0;
                $base['publicacao_pontos'] = (int) $linha['publicacao_pontos'];
                $base['publicacao_prova'] = $capacidades['so_imagem'] ? 'imagem' : $linha['publicacao_prova'];
                $base['publicacao_teto_dia'] = (int) $linha['publicacao_teto_dia'];
                $base['publicacao_teto_evento'] = (int) $linha['publicacao_teto_evento'];
                $base['acompanhar_ativa'] = $capacidades['acompanhar'] ? (int) $linha['acompanhar_ativa'] : 0;
                $base['acompanhar_pontos'] = (int) $linha['acompanhar_pontos'];
                $base['acompanhar_prova'] = $linha['acompanhar_prova'];
                $base['acompanhar_canal_nome'] = isset($linha['acompanhar_canal_nome']) ? $linha['acompanhar_canal_nome'] : null;
                $base['acompanhar_canal_endereco'] = isset($linha['acompanhar_canal_endereco']) ? $linha['acompanhar_canal_endereco'] : null;
            } elseif ($capacidades['so_imagem']) {
                $base['publicacao_prova'] = 'imagem';
            }

            $resultado[$rede] = $base;
        }

        return $resultado;
    }

    /**
     * Fase 58: as redes que o Administrador escolheu para este evento, na
     * ordem da constante. E' a lista que a tela de Configuracoes desenha.
     */
    public function redesIncluidas($eventoId)
    {
        $incluidas = [];
        foreach ($this->listarRedes($eventoId) as $rede => $dados) {
            if ((int) $dados['incluida'] === 1) {
                $incluidas[$rede] = $dados;
            }
        }

        return $incluidas;
    }

    /**
     * Fase 58: chave => rotulo das redes que ainda nao foram incluidas, para
     * o seletor "Acrescentar rede". Mesmo desenho de
     * GamificacaoAdminController::desempate(), que calcula a lista fechada
     * menos o que ja' esta' dentro.
     */
    public function redesDisponiveis($eventoId)
    {
        $disponiveis = [];
        foreach ($this->listarRedes($eventoId) as $rede => $dados) {
            if ((int) $dados['incluida'] !== 1) {
                $disponiveis[$rede] = $dados['rotulo'];
            }
        }

        return $disponiveis;
    }

    /**
     * Fase 58: inclui uma rede no evento. Cria a linha com os padroes, ou
     * marca a que ja' existe, preservando o que estiver gravado nela.
     * Devolve false quando a rede nao existe na lista fechada ou ja' estava
     * incluida, para a tela avisar sem tratar como erro.
     */
    public function incluirRede($eventoId, $rede)
    {
        if (!isset(self::REDES[$rede])) {
            return false;
        }

        $antes = $this->listarRedes($eventoId);

        if (isset($antes[$rede]) && (int) $antes[$rede]['incluida'] === 1) {
            return false;
        }

        $capacidades = self::REDES[$rede];
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'INSERT INTO evento_divulgacao_redes (evento_id, rede, incluida, publicacao_ativa, publicacao_pontos,
                 publicacao_prova, publicacao_teto_dia, publicacao_teto_evento, acompanhar_ativa,
                 acompanhar_pontos, acompanhar_prova)
             VALUES (:evento_id, :rede, 1, 0, 0, :publicacao_prova, 0, 0, 0, 0, :acompanhar_prova)
             ON DUPLICATE KEY UPDATE incluida = 1'
        );
        $stmt->execute([
            'evento_id' => (int) $eventoId,
            'rede' => $rede,
            'publicacao_prova' => $capacidades['so_imagem'] ? 'imagem' : self::PADRAO_REDE['publicacao_prova'],
            'acompanhar_prova' => self::PADRAO_REDE['acompanhar_prova'],
        ]);

        Auditoria::registrar('incluir_rede', 'evento_divulgacao_redes', (int) $eventoId, null, ['rede' => $rede]);

        return true;
    }

    /**
     * Fase 58: retira a rede do evento. Nao apaga a linha: pontos, limites e
     * canal ficam guardados, para que a rede incluida de novo volte como
     * estava, e as comprovacoes ja' enviadas continuam com os pontos
     * congelados na propria linha delas.
     */
    public function retirarRede($eventoId, $rede)
    {
        if (!isset(self::REDES[$rede])) {
            return false;
        }

        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'UPDATE evento_divulgacao_redes SET incluida = 0 WHERE evento_id = :evento_id AND rede = :rede AND incluida = 1'
        );
        $stmt->execute(['evento_id' => (int) $eventoId, 'rede' => $rede]);

        if ($stmt->rowCount() === 0) {
            return false;
        }

        Auditoria::registrar('retirar_rede', 'evento_divulgacao_redes', (int) $eventoId, ['rede' => $rede], null);

        return true;
    }

    /**
     * So' as redes incluidas no evento e com ao menos um dos dois tipos de
     * acao ligado. E' o que a tela do participante usa para montar as
     * opcoes de envio.
     */
    public function redesAtivas($eventoId)
    {
        $ativas = [];
        foreach ($this->redesIncluidas($eventoId) as $rede => $dados) {
            if ($dados['publicacao_ativa'] === 1 || $dados['acompanhar_ativa'] === 1) {
                $ativas[$rede] = $dados;
            }
        }

        return $ativas;
    }

    /**
     * Configuracao de uma rede, sempre normalizada; rede desconhecida
     * devolve o padrao desligado, nunca nulo.
     */
    public function rede($eventoId, $rede)
    {
        $redes = $this->listarRedes($eventoId);

        return isset($redes[$rede]) ? $redes[$rede] : null;
    }

    public function salvarRedes($eventoId, array $redes)
    {
        $antes = $this->listarRedes($eventoId);
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'INSERT INTO evento_divulgacao_redes (evento_id, rede, incluida, publicacao_ativa, publicacao_pontos,
                 publicacao_prova, publicacao_teto_dia, publicacao_teto_evento, acompanhar_ativa,
                 acompanhar_pontos, acompanhar_prova, acompanhar_canal_nome, acompanhar_canal_endereco)
             VALUES (:evento_id, :rede, 1, :publicacao_ativa, :publicacao_pontos, :publicacao_prova,
                 :publicacao_teto_dia, :publicacao_teto_evento, :acompanhar_ativa, :acompanhar_pontos,
                 :acompanhar_prova, :acompanhar_canal_nome, :acompanhar_canal_endereco)
             ON DUPLICATE KEY UPDATE publicacao_ativa = VALUES(publicacao_ativa),
                 publicacao_pontos = VALUES(publicacao_pontos), publicacao_prova = VALUES(publicacao_prova),
                 publicacao_teto_dia = VALUES(publicacao_teto_dia),
                 publicacao_teto_evento = VALUES(publicacao_teto_evento),
                 acompanhar_ativa = VALUES(acompanhar_ativa), acompanhar_pontos = VALUES(acompanhar_pontos),
                 acompanhar_prova = VALUES(acompanhar_prova),
                 acompanhar_canal_nome = VALUES(acompanhar_canal_nome),
                 acompanhar_canal_endereco = VALUES(acompanhar_canal_endereco)'
        );

        // Fase 58: so' as redes incluidas no evento sao gravadas. Antes
        // disto o laco percorria a lista fechada inteira, entao o primeiro
        // salvamento criava uma linha para cada uma das dez redes e a tabela
        // nao distinguia a rede escolhida da rede que ninguem tocou.
        $depois = [];
        foreach ($antes as $rede => $linhaAnterior) {
            if ((int) $linhaAnterior['incluida'] !== 1) {
                continue;
            }

            $capacidades = self::REDES[$rede];
            $dados = isset($redes[$rede]) && is_array($redes[$rede]) ? $redes[$rede] : [];

            // A capacidade da rede e' conferida aqui, no servidor, e nao so'
            // na tela: rede que nao aceita publicacao nunca a tem ligada, e
            // rede so' de imagem nunca aceita endereco.
            $campos = [
                'evento_id' => $eventoId,
                'rede' => $rede,
                'publicacao_ativa' => $capacidades['publicacao'] && !empty($dados['publicacao_ativa']) ? 1 : 0,
                'publicacao_pontos' => $this->inteiroNaoNegativo(isset($dados['publicacao_pontos']) ? $dados['publicacao_pontos'] : 0),
                'publicacao_prova' => $capacidades['so_imagem']
                    ? 'imagem'
                    : $this->provaValida(isset($dados['publicacao_prova']) ? $dados['publicacao_prova'] : 'ambos'),
                'publicacao_teto_dia' => $this->inteiroNaoNegativo(isset($dados['publicacao_teto_dia']) ? $dados['publicacao_teto_dia'] : 0),
                'publicacao_teto_evento' => $this->inteiroNaoNegativo(isset($dados['publicacao_teto_evento']) ? $dados['publicacao_teto_evento'] : 0),
                'acompanhar_ativa' => $capacidades['acompanhar'] && !empty($dados['acompanhar_ativa']) ? 1 : 0,
                'acompanhar_pontos' => $this->inteiroNaoNegativo(isset($dados['acompanhar_pontos']) ? $dados['acompanhar_pontos'] : 0),
                'acompanhar_prova' => $this->provaValida(isset($dados['acompanhar_prova']) ? $dados['acompanhar_prova'] : 'imagem'),
                'acompanhar_canal_nome' => $this->textoOuNulo(isset($dados['acompanhar_canal_nome']) ? $dados['acompanhar_canal_nome'] : null, 100),
                'acompanhar_canal_endereco' => $this->enderecoOuNulo(isset($dados['acompanhar_canal_endereco']) ? $dados['acompanhar_canal_endereco'] : null),
            ];
            $stmt->execute($campos);
            $depois[$rede] = $campos;
        }

        Auditoria::registrar('salvar', 'evento_divulgacao_redes', (int) $eventoId, $antes, $depois);
    }

    private function dataOuNulo($valor)
    {
        $valor = is_string($valor) ? trim($valor) : '';

        if ($valor === '') {
            return null;
        }

        return preg_match('/^\d{4}-\d{2}-\d{2}$/', $valor) === 1 ? $valor : null;
    }

    private function inteiroNaoNegativo($valor)
    {
        $numero = (int) $valor;

        return $numero > 0 ? min($numero, 65535) : 0;
    }

    private function provaValida($valor)
    {
        return in_array($valor, self::PROVAS, true) ? $valor : 'ambos';
    }

    private function textoOuNulo($valor, $limite)
    {
        $valor = is_string($valor) ? trim($valor) : '';

        return $valor !== '' ? mb_substr($valor, 0, $limite) : null;
    }

    /**
     * Endereco do canal a seguir, digitado pelo Administrador e mostrado ao
     * participante como link: so' http ou https (linkHttpValido()), ate' o
     * tamanho da coluna. Qualquer outra coisa vira nulo e a tela mostra so'
     * o nome.
     */
    private function enderecoOuNulo($valor)
    {
        $valor = is_string($valor) ? trim($valor) : '';

        if ($valor === '' || mb_strlen($valor) > 255 || !linkHttpValido($valor)) {
            return null;
        }

        return $valor;
    }
}
