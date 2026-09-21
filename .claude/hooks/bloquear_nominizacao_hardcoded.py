#!/usr/bin/env python3
"""
Hook de PROJETO (PreToolUse, Write|Edit): bloqueia "TJRR"/"NPI" escritos
diretamente no codigo do sistema NPI/si, fora de comentario PHP e fora de
dominios/e-mails (tjrr.jus.br, npi@...). O projeto tem os helpers PHP
nomeInstituicao() e nomeUnidadeResponsavel() (app/helpers.php) para isso -
nominizacao fixa no codigo quebra o motor generico configuravel do sistema.
"""
import json
import re
import sys

ARQUIVO_RE = re.compile(
    r'(app/Views/|app/Ajuda/|app/Services/|app/Controllers/).*\.php$'
)
TJRR_RE = re.compile(r'\bTJRR\b')
NPI_RE = re.compile(r'\bNPI\b')
DOMINIO_RE = re.compile(r'(tjrr\.jus\.br|npi@|@tjrr)', re.IGNORECASE)


def remover_comentarios(texto):
    sem = re.sub(r'/\*.*?\*/', '', texto, flags=re.S)
    sem = re.sub(r'<!--.*?-->', '', sem, flags=re.S)
    sem = re.sub(r'//[^\n]*', '', sem)
    return sem


def linhas_suspeitas(texto, padrao):
    achados = []
    for linha in texto.split("\n"):
        if padrao.search(linha) and not DOMINIO_RE.search(linha):
            achados.append(linha.strip())
    return achados


def main():
    try:
        dados = json.load(sys.stdin)
    except Exception:
        print(json.dumps({"continue": True}))
        return

    entrada = dados.get("tool_input", {})
    caminho = entrada.get("file_path", "") or ""

    if not ARQUIVO_RE.search(caminho.replace("\\", "/")):
        print(json.dumps({"continue": True}))
        return

    texto = entrada.get("content") or entrada.get("new_string") or ""
    texto_sem_comentarios = remover_comentarios(texto)

    achados_tjrr = linhas_suspeitas(texto_sem_comentarios, TJRR_RE)
    achados_npi = linhas_suspeitas(texto_sem_comentarios, NPI_RE)

    if achados_tjrr or achados_npi:
        termo = "TJRR" if achados_tjrr else "NPI"
        helper = "nomeInstituicao()" if achados_tjrr else "nomeUnidadeResponsavel()"
        print(json.dumps({
            "hookSpecificOutput": {
                "hookEventName": "PreToolUse",
                "permissionDecision": "deny",
                "permissionDecisionReason": (
                    f"Nominizacao hardcoded \"{termo}\" detectada fora de comentario/dominio "
                    f"em {caminho}. Use o helper PHP {helper} (ver app/helpers.php) em vez de "
                    "escrever o nome fixo - o projeto e' um motor generico configuravel via "
                    "Configuracoes > Identidade institucional."
                ),
            }
        }))
    else:
        print(json.dumps({"continue": True}))


if __name__ == "__main__":
    main()
