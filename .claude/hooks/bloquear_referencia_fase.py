#!/usr/bin/env python3
"""
Hook de PROJETO (PreToolUse, Write|Edit): bloqueia referencia a "Fase NN"
em texto visivel ao usuario (app/Views, app/Ajuda) do sistema NPI/si.
Numeracao de fase de desenvolvimento e informacao interna e nao deve
vazar para a interface do usuario final - comentarios PHP continuam
permitidos, pois nao sao exibidos.
"""
import json
import re
import sys

ARQUIVO_RE = re.compile(r'(app/Views/|app/Ajuda/).*\.php$')
FASE_RE = re.compile(r'[Ff]ase\s+\d+')


def remover_comentarios(texto):
    sem = re.sub(r'/\*.*?\*/', '', texto, flags=re.S)
    sem = re.sub(r'<!--.*?-->', '', sem, flags=re.S)
    sem = re.sub(r'//[^\n]*', '', sem)
    return sem


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

    if FASE_RE.search(texto_sem_comentarios):
        print(json.dumps({
            "hookSpecificOutput": {
                "hookEventName": "PreToolUse",
                "permissionDecision": "deny",
                "permissionDecisionReason": (
                    "Referencia a \"Fase NN\" detectada em texto visivel ao usuario "
                    "(app/Views ou app/Ajuda). Isso vaza numeracao interna de fase de "
                    "desenvolvimento para o usuario final do sistema NPI/si - remova ou "
                    "reformule a frase antes de gravar. Comentarios PHP (//, /* */, <!-- -->) "
                    "continuam permitidos."
                ),
            }
        }))
    else:
        print(json.dumps({"continue": True}))


if __name__ == "__main__":
    main()
