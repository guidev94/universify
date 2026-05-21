# Spec: CRM Centralizado - Mautic

## 1. Responsabilidade

O Mautic sera a base central de marketing e qualificacao de leads. Ele deve armazenar contatos, segmentar leads, calcular score, controlar temperatura, executar campanhas e enviar leads qualificados para o LetsWork CRM.

## 2. Dependencias Externas

| Sistema | Dependencia |
|---|---|
| Roboze | Enviar contatos e eventos LinkedIn para o Mautic |
| LetsWork CRM | Receber leads qualificados e retornar eventos comerciais |
| Extensao Chrome | Indiretamente, via LetsWork CRM, receber eventos de reuniao transcrita |

## 3. Entregaveis

- Campos customizados no Mautic.
- Tags padronizadas.
- Segmentos operacionais.
- Score de interesse.
- Temperatura do lead.
- Fila de leads incompletos.
- Campanhas de nutricao e reativacao.
- Envio manual de leads para o LetsWork CRM com selecao de responsavel.
- Recebimento de eventos comerciais retornados pelo LetsWork CRM.

## 4. Tarefas

### M-001: Criar Campos Customizados

Prioridade: P0

Pode executar em paralelo com: LW-001, R-001, C-001

Dependencias: nenhuma

Descricao: criar todos os campos customizados necessarios no Mautic, pois nao existem campos customizados relevantes hoje.

Campos de identificacao:

- `firstname`
- `lastname`
- `company`
- `cargo`
- `segmento`
- `linkedin_url`
- `lead_incompleto`
- `motivo_lead_incompleto`

Campos de origem:

- `origem_principal`
- `origens`
- `campanha_origem`
- `data_primeira_origem`
- `data_ultima_origem`

Campos de LinkedIn:

- `linkedin_status`
- `linkedin_ultima_interacao`
- `linkedin_respondeu`
- `linkedin_ultima_mensagem`
- `linkedin_ultima_resposta`
- `linkedin_intencao`

Campos de scoring e qualificacao:

- `icp_score`
- `interesse_score`
- `temperatura`
- `modulo_interesse`
- `dor_principal`
- `dor_identificada`
- `objecao_identificada`
- `proxima_acao`

Campos comerciais:

- `status_comercial`
- `responsavel_marketing`
- `responsavel_comercial`
- `responsavel_recebimento_lead`
- `data_ultima_interacao`
- `data_proximo_followup`
- `motivo_perda`
- `valor_potencial`
- `ultima_reuniao_resumo`
- `ultima_reuniao_dores`
- `ultima_reuniao_objecoes`
- `ultima_reuniao_proximos_passos`
- `ultima_reuniao_transcricao_url`

Criterios de aceite:

- Todos os campos existem no Mautic.
- Campos ficam disponiveis para segmentacao ou automacao quando aplicavel.
- Campos de data, booleano e texto longo usam tipos adequados.

### M-002: Criar Tags Padronizadas

Prioridade: P0

Pode executar em paralelo com: M-001

Dependencias: nenhuma

Tags obrigatorias:

- `origem-linkedin-roboze`
- `origem-site`
- `origem-apollo`
- `origem-lista-importada`
- `lead-incompleto`
- `lead-enriquecimento`
- `interesse-ponto`
- `interesse-banco-horas`
- `interesse-ferias`
- `interesse-documentos`
- `interesse-ats`
- `interesse-avaliacoes-okrs`
- `interesse-timesheet`
- `temperatura-frio`
- `temperatura-morno`
- `temperatura-quente`
- `temperatura-prioritario`

Criterios de aceite:

- Tags seguem nomenclatura unica.
- Tags podem ser usadas em segmentos e campanhas.

### M-003: Criar Segmentos Operacionais

Prioridade: P0

Pode executar em paralelo com: M-004, M-005

Dependencias: M-001, M-002

Segmentos obrigatorios:

- `Prospecção - Nunca houve interação`
- `ICP alto`
- `ICP medio`
- `LinkedIn - novos contatos`
- `LinkedIn - responderam`
- `LinkedIn - frios com bom ICP`
- `Leads incompletos - enriquecimento`
- `Leads frios`
- `Leads mornos`
- `Leads quentes`
- `Pronto para comercial`
- `Reativacao 15 dias`
- `Reativacao 30 dias`
- `Reativacao 60 dias`
- `Perdidos sem motivo informado`
- `Comercio`
- `Engenharia`
- `Alimentacao`
- `Saude`
- `Servicos`
- `RH / DP`
- `Interesse - Ponto eletronico`
- `Interesse - Banco de horas`
- `Interesse - Ferias`
- `Interesse - Documentos e holerites`
- `Interesse - ATS vagas`
- `Interesse - Avaliacoes e OKRs`
- `Interesse - Timesheet e projetos`

Criterios de aceite:

- Segmentos populam automaticamente.
- Lead incompleto nao entra em campanhas comerciais.
- Lead quente entra em `Pronto para comercial`.

### M-004: Configurar Score De Interesse

Prioridade: P0

Pode executar em paralelo com: LW-002, R-002

Dependencias: M-001

Eventos LinkedIn:

| Evento | Pontos |
|---|---:|
| Contato importado do Roboze | +2 |
| Aceitou conexao | +5 |
| Respondeu mensagem | +15 |
| Resposta com dor identificada | +30 |
| Pediu mais informacoes | +40 |
| Aceitou diagnostico/reuniao | +60 |
| Nao respondeu apos 3 abordagens | -10 |
| Disse que nao tem interesse | -40 |

Eventos Mautic:

| Evento | Pontos |
|---|---:|
| Abriu e-mail | +2 |
| Clicou em e-mail | +5 |
| Visitou pagina do produto | +8 |
| Visitou pagina de precos | +20 |
| Preencheu formulario | +30 |
| Baixou material | +15 |
| Respondeu campanha | +25 |
| Sem interacao por 30 dias | -10 |
| Sem interacao por 60 dias | -20 |
| Descadastrou de e-mail | -40 |

Eventos comerciais recebidos do LetsWork:

| Evento | Pontos |
|---|---:|
| Reuniao agendada | +50 |
| Reuniao realizada | +70 |
| Reuniao transcrita com resumo | +10 |
| Dor clara identificada em reuniao | +30 |
| Objecao tratavel identificada em reuniao | +15 |
| Proximo passo com data definida | +40 |
| Proposta enviada | +90 |
| Negociacao ativa | +100 |
| Cliente ganho | +200 |
| Perdido | -100 |

Criterios de aceite:

- `interesse_score` atualiza por evento.
- Eventos repetidos possuem regra para evitar score artificial.
- Score pode ser recalculado a partir do historico.

### M-005: Configurar Temperatura Do Lead

Prioridade: P0

Dependencias: M-004

Tabela:

| Score | Temperatura | Acao |
|---:|---|---|
| 0 a 20 | Frio | Nutricao leve |
| 21 a 60 | Morno | Conteudo + abordagem consultiva |
| 61 a 100 | Quente | Comercial deve agir |
| 100+ | Prioritario | Follow-up imediato |

Criterios de aceite:

- Campo `temperatura` atualiza apos alteracao de score.
- Tags de temperatura ficam sincronizadas.
- Leads ganhos saem de campanhas de prospeccao.
- Leads perdidos podem entrar em reativacao futura.

### M-006: Configurar Fila De Leads Incompletos

Prioridade: P1

Pode executar em paralelo com: M-007, R-003

Dependencias: M-001, M-003

Regras:

- Lead sem e-mail ou telefone recebe `lead_incompleto = sim`.
- `motivo_lead_incompleto` indica se falta e-mail, telefone ou ambos.
- Lead incompleto entra em `Leads incompletos - enriquecimento`.
- Lead incompleto nao entra em campanha comercial.
- Quando e-mail e telefone forem preenchidos, lead sai da fila.

Criterios de aceite:

- Leads incompletos ficam pesquisaveis.
- Operador consegue revisar e completar dados.
- Origem e LinkedIn URL sao preservados.

### M-007: Criar Campanhas De Nutricao

Prioridade: P1

Pode executar em paralelo com: LW-003, R-003, C-002

Dependencias: M-001, M-003, M-004, M-005

Campanhas:

- Lead frio: conteudo educativo, checklist, case, pergunta consultiva, diagnostico, tentativa leve de reuniao.
- Lead morno: case por segmento, checklist avancado, convite para diagnostico, alerta para marketing.
- Reativacao 15 dias.
- Reativacao 30 dias.
- Reativacao 60 dias.

Criterios de aceite:

- Lead incompleto nao entra em campanha comercial.
- Lead quente sai de campanhas frias.
- Campanhas usam segmento e modulo de interesse quando disponivel.
- Campanhas atualizam score por abertura, clique e resposta.

### M-008: Enviar Lead Qualificado Para LetsWork CRM

Prioridade: P1

Pode executar em paralelo com: R-004, C-003

Dependencias: M-001, M-004, M-005, LW-002

Gatilhos elegiveis:

- Temperatura quente.
- Score acima de 60.
- Pediu informacao.
- Aceitou reuniao.
- Visitou pagina de preco.
- Respondeu positivamente no LinkedIn.
- Preencheu formulario de interesse.

Regras:

- Envio manual no MVP.
- Usuario deve selecionar quem recebera o lead.
- Envio registra responsavel escolhido.
- Erros de API ficam visiveis para operador.

Criterios de aceite:

- Lead e criado/atualizado no LetsWork CRM.
- Mautic registra envio e responsavel escolhido.
- Lead nao e enviado duplicado em curto periodo.

### M-009: Receber Eventos Comerciais Do LetsWork CRM

Prioridade: P1

Pode executar em paralelo com: M-007

Dependencias: M-004, LW-003

Eventos:

- Reuniao agendada.
- Reuniao realizada.
- Reuniao transcrita.
- Dor identificada em reuniao.
- Objecao identificada em reuniao.
- Proximo passo definido em reuniao.
- Proposta enviada.
- Negociacao iniciada.
- Ganho.
- Perdido.
- Motivo de perda.
- Sem resposta.
- Follow-up futuro.

Criterios de aceite:

- Evento recebido altera score quando aplicavel.
- Temperatura e recalculada.
- Lead ganho sai de campanhas de prospeccao.
- Lead perdido entra em fluxo adequado.

## 5. Ordem Recomendada

1. M-001, M-002, M-004 em paralelo.
2. M-003 e M-005 apos campos e tags.
3. M-006 apos segmentos.
4. M-007 apos scoring e segmentos.
5. M-008 apos endpoint LetsWork `LW-002`.
6. M-009 apos endpoint LetsWork `LW-003`.

## 6. Criterios De Aceite Da Responsabilidade Mautic

- Campos customizados criados.
- Tags e segmentos criados.
- Score e temperatura funcionando.
- Leads incompletos preservados e isolados em fila.
- Campanhas iniciais criadas.
- Lead quente pode ser enviado para LetsWork CRM com responsavel selecionado.
- Eventos comerciais retornados atualizam score, temperatura e campanhas.
