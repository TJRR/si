<?php

namespace App\Services;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Core\Database;
use App\Repositories\EventoTrabalhoTermoRepository;
use App\Repositories\PerfilRepository;
use App\Repositories\TrabalhoAutorRepository;
use App\Repositories\TrabalhoAvaliadorRepository;
use App\Repositories\TrabalhoConfigRepository;
use App\Repositories\TrabalhoRepository;
use App\Repositories\TrabalhoTermoAceiteRepository;
use App\Repositories\UsuarioPerfilRepository;
use App\Validation\CpfValidador;

/**
 * Fase 49: submissao de um Trabalho. Toda regra vem de
 * evento_trabalhos_config (metodos aceitos, quantidade maxima de
 * autores, exige telefone, deduplicacao de pessoa), nunca fixa aqui.
 *
 * Protecao de concorrencia (achado corrigido na revisao desta fase): a
 * checagem de CPF duplicado roda dentro de uma transacao que trava a
 * linha de configuracao do evento (FOR UPDATE), serializando submissoes
 * concorrentes do mesmo evento - mesmo mecanismo ja usado em
 * EventoAtividadeInscricaoRepository::inscrever() para vagas de
 * atividade, adaptado aqui para travar o recurso pai em vez de uma
 * contagem.
 */
class TrabalhoSubmissaoService
{
    private $config;
    private $trabalhos;
    private $autores;
    private $perfis;
    private $avaliadores;
    private $usuarioPerfil;
    private $termos;
    private $aceites;

    public function __construct()
    {
        $this->config = new TrabalhoConfigRepository();
        $this->trabalhos = new TrabalhoRepository();
        $this->autores = new TrabalhoAutorRepository();
        $this->perfis = new PerfilRepository();
        $this->avaliadores = new TrabalhoAvaliadorRepository();
        $this->usuarioPerfil = new UsuarioPerfilRepository();
        $this->termos = new EventoTrabalhoTermoRepository();
        $this->aceites = new TrabalhoTermoAceiteRepository();
    }

    /**
     * $dadosAutorPrincipal: nome, cpf, email, cargo, orgao_origem (cargo/
     * orgao_origem sincronizam com usuarios_perfil ao gravar - Fase 49B).
     * $dadosTrabalho: eixo_tematico_id, natureza_id, titulo, telefone_contato,
     * metodo_submissao, conteudo_html (metodo formulario), link_avaliacao/
     * link_publicacao (metodo link_externo).
     * $coautores: lista de ['nome','cpf','email','cargo','orgao_origem'].
     * $arquivosEnviados: $_FILES['arquivo_avaliacao']/['arquivo_publicacao']
     * (metodos de documento).
     * $termosAceitos (Fase 51): ids dos termos de aceite marcados no
     * formulario. Todo termo ativo e obrigatorio precisa estar aqui, e o
     * texto aceito e' gravado congelado em trabalho_termos_aceitos.
     */
    public function submeter($eventoId, $usuarioId, array $dadosAutorPrincipal, array $dadosTrabalho, array $coautores, array $arquivosEnviados = [], array $termosAceitos = [])
    {
        $config = $this->config->buscarPorEvento($eventoId);

        if ($config === null) {
            throw new \RuntimeException('Este evento ainda não está com a submissão de Trabalhos configurada.');
        }

        $this->validarPrazo($config);
        $this->validarMetodo($config, $dadosTrabalho);

        if ((int) $config['exige_telefone_contato'] === 1 && empty($dadosTrabalho['telefone_contato'])) {
            throw new \RuntimeException('Informe um telefone para contato.');
        }

        $totalAutores = 1 + count($coautores);

        if ($totalAutores > (int) $config['quantidade_maxima_autores']) {
            throw new \RuntimeException('Quantidade de autores acima do limite permitido para este evento (' . (int) $config['quantidade_maxima_autores'] . ').');
        }

        if (!CpfValidador::valido($dadosAutorPrincipal['cpf'])) {
            throw new \RuntimeException('CPF do autor principal inválido.');
        }

        foreach ($coautores as $coautor) {
            if (!CpfValidador::valido($coautor['cpf'])) {
                throw new \RuntimeException('CPF de um dos coautores inválido.');
            }
        }

        // Fase 49B, achado do teste de fumaça (item 6.b): exclusão mútua
        // autor/avaliador dentro do mesmo evento, na outra direção - quem
        // já é avaliador avulso ATIVO deste evento não pode submeter um
        // trabalho nele. Checa autor principal e cada coautor, por e-mail.
        if ($this->avaliadores->emailJaEhAvaliadorAtivo($eventoId, $dadosAutorPrincipal['email'])) {
            throw new \RuntimeException('Este e-mail já está cadastrado como avaliador de Trabalhos deste evento e não pode submeter um trabalho.');
        }

        foreach ($coautores as $coautor) {
            if ($this->avaliadores->emailJaEhAvaliadorAtivo($eventoId, $coautor['email'])) {
                throw new \RuntimeException('O e-mail de um dos coautores já está cadastrado como avaliador de Trabalhos deste evento.');
            }
        }

        $termosParaRegistrar = $this->validarTermos($eventoId, $termosAceitos);

        $conteudo = $this->validarConteudo($config, $dadosTrabalho, $arquivosEnviados);

        $pdo = Database::conexao();
        $pdo->beginTransaction();

        try {
            $configTravada = $this->config->buscarPorEventoParaAtualizar($eventoId);

            if ((int) $configTravada['permite_multiplos_trabalhos_por_pessoa'] === 0) {
                $this->checarDuplicidade($eventoId, $dadosAutorPrincipal, $coautores);
            }

            $trabalhoId = $this->trabalhos->criar([
                'evento_id' => $eventoId,
                'eixo_tematico_id' => !empty($dadosTrabalho['eixo_tematico_id']) ? $dadosTrabalho['eixo_tematico_id'] : null,
                'natureza_id' => !empty($dadosTrabalho['natureza_id']) ? $dadosTrabalho['natureza_id'] : null,
                'titulo' => $dadosTrabalho['titulo'],
                'telefone_contato' => !empty($dadosTrabalho['telefone_contato']) ? $dadosTrabalho['telefone_contato'] : null,
                'metodo_submissao' => $dadosTrabalho['metodo_submissao'],
                'conteudo_html' => isset($conteudo['conteudo_html']) ? $conteudo['conteudo_html'] : null,
                'link_avaliacao' => isset($conteudo['link_avaliacao']) ? $conteudo['link_avaliacao'] : null,
                'link_publicacao' => isset($conteudo['link_publicacao']) ? $conteudo['link_publicacao'] : null,
            ]);

            if (isset($conteudo['arquivo_avaliacao'])) {
                $caminhoAvaliacao = TrabalhoArquivoValidador::salvar($conteudo['arquivo_avaliacao'], $conteudo['extensao_avaliacao'], $trabalhoId);
                $caminhoPublicacao = null;

                if (isset($conteudo['arquivo_publicacao'])) {
                    $caminhoPublicacao = TrabalhoArquivoValidador::salvar($conteudo['arquivo_publicacao'], $conteudo['extensao_publicacao'], $trabalhoId);
                }

                $this->trabalhos->atualizarArquivos($trabalhoId, $caminhoAvaliacao, $caminhoPublicacao);
            }

            $cpfAutorPrincipal = CpfValidador::apenasDigitos($dadosAutorPrincipal['cpf']);
            $cargoAutorPrincipal = !empty($dadosAutorPrincipal['cargo']) ? $dadosAutorPrincipal['cargo'] : null;
            $orgaoOrigemAutorPrincipal = !empty($dadosAutorPrincipal['orgao_origem']) ? $dadosAutorPrincipal['orgao_origem'] : null;

            $this->autores->inserir(
                $trabalhoId,
                true,
                $usuarioId,
                $dadosAutorPrincipal['nome'],
                $cpfAutorPrincipal,
                $dadosAutorPrincipal['email'],
                $cargoAutorPrincipal,
                $orgaoOrigemAutorPrincipal
            );

            // Fase 49B: o autor principal SEMPRE tem conta - CPF/cargo/
            // órgão de origem informados aqui sincronizam de volta com
            // usuarios_perfil (fonte única da pessoa), fechando o ciclo
            // documento/inscrição/submissão em vez de deixar uma cópia
            // solta só em trabalho_autores.
            $camposPerfilAutor = ['documento' => $cpfAutorPrincipal, 'tipo_documento' => 'CPF'];

            if ($cargoAutorPrincipal !== null) {
                $camposPerfilAutor['cargo'] = $cargoAutorPrincipal;
            }

            if ($orgaoOrigemAutorPrincipal !== null) {
                $camposPerfilAutor['orgao_origem'] = $orgaoOrigemAutorPrincipal;
            }

            $this->usuarioPerfil->atualizarParcial($usuarioId, $camposPerfilAutor);

            foreach ($coautores as $coautor) {
                $this->autores->inserir(
                    $trabalhoId,
                    false,
                    null,
                    $coautor['nome'],
                    CpfValidador::apenasDigitos($coautor['cpf']),
                    $coautor['email'],
                    !empty($coautor['cargo']) ? $coautor['cargo'] : null,
                    !empty($coautor['orgao_origem']) ? $coautor['orgao_origem'] : null
                );
            }

            if (!empty($termosParaRegistrar)) {
                $this->aceites->registrar($trabalhoId, $termosParaRegistrar);
            }

            $this->garantirPerfilInscrito($usuarioId);

            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();

            throw $e;
        }

        return $trabalhoId;
    }

    /**
     * Fase 51: confere os termos de aceite do evento contra o que foi
     * marcado no formulario e devolve, ja pronto, o que sera gravado como
     * aceite (rotulo e texto do momento, copia congelada). Evento sem termo
     * cadastrado simplesmente nao exige nada: o mecanismo e' opcional, como
     * todo catalogo por evento neste sistema.
     */
    private function validarTermos($eventoId, array $termosAceitos)
    {
        $ativos = $this->termos->listarAtivos($eventoId);

        if (empty($ativos)) {
            return [];
        }

        $marcados = array_map('intval', $termosAceitos);
        $registrar = [];

        foreach ($ativos as $termo) {
            $foiMarcado = in_array((int) $termo['id'], $marcados, true);

            if (!$foiMarcado) {
                if ((int) $termo['obrigatorio'] === 1) {
                    throw new \RuntimeException('É necessário aceitar: ' . $termo['rotulo']);
                }

                continue;
            }

            $registrar[] = [
                'termo_id' => (int) $termo['id'],
                'rotulo' => $termo['rotulo'],
                'texto_html' => $termo['texto_html'],
                'origem' => 'sistema',
            ];
        }

        return $registrar;
    }

    /**
     * Metodo "formulario direto" usa um campo de texto simples (textarea),
     * nao um editor rico tipo WYSIWYG: reaproveitar _editor_rico.php
     * (assets/js/editor-rico.js) exigiria confiar em HTML digitado por um
     * participante externo qualquer, quebrando o pressuposto de seguranca
     * daquele componente (so' e' seguro porque so' Administrador grava
     * nele hoje) e ainda ofereceria botoes (imagem, cor, fonte) que essa
     * submissao nao suporta. Em vez disso, o texto digitado e' sempre
     * escapado primeiro (nenhuma tag do participante sobrevive) e so'
     * depois reconstruido em paragrafos/quebras de linha - nao ha como uma
     * tag maliciosa passar, porque a reconstrucao nunca le tag nenhuma do
     * texto original.
     */
    private function textoParaHtmlSeguro($texto)
    {
        $texto = str_replace(["\r\n", "\r"], "\n", trim($texto));
        $paragrafos = preg_split('/\n{2,}/', $texto);

        $html = '';
        foreach ($paragrafos as $paragrafo) {
            $paragrafo = trim($paragrafo);

            if ($paragrafo === '') {
                continue;
            }

            $escapado = htmlspecialchars($paragrafo, ENT_QUOTES, 'UTF-8');
            $html .= '<p>' . nl2br($escapado) . '</p>';
        }

        return $html;
    }

    private function validarPrazo(array $config)
    {
        $agora = date('Y-m-d H:i:s');

        if ($config['data_abertura_submissao'] !== null && $agora < $config['data_abertura_submissao']) {
            throw new \RuntimeException('O prazo de submissão de trabalhos ainda não começou.');
        }

        if ($config['data_fim_submissao'] !== null && $agora > $config['data_fim_submissao']) {
            throw new \RuntimeException('O prazo de submissão de trabalhos já terminou.');
        }
    }

    private function validarMetodo(array $config, array $dadosTrabalho)
    {
        $metodosHabilitados = $config['metodos_submissao_json'] !== null ? json_decode($config['metodos_submissao_json'], true) : [];

        if (empty($dadosTrabalho['metodo_submissao']) || !in_array($dadosTrabalho['metodo_submissao'], (array) $metodosHabilitados, true)) {
            throw new \RuntimeException('Método de submissão inválido ou não habilitado para este evento.');
        }
    }

    /**
     * So' chamar dentro da transacao com FOR UPDATE em
     * evento_trabalhos_config (buscarPorEventoParaAtualizar() ja' chamado
     * pelo metodo publico antes deste).
     */
    private function checarDuplicidade($eventoId, array $dadosAutorPrincipal, array $coautores)
    {
        $cpfPrincipal = CpfValidador::apenasDigitos($dadosAutorPrincipal['cpf']);

        if ($this->autores->cpfJaExisteNoEvento($eventoId, $cpfPrincipal)) {
            throw new \RuntimeException('Este CPF já consta em outro trabalho submetido neste evento.');
        }

        foreach ($coautores as $coautor) {
            $cpfCoautor = CpfValidador::apenasDigitos($coautor['cpf']);

            if ($this->autores->cpfJaExisteNoEvento($eventoId, $cpfCoautor)) {
                throw new \RuntimeException('O CPF de um dos coautores já consta em outro trabalho submetido neste evento.');
            }
        }
    }

    /**
     * Valida o conteudo conforme o metodo escolhido, ANTES de abrir a
     * transacao (e' entrada/saida de arquivo, nao deve ficar presa numa
     * transacao de banco). Devolve os dados ja' validados/sanitizados,
     * prontos para gravar.
     */
    private function validarConteudo(array $config, array $dadosTrabalho, array $arquivosEnviados)
    {
        $sigiloCego = (int) $config['sigilo_cego'] === 1;
        $metodo = $dadosTrabalho['metodo_submissao'];

        if ($metodo === 'formulario') {
            if (empty($dadosTrabalho['conteudo_html'])) {
                throw new \RuntimeException('Informe o conteúdo do trabalho.');
            }

            return ['conteudo_html' => $this->textoParaHtmlSeguro($dadosTrabalho['conteudo_html'])];
        }

        if ($metodo === 'link_externo') {
            if (empty($dadosTrabalho['link_avaliacao']) || !linkHttpValido($dadosTrabalho['link_avaliacao'])) {
                throw new \RuntimeException('Informe, no campo do arquivo sem identificação, um endereço eletrônico válido, começando com http:// ou https://.');
            }

            $resultado = ['link_avaliacao' => $dadosTrabalho['link_avaliacao']];

            if ($sigiloCego) {
                if (empty($dadosTrabalho['link_publicacao']) || !linkHttpValido($dadosTrabalho['link_publicacao'])) {
                    throw new \RuntimeException('Informe o endereço eletrônico da versão completa (identificada), começando com http:// ou https://.');
                }

                $resultado['link_publicacao'] = $dadosTrabalho['link_publicacao'];
            }

            return $resultado;
        }

        if ($metodo === 'documento_editavel' || $metodo === 'documento_nao_editavel') {
            $extensoesPermitidas = $metodo === 'documento_nao_editavel'
                ? ['pdf']
                : $this->config->extensoesEditavelHabilitadas($config['evento_id']);

            $limiteBytes = (int) $config['tamanho_maximo_mb'] * 1024 * 1024;

            if (!isset($arquivosEnviados['arquivo_avaliacao'])) {
                throw new \RuntimeException('O campo para envio do arquivo "sem identificação" está vazio.');
            }

            $validacaoAvaliacao = TrabalhoArquivoValidador::validar($arquivosEnviados['arquivo_avaliacao'], $extensoesPermitidas, $limiteBytes);

            if (!$validacaoAvaliacao['valido']) {
                throw new \RuntimeException('Arquivo sem identificação: ' . $validacaoAvaliacao['mensagem']);
            }

            $resultado = [
                'arquivo_avaliacao' => $arquivosEnviados['arquivo_avaliacao'],
                'extensao_avaliacao' => $validacaoAvaliacao['extensao'],
            ];

            if ($sigiloCego) {
                if (!isset($arquivosEnviados['arquivo_publicacao'])) {
                    throw new \RuntimeException('O campo para envio do arquivo da "versão completa (identificada)" está vazio.');
                }

                $validacaoPublicacao = TrabalhoArquivoValidador::validar($arquivosEnviados['arquivo_publicacao'], $extensoesPermitidas, $limiteBytes);

                if (!$validacaoPublicacao['valido']) {
                    throw new \RuntimeException('Versão completa (identificada): ' . $validacaoPublicacao['mensagem']);
                }

                $resultado['arquivo_publicacao'] = $arquivosEnviados['arquivo_publicacao'];
                $resultado['extensao_publicacao'] = $validacaoPublicacao['extensao'];
            }

            return $resultado;
        }

        throw new \RuntimeException('Método de submissão inválido.');
    }

    /**
     * Mesmo mecanismo ja usado em AuthService::cadastrarInscrito() e em
     * EventoAtividadeFacilitadorRepository::garantirPerfilInscrito() (Fase
     * 48) - so' para Auth::destinoPainel() reconhecer a pessoa e o
     * roteamento do app funcionar (ver correcao de roteamento no plano
     * desta fase).
     */
    private function garantirPerfilInscrito($usuarioId)
    {
        $perfilInscrito = $this->perfis->buscarPorChave('inscrito');

        if ($perfilInscrito === null) {
            return;
        }

        if (!$this->perfis->possuiPerfil($usuarioId, $perfilInscrito['id'], null)) {
            $this->perfis->atribuir($usuarioId, $perfilInscrito['id'], null);
        }
    }
}
