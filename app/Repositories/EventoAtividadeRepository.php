<?php

namespace App\Repositories;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Core\Auditoria;
use App\Core\Database;
use App\Services\CodigoUnicoService;

/**
 * Fase 46: cursos/palestras/seminarios do Evento - primeira entidade filha
 * de arvore do Evento (NavegacaoService::noAtividades()/noAtividade()).
 * Inscricao (evento_atividade_inscricoes, ver EventoAtividadeInscricaoRepository)
 * e emissao de certificado sao configuraveis como opcionais por atividade.
 *
 * Fase 59: emite_certificado, gravada aqui desde a Fase 46 e sem leitor ate'
 * agora, passou a valer de verdade - e' ela que decide se a atividade gera
 * certificado proprio (CertificadoElegibilidadeService). A atividade ganhou
 * tambem certificado_fundo_url, o plano de fundo so' dela; em branco, vale o
 * das atividades na configuracao dos certificados do evento.
 */
class EventoAtividadeRepository
{
    public function listarPorEvento($eventoId)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'SELECT a.*,
                    (SELECT COUNT(*) FROM evento_atividade_inscricoes i WHERE i.atividade_id = a.id AND i.status = "confirmada") AS total_confirmadas,
                    (SELECT COUNT(*) FROM evento_atividade_inscricoes i WHERE i.atividade_id = a.id AND i.status = "espera") AS total_espera
             FROM evento_atividades a
             WHERE a.evento_id = :evento_id
             ORDER BY a.data_inicio ASC'
        );
        $stmt->execute(['evento_id' => $eventoId]);

        return $stmt->fetchAll();
    }

    public function buscarPorId($id)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('SELECT * FROM evento_atividades WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);

        $atividade = $stmt->fetch();

        return $atividade !== false ? $atividade : null;
    }

    /**
     * Fase 47: usado por EventoAppController::validarPresenca() - a
     * atividade e' identificada pelo proprio codigo lido, restrito ao
     * evento do leitor (nunca aceita atividade_id cru do cliente).
     */
    public function buscarPorCodigo($eventoId, $codigo)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('SELECT * FROM evento_atividades WHERE evento_id = :evento_id AND codigo_atividade = :codigo LIMIT 1');
        $stmt->execute(['evento_id' => $eventoId, 'codigo' => $codigo]);

        $atividade = $stmt->fetch();

        return $atividade !== false ? $atividade : null;
    }

    /**
     * Fase 48: mesmo molde de buscarPorCodigo(), mas para o codigo de 5
     * caracteres usado na confirmacao de presenca online (atividade
     * 'online' ou 'hibrido') - EventoAppController::validarPresenca()
     * decide qual dos dois metodos chamar pelo tamanho do codigo recebido.
     */
    public function buscarPorCodigoPresencaOnline($eventoId, $codigo)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('SELECT * FROM evento_atividades WHERE evento_id = :evento_id AND codigo_presenca_online = :codigo LIMIT 1');
        $stmt->execute(['evento_id' => $eventoId, 'codigo' => $codigo]);

        $atividade = $stmt->fetch();

        return $atividade !== false ? $atividade : null;
    }

    public function criar($eventoId, array $dados)
    {
        $campos = $this->camposComuns($dados);
        $campos['evento_id'] = $eventoId;
        // Fase 58: o codigo de 6 caracteres da sala e' lido pelo mesmo leitor
        // que os codigos de competicao e de credenciamento no local, entao a
        // unicidade tem de valer entre as tres colunas, e nao so' nesta.
        $campos['codigo_atividade'] = CodigoUnicoService::gerarCodigoFixoDoEvento();
        $campos['codigo_presenca_online'] = $campos['modalidade'] !== 'presencial'
            ? CodigoUnicoService::gerar('evento_atividades', 'codigo_presenca_online', 5)
            : null;

        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'INSERT INTO evento_atividades
                (evento_id, nome, tipo_id, destacar_na_pagina, descricao_html, local, modalidade, data_inicio, data_fim, exige_inscricao, emite_certificado, certificado_fundo_url, certificado_fundo_cor, vagas, permite_lista_espera, tolerancia_presenca_efetiva, antecedencia_abertura_presenca, pontos_presenca, pontos_pontualidade, codigo_atividade, codigo_presenca_online)
             VALUES
                (:evento_id, :nome, :tipo_id, :destacar_na_pagina, :descricao_html, :local, :modalidade, :data_inicio, :data_fim, :exige_inscricao, :emite_certificado, :certificado_fundo_url, :certificado_fundo_cor, :vagas, :permite_lista_espera, :tolerancia_presenca_efetiva, :antecedencia_abertura_presenca, :pontos_presenca, :pontos_pontualidade, :codigo_atividade, :codigo_presenca_online)'
        );
        $stmt->execute($campos);
        $id = (int) $pdo->lastInsertId();

        Auditoria::registrar('criar', 'evento_atividades', $id, null, $campos);

        return $id;
    }

    /**
     * Fase 48: se a modalidade mudar para 'presencial' depois de ja ter um
     * codigo_presenca_online gerado, o codigo e' descartado (NULL) - nao ha'
     * mais caminho online pra ele valer. Se mudar de 'presencial' para
     * 'online'/'hibrido' e ainda nao existir codigo, gera um novo. Uma vez
     * gerado, o codigo permanece o mesmo entre idas e vindas (fixo por
     * atividade, decisao desta fase - ver plano).
     */
    public function atualizar($id, array $dados)
    {
        $antes = $this->buscarPorId($id);
        $campos = $this->camposComuns($dados);
        $campos['id'] = $id;

        if ($campos['modalidade'] === 'presencial') {
            $campos['codigo_presenca_online'] = null;
        } elseif (empty($antes['codigo_presenca_online'])) {
            $campos['codigo_presenca_online'] = CodigoUnicoService::gerar('evento_atividades', 'codigo_presenca_online', 5);
        } else {
            $campos['codigo_presenca_online'] = $antes['codigo_presenca_online'];
        }

        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'UPDATE evento_atividades
             SET nome = :nome, tipo_id = :tipo_id, destacar_na_pagina = :destacar_na_pagina,
                 descricao_html = :descricao_html, local = :local, modalidade = :modalidade,
                 codigo_presenca_online = :codigo_presenca_online, data_inicio = :data_inicio,
                 data_fim = :data_fim, exige_inscricao = :exige_inscricao, emite_certificado = :emite_certificado,
                 certificado_fundo_url = :certificado_fundo_url,
                 certificado_fundo_cor = :certificado_fundo_cor,
                 vagas = :vagas, permite_lista_espera = :permite_lista_espera,
                 tolerancia_presenca_efetiva = :tolerancia_presenca_efetiva,
                 antecedencia_abertura_presenca = :antecedencia_abertura_presenca,
                 pontos_presenca = :pontos_presenca, pontos_pontualidade = :pontos_pontualidade
             WHERE id = :id'
        );
        $stmt->execute($campos);

        Auditoria::registrar('atualizar', 'evento_atividades', $id, $antes, $campos);
    }

    public function remover($id)
    {
        $antes = $this->buscarPorId($id);

        $pdo = Database::conexao();
        $stmt = $pdo->prepare('DELETE FROM evento_atividades WHERE id = :id');
        $stmt->execute(['id' => $id]);

        Auditoria::registrar('remover', 'evento_atividades', $id, $antes, null);
    }

    private function camposComuns(array $dados)
    {
        return [
            'nome' => $dados['nome'],
            // Fase 51: tipo (etiqueta colorida) e destaque na pagina publica
            // do evento - os dois so' fazem sentido quando a pagina existe,
            // por isso sao opcionais aqui.
            'tipo_id' => !empty($dados['tipo_id']) ? (int) $dados['tipo_id'] : null,
            'destacar_na_pagina' => !empty($dados['destacar_na_pagina']) ? 1 : 0,
            'descricao_html' => $dados['descricao_html'] !== '' ? $dados['descricao_html'] : null,
            'local' => $dados['local'] !== '' ? $dados['local'] : null,
            'modalidade' => $dados['modalidade'],
            'data_inicio' => str_replace('T', ' ', $dados['data_inicio']),
            'data_fim' => str_replace('T', ' ', $dados['data_fim']),
            'exige_inscricao' => $dados['exige_inscricao'] ? 1 : 0,
            'emite_certificado' => $dados['emite_certificado'] ? 1 : 0,
            'vagas' => $dados['vagas'] !== '' && $dados['vagas'] !== null ? (int) $dados['vagas'] : null,
            'permite_lista_espera' => $dados['permite_lista_espera'] ? 1 : 0,
            'tolerancia_presenca_efetiva' => (int) $dados['tolerancia_presenca_efetiva'],
            'antecedencia_abertura_presenca' => (int) $dados['antecedencia_abertura_presenca'],
            // Fase 58: excecao de pontos na propria atividade. Campo em
            // branco grava NULO, que e' "herda do tipo"; zero e' "esta
            // atividade nao pontua".
            'pontos_presenca' => isset($dados['pontos_presenca']) && $dados['pontos_presenca'] !== '' && $dados['pontos_presenca'] !== null
                ? (int) $dados['pontos_presenca']
                : null,
            'pontos_pontualidade' => isset($dados['pontos_pontualidade']) && $dados['pontos_pontualidade'] !== '' && $dados['pontos_pontualidade'] !== null
                ? (int) $dados['pontos_pontualidade']
                : null,
            // Fase 59: plano de fundo proprio do certificado desta atividade.
            // Campo em branco grava NULO, que e' "herda a arte das atividades
            // da configuracao dos certificados" - nunca uma arte inventada.
            'certificado_fundo_url' => isset($dados['certificado_fundo_url']) && trim((string) $dados['certificado_fundo_url']) !== ''
                ? trim((string) $dados['certificado_fundo_url'])
                : null,
            'certificado_fundo_cor' => isset($dados['certificado_fundo_cor']) && trim((string) $dados['certificado_fundo_cor']) !== ''
                ? trim((string) $dados['certificado_fundo_cor'])
                : null,
        ];
    }
}
