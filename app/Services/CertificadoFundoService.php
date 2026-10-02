<?php

namespace App\Services;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Repositories\MidiaPastaRepository;
use App\Repositories\MidiaRepository;

/**
 * Fase 59: a pasta de planos de fundo dos certificados na Biblioteca de
 * midia, e as imagens que o seletor da tela oferece.
 *
 * A pasta e a arte que acompanha o sistema sao criadas na PRIMEIRA ABERTURA
 * da tela de certificados, nunca semeadas em migration: dado de negocio nao
 * entra por migration, e o mesmo gatilho sob demanda ja' e' usado em
 * TrabalhoRepository::garantirNumerosSigilo().
 *
 * A arte padrao mora em assets/img/certificados/fundo_padrao.webp, que e'
 * arquivo do proprio sistema (vai no deploy junto com o codigo). A linha em
 * midias apenas APONTA para ela: nada e' copiado para assets/uploads, entao
 * apagar a linha nao apaga a arte e o sistema continua podendo oferece-la.
 */
class CertificadoFundoService
{
    const NOME_PASTA = 'Fundo Certificados';

    const ARTE_PADRAO = 'img/certificados/fundo_padrao.webp';

    const TITULO_ARTE_PADRAO = 'Fundo padrão de certificado';

    private $pastas;
    private $midias;

    public function __construct()
    {
        $this->pastas = new MidiaPastaRepository();
        $this->midias = new MidiaRepository();
    }

    /**
     * Devolve a pasta de fundos, criando-a com a arte padrao dentro quando
     * ainda nao existir. Falha de banco nunca derruba a tela que chamou:
     * devolve null, e o seletor abre sem pasta inicial.
     */
    public function garantirPasta($usuarioId)
    {
        try {
            $pasta = $this->buscarPasta();

            if ($pasta === null) {
                $id = $this->pastas->criar(self::NOME_PASTA, null, $usuarioId);
                $pasta = $this->pastas->buscarPorId($id);
            }

            if ($pasta !== null) {
                $this->garantirArtePadrao((int) $pasta['id'], $usuarioId);
            }

            return $pasta;
        } catch (\PDOException $e) {
            error_log('[Certificado] Falha ao preparar a pasta de fundos: ' . $e->getMessage());

            return null;
        }
    }

    /**
     * A pasta pelo nome, na raiz da Biblioteca. Renomeada pelo
     * Administrador, uma pasta nova e' criada na proxima abertura e as
     * imagens antigas continuam onde estao, alcancaveis pelo seletor de
     * pasta - nenhuma arte se perde.
     */
    public function buscarPasta()
    {
        foreach ($this->pastas->listarFilhas(null) as $pasta) {
            if ($pasta['nome'] === self::NOME_PASTA) {
                return $pasta;
            }
        }

        return null;
    }

    /**
     * O endereco da arte que acompanha o sistema, para o seletor nascer com
     * ela escolhida enquanto o Administrador nao escolher outra.
     */
    public static function urlArtePadrao()
    {
        return config('base_path') . '/assets/' . self::ARTE_PADRAO;
    }

    /**
     * As imagens de uma pasta, no formato que o seletor consome. Pasta nula
     * lista a raiz da Biblioteca.
     */
    public function listarImagens($pastaId)
    {
        $imagens = [];

        foreach ($this->midias->listar('imagem', $pastaId) as $midia) {
            $imagens[] = [
                'url' => config('base_path') . '/assets/' . $midia['arquivo_path'],
                'titulo' => $midia['titulo'] !== null && $midia['titulo'] !== ''
                    ? $midia['titulo']
                    : basename($midia['arquivo_path']),
            ];
        }

        return $imagens;
    }

    /**
     * As pastas da Biblioteca, em lista plana com o caminho por extenso, para
     * o seletor poder sair da pasta de fundos sem precisar de arvore.
     */
    public function listarPastas()
    {
        $lista = [];

        foreach ($this->pastas->listarTodas() as $pasta) {
            $nomes = [];

            // caminho() devolve a cadeia de pastas ate' a raiz, uma linha por
            // nivel; aqui ela vira um texto so', como a tela precisa.
            foreach ($this->pastas->caminho((int) $pasta['id']) as $nivel) {
                $nomes[] = $nivel['nome'];
            }

            $lista[] = [
                'id' => (int) $pasta['id'],
                'nome' => implode(' / ', $nomes),
            ];
        }

        return $lista;
    }

    /**
     * Grava em midias a arte que acompanha o sistema, se ela ainda nao
     * estiver registrada na pasta. A conferencia e' pelo caminho do arquivo,
     * entao reabrir a tela nao cria uma segunda linha.
     */
    private function garantirArtePadrao($pastaId, $usuarioId)
    {
        foreach ($this->midias->listar('imagem', $pastaId) as $midia) {
            if ($midia['arquivo_path'] === self::ARTE_PADRAO) {
                return;
            }
        }

        if (!is_file(__DIR__ . '/../../assets/' . self::ARTE_PADRAO)) {
            error_log('[Certificado] A arte padrao de fundo nao foi encontrada em assets/' . self::ARTE_PADRAO);

            return;
        }

        $this->midias->criar([
            'concurso_id' => null,
            'pasta_id' => $pastaId,
            'arquivo_path' => self::ARTE_PADRAO,
            'tipo' => 'imagem',
            'alt_text' => null,
            'titulo' => self::TITULO_ARTE_PADRAO,
            'descricao' => 'Arte que acompanha o sistema, oferecida como fundo inicial dos certificados.',
            'criado_por' => $usuarioId,
        ]);
    }
}
