ALTER TABLE formulas_pontuacao
    MODIFY COLUMN template_codigo ENUM(
        'media_ponderada_criterios',
        'soma_ponderada_etapas',
        'media_aritmetica',
        'media_fontes_ponderadas'
    ) NOT NULL;
