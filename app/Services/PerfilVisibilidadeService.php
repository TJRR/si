<?php

namespace App\Services;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Repositories\UsuarioPerfilRepository;

/**
 * Fase 55: decide o que uma pessoa mostra a outra depois de uma conexao.
 *
 * Fonte unica dessa decisao, de proposito: a consulta traz as colunas cruas
 * mais as seis marcas, e so' aqui se resolve o que aparece. Filtrar no SQL
 * espalharia a regra por dois lugares e, no dia em que um campo novo
 * entrasse, um deles ficaria para tras.
 *
 * Nome e e-mail saem sempre (decisao do dono). Os demais campos so' com a
 * marca correspondente ligada, lida no momento da exibicao e nunca copiada
 * para a conexao: desligar uma marca esconde o dado tambem das conexoes ja'
 * feitas.
 *
 * Endereco de rede social e' conteudo digitado pela propria pessoa e
 * mostrado a terceiros, o mesmo risco do endereco do trabalho na Fase 49:
 * passa por linkHttpValido() aqui de novo, mesmo ja tendo passado na
 * gravacao, para que linha antiga ou gravada por outro caminho nunca vire
 * endereco clicavel.
 */
class PerfilVisibilidadeService
{
    /**
     * Recebe a linha crua de ConexaoRepository::listarDaInscricao() e
     * devolve so' o que pode ser exibido, ja com os enderecos de acesso
     * direto montados. Telefone ou endereco que nao dao para interpretar
     * voltam sem acesso direto, nunca como endereco quebrado.
     */
    public function paraExibicao(array $linha)
    {
        $exibicao = [
            'nome' => (string) $linha['usuario_nome'],
            'email' => (string) $linha['usuario_email'],
            'foto_path' => null,
            'cargo' => null,
            'orgao_origem' => null,
            'minicurriculo' => null,
            'telefone' => null,
            'telefone_discagem' => null,
            'telefone_whatsapp' => null,
            'redes_sociais' => [],
        ];

        if (!empty($linha['mostrar_foto']) && !empty($linha['foto_path'])) {
            $exibicao['foto_path'] = (string) $linha['foto_path'];
        }

        if (!empty($linha['mostrar_cargo']) && !empty($linha['cargo'])) {
            $exibicao['cargo'] = (string) $linha['cargo'];
        }

        if (!empty($linha['mostrar_orgao_origem']) && !empty($linha['orgao_origem'])) {
            $exibicao['orgao_origem'] = (string) $linha['orgao_origem'];
        }

        if (!empty($linha['mostrar_minicurriculo']) && !empty($linha['minicurriculo'])) {
            $exibicao['minicurriculo'] = (string) $linha['minicurriculo'];
        }

        if (!empty($linha['mostrar_telefone']) && !empty($linha['telefone'])) {
            $numero = (string) $linha['telefone'];
            $formatado = formatarTelefoneBr($numero);

            $exibicao['telefone'] = $formatado !== null ? $formatado : $numero;
            $exibicao['telefone_discagem'] = linkTelefone($numero);

            if (!empty($linha['telefone_whatsapp'])) {
                $exibicao['telefone_whatsapp'] = linkWhatsApp($numero);
            }
        }

        if (!empty($linha['mostrar_redes_sociais'])) {
            $exibicao['redes_sociais'] = $this->redesExibiveis($linha);
        }

        return $exibicao;
    }

    /**
     * Mapa rede -> ['rotulo' => ..., 'endereco' => ...], so' com as cinco
     * redes suportadas e so' com endereco que comeca em http:// ou https://
     * (e' o que barra entrada do tipo javascript:, mesma checagem que a Fase
     * 24 criou e a Fase 49 exigiu no endereco do trabalho).
     */
    private function redesExibiveis(array $linha)
    {
        $perfil = ['redes_sociais' => isset($linha['redes_sociais']) ? $linha['redes_sociais'] : null];
        $cadastradas = (new UsuarioPerfilRepository())->redesSociais($perfil);
        $rotulos = UsuarioPerfilRepository::REDES_ROTULOS;
        $redes = [];

        foreach ($cadastradas as $rede => $endereco) {
            if (!linkHttpValido($endereco)) {
                continue;
            }

            $redes[$rede] = [
                'rotulo' => isset($rotulos[$rede]) ? $rotulos[$rede] : $rede,
                'endereco' => $endereco,
            ];
        }

        return $redes;
    }
}
