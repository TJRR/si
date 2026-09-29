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
     * desligado e sem janela propria (as datas do evento valem).
     */
    const PADRAO = [
        'ativo' => 0,
        'data_inicio' => null,
        'data_fim' => null,
    ];

    /**
     * Linha de rede que ainda nao foi salva: nada ativo, nada pontuando.
     */
    const PADRAO_REDE = [
        'publicacao_ativa' => 0,
        'publicacao_pontos' => 0,
        'publicacao_prova' => 'ambos',
        'publicacao_teto_dia' => 0,
        'publicacao_teto_evento' => 0,
        'acompanhar_ativa' => 0,
        'acompanhar_pontos' => 0,
        'acompanhar_prova' => 'imagem',
    ];

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
        ];
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
        ];

        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'INSERT INTO evento_divulgacao_config (evento_id, ativo, data_inicio, data_fim)
             VALUES (:evento_id, :ativo, :data_inicio, :data_fim)
             ON DUPLICATE KEY UPDATE ativo = VALUES(ativo), data_inicio = VALUES(data_inicio),
                 data_fim = VALUES(data_fim)'
        );
        $stmt->execute($campos);

        Auditoria::registrar('salvar', 'evento_divulgacao_config', (int) $eventoId, $antes, $campos);
    }

    /**
     * Todas as redes suportadas, com a linha salva do evento ou o padrao
     * quando ainda nao ha' linha. A tela de configuracao desenha uma secao
     * por rede a partir daqui, entao a lista vem sempre completa.
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
        foreach (UsuarioPerfilRepository::REDES_SUPORTADAS as $rede) {
            $base = self::PADRAO_REDE;
            $base['rede'] = $rede;
            $base['rotulo'] = isset(UsuarioPerfilRepository::REDES_ROTULOS[$rede])
                ? UsuarioPerfilRepository::REDES_ROTULOS[$rede]
                : $rede;

            if (isset($porRede[$rede])) {
                $linha = $porRede[$rede];
                $base['publicacao_ativa'] = (int) $linha['publicacao_ativa'];
                $base['publicacao_pontos'] = (int) $linha['publicacao_pontos'];
                $base['publicacao_prova'] = $linha['publicacao_prova'];
                $base['publicacao_teto_dia'] = (int) $linha['publicacao_teto_dia'];
                $base['publicacao_teto_evento'] = (int) $linha['publicacao_teto_evento'];
                $base['acompanhar_ativa'] = (int) $linha['acompanhar_ativa'];
                $base['acompanhar_pontos'] = (int) $linha['acompanhar_pontos'];
                $base['acompanhar_prova'] = $linha['acompanhar_prova'];
            }

            $resultado[$rede] = $base;
        }

        return $resultado;
    }

    /**
     * So' as redes com ao menos um dos dois tipos de acao ligado. E' o que a
     * tela do participante usa para montar as opcoes de envio.
     */
    public function redesAtivas($eventoId)
    {
        $ativas = [];
        foreach ($this->listarRedes($eventoId) as $rede => $dados) {
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
            'INSERT INTO evento_divulgacao_redes (evento_id, rede, publicacao_ativa, publicacao_pontos,
                 publicacao_prova, publicacao_teto_dia, publicacao_teto_evento, acompanhar_ativa,
                 acompanhar_pontos, acompanhar_prova)
             VALUES (:evento_id, :rede, :publicacao_ativa, :publicacao_pontos, :publicacao_prova,
                 :publicacao_teto_dia, :publicacao_teto_evento, :acompanhar_ativa, :acompanhar_pontos,
                 :acompanhar_prova)
             ON DUPLICATE KEY UPDATE publicacao_ativa = VALUES(publicacao_ativa),
                 publicacao_pontos = VALUES(publicacao_pontos), publicacao_prova = VALUES(publicacao_prova),
                 publicacao_teto_dia = VALUES(publicacao_teto_dia),
                 publicacao_teto_evento = VALUES(publicacao_teto_evento),
                 acompanhar_ativa = VALUES(acompanhar_ativa), acompanhar_pontos = VALUES(acompanhar_pontos),
                 acompanhar_prova = VALUES(acompanhar_prova)'
        );

        $depois = [];
        foreach (UsuarioPerfilRepository::REDES_SUPORTADAS as $rede) {
            $dados = isset($redes[$rede]) && is_array($redes[$rede]) ? $redes[$rede] : [];
            $campos = [
                'evento_id' => $eventoId,
                'rede' => $rede,
                'publicacao_ativa' => !empty($dados['publicacao_ativa']) ? 1 : 0,
                'publicacao_pontos' => $this->inteiroNaoNegativo(isset($dados['publicacao_pontos']) ? $dados['publicacao_pontos'] : 0),
                'publicacao_prova' => $this->provaValida(isset($dados['publicacao_prova']) ? $dados['publicacao_prova'] : 'ambos'),
                'publicacao_teto_dia' => $this->inteiroNaoNegativo(isset($dados['publicacao_teto_dia']) ? $dados['publicacao_teto_dia'] : 0),
                'publicacao_teto_evento' => $this->inteiroNaoNegativo(isset($dados['publicacao_teto_evento']) ? $dados['publicacao_teto_evento'] : 0),
                'acompanhar_ativa' => !empty($dados['acompanhar_ativa']) ? 1 : 0,
                'acompanhar_pontos' => $this->inteiroNaoNegativo(isset($dados['acompanhar_pontos']) ? $dados['acompanhar_pontos'] : 0),
                'acompanhar_prova' => $this->provaValida(isset($dados['acompanhar_prova']) ? $dados['acompanhar_prova'] : 'imagem'),
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
}
