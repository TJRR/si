<?php

namespace App\Services;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Repositories\EventoModeloAvisoRepository;

/**
 * Textos editaveis dos oito avisos por correio eletronico exclusivos do
 * Evento. Os avisos do Concurso nao passam por aqui.
 *
 * O resolvedor de palavras-chave e' copia do de ModeloDocumentoService, com
 * dicionario proprio por aviso: o arquivo do Concurso nao muda. Palavra do
 * tipo 'texto' sai escapada; a do tipo 'html' entra como veio, porque e'
 * montada pelo proprio sistema ou ja foi saneada na gravacao.
 */
class EventoModeloAvisoService
{
    const ASSUNTO_MAXIMO = 200;

    const AVISOS = [
        'confirmacao_inscricao_evento' => [
            'nome' => 'Confirmação de inscrição no evento',
            'quando' => 'Quando a pessoa conclui a inscrição pelo formulário público do evento.',
            'palavras' => [
                'nome' => ['texto', 'Nome de quem se inscreveu'],
                'evento' => ['texto', 'Nome do evento'],
                'evento_inicio' => ['texto', 'Data de início do evento'],
                'evento_fim' => ['texto', 'Data de término do evento'],
                'mensagem_do_evento' => ['html', 'Mensagem de confirmação escrita nos Dados Gerais do evento, ou o texto padrão quando ela estiver vazia'],
            ],
            'obrigatorias' => [],
            'assunto' => 'Inscrição confirmada: [[evento]]',
            'corpo' => '<p>Olá, [[nome]],</p><p>Sua inscrição em <strong>[[evento]]</strong> ([[evento_inicio]] a [[evento_fim]]) foi confirmada.</p>[[mensagem_do_evento]]',
        ],
        'convite_avaliador_trabalhos' => [
            'nome' => 'Convite de avaliador de trabalhos, conta nova',
            'quando' => 'Quando a organização convida para avaliar trabalhos alguém que ainda não tem acesso ao sistema.',
            'palavras' => [
                'nome' => ['texto', 'Nome de quem foi convidado'],
                'evento' => ['texto', 'Nome do evento'],
                'link_definir_senha' => ['html', 'Ligação "Definir minha senha"'],
                'endereco_definir_senha' => ['texto', 'Endereço de definir a senha, escrito por extenso'],
                'link_entrar_google' => ['html', 'Ligação "Entrar com Google"'],
            ],
            'obrigatorias' => [['link_definir_senha', 'endereco_definir_senha']],
            'assunto' => 'Convite para avaliar trabalhos: [[evento]]',
            'corpo' => '<p>Olá, [[nome]],</p><p>Você foi convidado a avaliar trabalhos submetidos ao evento "[[evento]]".</p><p>Você já pode acessar o sistema de duas formas:</p><ul><li>🔵 Se este endereço de e-mail for de uma conta Google, clique em [[link_entrar_google]]; ou</li><li>🔑 Clicando em [[link_definir_senha]] e entrando com este e-mail e uma senha que você deverá definir.</li></ul><p style="color:#555;font-size:0.9em;">Este e-mail foi enviado automaticamente. Não compartilhe sua senha com terceiros. Em caso de dúvida sobre a autenticidade deste e-mail, entre em contato pelos canais abaixo.</p><p>Atenciosamente,</p>',
        ],
        'convite_avaliador_trabalhos_conta_existente' => [
            'nome' => 'Convite de avaliador de trabalhos, conta existente',
            'quando' => 'Quando a organização convida para avaliar trabalhos alguém que já usa o sistema.',
            'palavras' => [
                'nome' => ['texto', 'Nome de quem foi convidado'],
                'evento' => ['texto', 'Nome do evento'],
                'link_entrar' => ['html', 'Ligação "Entrar no sistema"'],
            ],
            'obrigatorias' => [],
            'assunto' => 'Convite para avaliar trabalhos: [[evento]]',
            'corpo' => '<p>Olá, [[nome]],</p><p>Você foi convidado a avaliar trabalhos submetidos ao evento "[[evento]]". Como você já tem conta neste sistema, não é preciso se cadastrar de novo: acesse normalmente com o e-mail e a senha que já usa (ou com sua conta Google, se for assim que costuma entrar).</p><p>[[link_entrar]]</p><p style="color:#555;font-size:0.9em;">Este e-mail foi enviado automaticamente. Em caso de dúvida sobre a autenticidade deste e-mail, entre em contato pelos canais abaixo.</p><p>Atenciosamente,</p>',
        ],
        'convite_representante_estande' => [
            'nome' => 'Convite de representante de estande, conta nova',
            'quando' => 'Quando a organização indica como representante de um estande alguém que ainda não tem acesso ao sistema.',
            'palavras' => [
                'nome' => ['texto', 'Nome do representante'],
                'evento' => ['texto', 'Nome do evento'],
                'estande' => ['texto', 'Nome do estande'],
                'mensagem_do_evento' => ['html', 'Texto do convite escrito em Estandes, Configurações'],
                'link_definir_senha' => ['html', 'Ligação "Definir minha senha"'],
                'endereco_definir_senha' => ['texto', 'Endereço de definir a senha, escrito por extenso'],
                'link_entrar_google' => ['html', 'Ligação "Entrar com Google"'],
            ],
            'obrigatorias' => [['link_definir_senha', 'endereco_definir_senha']],
            'assunto' => 'Representante de estande: [[evento]]',
            'corpo' => '<p>Olá, [[nome]],</p><p>Você foi indicado(a) como representante do estande "[[estande]]" no evento "[[evento]]". Pelo sistema, você atualiza o nome, a descrição e o logotipo do estande, baixa o cartaz com o código que os participantes leem para registrar a visita e acompanha quantas visitas o estande recebeu.</p>[[mensagem_do_evento]]<p>Você já pode acessar o sistema de duas formas:</p><ul><li>🔵 Se este endereço de e-mail for de uma conta Google, clique em [[link_entrar_google]]; ou</li><li>🔑 Clicando em [[link_definir_senha]] e entrando com este e-mail e uma senha que você deverá definir.</li></ul><p style="color:#555;font-size:0.9em;">Este e-mail foi enviado automaticamente. Não compartilhe sua senha com terceiros. Em caso de dúvida sobre a autenticidade deste e-mail, entre em contato pelos canais abaixo.</p><p>Atenciosamente,</p>',
        ],
        'convite_representante_estande_conta_existente' => [
            'nome' => 'Convite de representante de estande, conta existente',
            'quando' => 'Quando a organização indica como representante de um estande alguém que já usa o sistema.',
            'palavras' => [
                'nome' => ['texto', 'Nome do representante'],
                'evento' => ['texto', 'Nome do evento'],
                'estande' => ['texto', 'Nome do estande'],
                'mensagem_do_evento' => ['html', 'Texto do convite escrito em Estandes, Configurações'],
                'link_entrar' => ['html', 'Ligação "Entrar no sistema"'],
            ],
            'obrigatorias' => [],
            'assunto' => 'Representante de estande: [[evento]]',
            'corpo' => '<p>Olá, [[nome]],</p><p>Você foi indicado(a) como representante do estande "[[estande]]" no evento "[[evento]]". Pelo sistema, você atualiza o nome, a descrição e o logotipo do estande, baixa o cartaz com o código que os participantes leem para registrar a visita e acompanha quantas visitas o estande recebeu.</p>[[mensagem_do_evento]]<p>Como você já tem conta neste sistema, não é preciso se cadastrar de novo: acesse normalmente com o e-mail e a senha que já usa (ou com sua conta Google, se for assim que costuma entrar).</p><p>[[link_entrar]]</p><p style="color:#555;font-size:0.9em;">Este e-mail foi enviado automaticamente. Não compartilhe sua senha com terceiros. Em caso de dúvida sobre a autenticidade deste e-mail, entre em contato pelos canais abaixo.</p><p>Atenciosamente,</p>',
        ],
        'convite_autor_trabalho_importado' => [
            'nome' => 'Trabalho registrado pela organização, conta nova',
            'quando' => 'Quando a organização traz para o sistema um trabalho recebido por canal alternativo e o autor ainda não tinha acesso.',
            'palavras' => [
                'nome' => ['texto', 'Nome do autor'],
                'evento' => ['texto', 'Nome do evento'],
                'link_definir_senha' => ['html', 'Ligação "Definir minha senha"'],
                'endereco_definir_senha' => ['texto', 'Endereço de definir a senha, escrito por extenso'],
                'link_entrar_google' => ['html', 'Ligação "Entrar com Google"'],
            ],
            'obrigatorias' => [['link_definir_senha', 'endereco_definir_senha']],
            'assunto' => 'Seu trabalho foi registrado: [[evento]]',
            'corpo' => '<p>Olá, [[nome]],</p><p>O trabalho que você enviou para o evento "[[evento]]" foi registrado no sistema oficial do evento. Para acompanhar a situação dele, defina sua senha de acesso no endereço abaixo.</p><p>Você já pode acessar o sistema de duas formas:</p><ul><li>🔵 Se este endereço de e-mail for de uma conta Google, clique em [[link_entrar_google]]; ou</li><li>🔑 Clicando em [[link_definir_senha]] e entrando com este e-mail e uma senha que você deverá definir.</li></ul><p style="color:#555;font-size:0.9em;">Este e-mail foi enviado automaticamente. Não compartilhe sua senha com terceiros. Em caso de dúvida sobre a autenticidade deste e-mail, entre em contato pelos canais abaixo.</p><p>Atenciosamente,</p>',
        ],
        'recebimento_trabalho' => [
            'nome' => 'Recebimento de trabalho',
            'quando' => 'Quando um trabalho é submetido: sai para o autor principal e para cada coautor.',
            'palavras' => [
                'nome' => ['texto', 'Nome de quem recebe o aviso'],
                'evento' => ['texto', 'Nome do evento'],
                'situacao' => ['texto', '"Trabalho recebido" ou "Trabalho recebido e inscrição registrada", conforme a pessoa tenha ficado inscrita no evento'],
                'titulo_trabalho' => ['texto', 'Título do trabalho'],
                'protocolo' => ['texto', 'Número do protocolo'],
                'recebido_em' => ['texto', 'Data e hora do recebimento'],
                'autor_principal' => ['texto', 'Nome do autor principal'],
                'paragrafo_autoria' => ['html', 'Parágrafo que diz quem enviou o trabalho, diferente para o autor principal e para o coautor'],
                'paragrafo_inscricao' => ['html', 'Parágrafo da inscrição no evento, quando houver; vazio quando a pessoa não ficou inscrita'],
                'paragrafo_acesso' => ['html', 'Parágrafo com o endereço de definir a senha (conta nova) ou de acompanhar o trabalho (conta existente)'],
                'mensagem_do_evento' => ['html', 'Texto do e-mail de recebimento escrito em Trabalhos, Configurações, ou o texto padrão quando ele estiver vazio'],
            ],
            'obrigatorias' => [['paragrafo_acesso']],
            'assunto' => '[[situacao]]: [[evento]]',
            'corpo' => '<p>Olá, [[nome]],</p>[[paragrafo_autoria]]<p>Protocolo: <strong>nº [[protocolo]]</strong>. Recebido em [[recebido_em]].</p>[[paragrafo_inscricao]][[paragrafo_acesso]][[mensagem_do_evento]]',
        ],
        'aviso_autor_trabalho_importado' => [
            'nome' => 'Trabalho registrado pela organização, conta existente',
            'quando' => 'Quando a organização traz para o sistema um trabalho recebido por canal alternativo e o autor já usava o sistema.',
            'palavras' => [
                'nome' => ['texto', 'Nome do autor'],
                'evento' => ['texto', 'Nome do evento'],
                'link_entrar' => ['html', 'Ligação "Entrar no sistema"'],
            ],
            'obrigatorias' => [],
            'assunto' => 'Seu trabalho foi registrado: [[evento]]',
            'corpo' => '<p>Olá, [[nome]],</p><p>O trabalho que você enviou para o evento "[[evento]]" foi registrado no sistema oficial do evento. Como você já tem conta neste sistema, acesse normalmente com o e-mail e a senha que já usa (ou com sua conta Google, se for assim que costuma entrar) para acompanhar a situação do trabalho.</p><p>[[link_entrar]]</p><p style="color:#555;font-size:0.9em;">Este e-mail foi enviado automaticamente. Em caso de dúvida sobre a autenticidade deste e-mail, entre em contato pelos canais abaixo.</p><p>Atenciosamente,</p>',
        ],
    ];

    private $modelos;

    public function __construct()
    {
        $this->modelos = new EventoModeloAvisoRepository();
    }

    public static function existe($chave)
    {
        return isset(self::AVISOS[$chave]);
    }

    /**
     * Assunto e corpo do modelo gravado, com as palavras-chave resolvidas, ou
     * null quando nao ha modelo. Qualquer falha de leitura tambem devolve
     * null: o aviso nunca deixa de sair por causa do modelo.
     */
    public function aplicar($chave, array $dados)
    {
        if (!self::existe($chave)) {
            return null;
        }

        try {
            $modelo = $this->modelos->buscarPorChave($chave);
        } catch (\Throwable $e) {
            error_log('[Avisos do Evento] falha ao ler o modelo ' . $chave . ': ' . $e->getMessage());
            return null;
        }

        if ($modelo === null) {
            return null;
        }

        $assunto = trim(preg_replace('/\s+/', ' ', strip_tags($this->resolver($chave, $modelo['assunto'], $dados, false))));

        return [
            'assunto' => $assunto !== '' ? $assunto : $this->resolver($chave, self::AVISOS[$chave]['assunto'], $dados, false),
            'corpo' => $this->resolver($chave, $modelo['corpo_html'], $dados, true),
        ];
    }

    /**
     * Problemas que impedem gravar o modelo, ja' escritos para a tela.
     */
    public function problemasDoModelo($chave, $assunto, $corpoHtml)
    {
        $problemas = [];
        $aviso = self::AVISOS[$chave];

        if (trim($assunto) === '') {
            $problemas[] = 'Escreva o assunto.';
        } elseif (mb_strlen($assunto) > self::ASSUNTO_MAXIMO) {
            $problemas[] = 'O assunto passa de ' . self::ASSUNTO_MAXIMO . ' caracteres.';
        }

        if (trim(strip_tags($corpoHtml)) === '') {
            $problemas[] = 'Escreva o texto do aviso.';
        }

        $desconhecidas = array_unique(array_merge(
            $this->palavrasDesconhecidas($chave, $assunto),
            $this->palavrasDesconhecidas($chave, $corpoHtml)
        ));

        foreach ($desconhecidas as $palavra) {
            $problemas[] = 'A palavra-chave [[' . $palavra . ']] não existe neste aviso.';
        }

        foreach ($this->palavrasUsadas($assunto) as $palavra) {
            if (isset($aviso['palavras'][$palavra]) && $aviso['palavras'][$palavra][0] === 'html') {
                $problemas[] = 'A palavra-chave [[' . $palavra . ']] não pode ir no assunto, só no texto.';
            }
        }

        $usadasNoCorpo = $this->palavrasUsadas($corpoHtml);

        foreach ($aviso['obrigatorias'] as $alternativas) {
            if (array_intersect($alternativas, $usadasNoCorpo) === []) {
                $problemas[] = 'O texto precisa ter ' . $this->descreverAlternativas($alternativas) . ', senão a pessoa não consegue continuar.';
            }
        }

        return $problemas;
    }

    private function resolver($chave, $texto, array $dados, $comHtml)
    {
        $palavras = self::AVISOS[$chave]['palavras'];

        return preg_replace_callback('/\[\[([a-z0-9_\.]+)\]\]/i', function ($correspondencia) use ($dados, $palavras, $comHtml) {
            $palavra = $correspondencia[1];

            if (!isset($palavras[$palavra]) || !array_key_exists($palavra, $dados)) {
                return '';
            }

            $valor = (string) $dados[$palavra];

            if ($palavras[$palavra][0] === 'html') {
                return $comHtml ? $valor : strip_tags($valor);
            }

            return $comHtml ? htmlspecialchars($valor, ENT_QUOTES, 'UTF-8') : $valor;
        }, (string) $texto);
    }

    private function palavrasUsadas($texto)
    {
        preg_match_all('/\[\[([a-z0-9_\.]+)\]\]/i', (string) $texto, $correspondencias);

        return array_values(array_unique($correspondencias[1]));
    }

    private function palavrasDesconhecidas($chave, $texto)
    {
        return array_values(array_diff($this->palavrasUsadas($texto), array_keys(self::AVISOS[$chave]['palavras'])));
    }

    private function descreverAlternativas(array $alternativas)
    {
        $marcadas = array_map(function ($palavra) {
            return '[[' . $palavra . ']]';
        }, $alternativas);

        return count($marcadas) === 1 ? 'a palavra-chave ' . $marcadas[0] : 'uma destas palavras-chave: ' . implode(' ou ', $marcadas);
    }
}
