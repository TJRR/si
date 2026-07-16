-- Temas/desafios reais do 5º Prêmio de Inovação TJRR (edição 2026), extraídos
-- literalmente dos Editais 12/2026 (Externo) e 13/2026 (Interno).
-- Substitui os 2 temas placeholder criados na Fase 4 ('Acesso à Justiça' /
-- 'Eficiência Operacional'), que não eram dado real de edital.
-- Pré-condição já confirmada: nenhuma equipe aponta para os temas placeholder
-- (equipes.tema_desafio_id IS NOT NULL retorna 0 linhas em npi_si_dev).
--
-- HISTÓRICO (Fase 17): este script já foi executado - a tabela "temas_desafios"
-- citada abaixo foi renomeada para "temas" na migration 055. Não rodar de novo
-- num banco onde a migration 055 já foi aplicada (a tabela não existe mais
-- com este nome); mantido aqui só como registro do que foi feito.

DELETE FROM temas_desafios WHERE id IN (2, 3);

-- Trilha Externa (id=2) - Edital 12/2026
INSERT INTO temas_desafios (trilha_id, nome, descricao_longa, ativo) VALUES
(2, 'Otimização e Celeridade da Prestação Jurisdicional (Tema 1)',
 'Desenvolvimento de soluções tecnológicas orientadas a processos, automação de fluxos de trabalho judiciais e ferramentas baseadas em inteligência artificial ética para triagem processual, minutas de atos e redução da taxa de congestionamento do Tribunal.',
 1),
(2, 'Aprimoramento do Atendimento ao Cidadão e Acesso à Justiça (Tema 2)',
 'Soluções tecnológicas integradas para gestão de canais de atendimento digital, acessibilidade informacional, simplificação da linguagem processual e facilitação das formas alternativas de solução de conflitos de forma interativa e humanizada.',
 1),
(2, 'Eficiência e Sustentabilidade da Gestão Administrativa (Tema 3)',
 'Soluções administrativas inteligentes voltadas para a melhoria de processos internos e gestão das atividades meio do TJRR.',
 1);

-- Trilha Interna (id=3) - Edital 13/2026
INSERT INTO temas_desafios (trilha_id, nome, descricao_longa, ativo) VALUES
(3, 'Processos internos (Tema 1)',
 'Propostas de melhoria organizacional dos processos internos e gestão administrativa, compreendendo a estrutura, sistema hierárquico, processos, recursos financeiros, recursos tecnológicos e corpo funcional.',
 1),
(3, 'Atividade finalística (Tema 2)',
 'Propostas de otimização e celeridade da prestação jurisdicional, compreendendo soluções tecnológicas orientadas a processos, automação de fluxos de trabalho judiciais e ferramentas baseadas em inteligência artificial.',
 1),
(3, 'Cidadania e Justiça Inclusiva (Tema 3)',
 'Compondo a atividade finalística, compreende soluções inovadoras destinadas a facilitar o acesso dos jurisdicionados mais vulneráveis e usuários extremos aos serviços judiciais.',
 1);
