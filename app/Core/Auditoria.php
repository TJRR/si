<?php

namespace App\Core;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Repositories\LogAuditoriaRepository;

class Auditoria
{
    /**
     * $usuarioId: passar explicitamente quando a sessao ja tiver sido destruida
     * (logout/timeout) ou ainda nao setada; caso contrario, usa Auth::usuarioId().
     * Nunca deixa uma falha de auditoria (ex.: banco fora do ar) derrubar a acao
     * principal que esta sendo auditada.
     */
    /**
     * Fase 35 (Parte C): campos cujo VALOR nunca pode ser gravado na trilha
     * de auditoria. Rede de seguranca em codigo compartilhado - quem chamar
     * registrar() daqui a seis meses nao precisa conhecer esta regra.
     *
     * Por que importa tanto: log_auditoria entra no arquivo de exportacao
     * completa do banco (exportar_dump_completo.php faz SHOW TABLES e leva
     * TUDO), que e' baixado por endereco publico durante a atualizacao, e
     * tambem alimenta o relatorio de conferencia em PDF. Um segredo que
     * entre aqui vaza pelos dois caminhos de uma vez - e ainda por cima
     * ficaria em texto puro na tabela vizinha aquela onde ele esta cifrado,
     * anulando a cifragem inteira.
     */
    private static $camposProtegidos = [
        'private_key',
        'client_secret',
        'pass',
        'senha',
        'senha_hash',
        'chave_mestra',
    ];
    /**
     * Campos protegidos APENAS dentro de uma entidade especifica. Existe por
     * causa de 'valor': em credenciais_sistema ele e' o segredo cifrado, e em
     * conteudos_site (ConteudoSiteRepository::atualizarValor) e' texto do
     * site, legitimo e ja' presente em linhas historicas da trilha. Proibir
     * 'valor' na lista geral apagaria em silencio uma trilha que funciona -
     * renomear a chave la' faria a auditoria dizer um nome e a coluna do
     * banco dizer outro.
     *
     * Cobre tambem quem, no futuro, escrever direto em credenciais_sistema
     * sem passar por CredencialSistemaRepository.
     */
    private static $camposProtegidosPorEntidade = [
        'credenciais_sistema' => ['valor'],
    ];

    public static function registrar($acao, $entidade, $entidadeId = null, $dadosAntes = null, $dadosDepois = null, $mensagem = null, $usuarioId = null)
    {
        try {
            (new LogAuditoriaRepository())->registrar([
                'usuario_id' => $usuarioId !== null ? $usuarioId : Auth::usuarioId(),
                'acao' => $acao,
                'entidade' => $entidade,
                'entidade_id' => $entidadeId,
                'ip_origem' => isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : null,
                'dados_antes' => $dadosAntes !== null ? json_encode(self::protegerSegredos($dadosAntes, $entidade)) : null,
                'dados_depois' => $dadosDepois !== null ? json_encode(self::protegerSegredos($dadosDepois, $entidade)) : null,
                'mensagem' => $mensagem,
            ]);
        } catch (\Throwable $e) {
            error_log('Falha ao registrar auditoria: ' . $e->getMessage());
        }
    }

    /**
     * Substitui pelo texto "[protegido]" o VALOR de qualquer campo listado
     * em $camposProtegidos, em qualquer profundidade. O nome do campo
     * continua visivel de proposito: a trilha precisa mostrar QUE aquele
     * campo mudou, so' nao pode mostrar para quanto.
     */
    private static function protegerSegredos($dados, $entidade = null)
    {
        if (!is_array($dados)) {
            return $dados;
        }

        $protegidos = self::$camposProtegidos;

        if ($entidade !== null && isset(self::$camposProtegidosPorEntidade[$entidade])) {
            $protegidos = array_merge($protegidos, self::$camposProtegidosPorEntidade[$entidade]);
        }

        foreach ($dados as $campo => $valor) {
            if (is_string($campo) && in_array(strtolower($campo), $protegidos, true)) {
                $dados[$campo] = $valor === null || $valor === '' ? $valor : '[protegido]';
                continue;
            }

            if (is_array($valor)) {
                // A entidade desce junto: um segredo aninhado dentro de um
                // sub-array continua sendo da mesma entidade.
                $dados[$campo] = self::protegerSegredos($valor, $entidade);
            }
        }

        return $dados;
    }
}
