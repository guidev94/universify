# Spec: CRM Centralizado no Mautic

## 1. Objetivo Da Spec

Transformar o PRD `temp/prd-crm-centralizado-mautic.md` em uma especificacao executavel, com tarefas bem definidas, dependencias claras, criterio de aceite e maxima paralelizacao possivel.

## 2. Resultado Esperado

Ao final do MVP, o fluxo deve permitir:

- Roboze enviar contatos e eventos do LinkedIn para o Mautic.
- Mautic centralizar leads, campos customizados, segmentos, scoring e campanhas.
- Mautic enviar leads qualificados para o LetsWork CRM com selecao do responsavel comercial.
- LetsWork CRM receber leads, oportunidades, eventos comerciais e transcricoes de reuniao.
- Extensao Chrome capturar reunioes no Google Meet, transcrever e atualizar o CRM.
- IA classificar respostas, sugerir mensagens, resumir leads e analisar reunioes.
- Leads sem e-mail ou telefone serem preservados em fila de enriquecimento.

## 3. Sistemas Envolvidos

| Sistema | Caminho | Papel |
|---|---|---|
| Mautic | `../mautic7/` | Base central, automacao, scoring, segmentos e campanhas |
| LetsWork | `.` | CRM comercial, oportunidades, reunioes, propostas e fechamento |
| Roboze | `../auto-pilot-dashboard` | Origem de contatos e eventos do LinkedIn |
| Extensao Chrome | a definir | Captura de reunioes no Google Meet |
| IA | LetsWork ou servico auxiliar | Classificacao, resumo, sugestoes e transcricao |

## 4. Principios De Execucao

- Priorizar APIs simples e incrementais no MVP.
- Evitar automacao irreversivel sem revisao humana.
- Separar tarefas por sistema para permitir trabalho paralelo.
- Registrar eventos em vez de sobrescrever historico.
- Preservar leads incompletos para enriquecimento, sem descartar dados.
- Remover audio bruto apos transcricao bem-sucedida.
- Manter transcricao e resumo no cadastro do lead/oportunidade.

## 5. Workstreams Paralelos

| Workstream | Responsavel Sugerido | Pode Comecar Em Paralelo? | Bloqueadores Principais |
|---|---|---:|---|
| WS1 - Fundacao Mautic | Marketing Ops / Dev Mautic | Sim | Nenhum |
| WS2 - API LetsWork CRM | Backend LetsWork | Sim | Definicao minima de payloads |
| WS3 - Roboze para Mautic | Dev Roboze | Sim | Campos e endpoint Mautic |
| WS4 - IA | Dev IA / Backend | Sim | Schemas de entrada/saida |
| WS5 - Extensao Chrome | Dev Front/Extension | Sim | API de reuniao no LetsWork |
| WS6 - Operacao Comercial | Comercial/Marketing | Sim | Lista de usuarios e regras basicas |
| WS7 - Campanhas Mautic | Marketing | Parcial | Campos, segmentos e tags |
| WS8 - QA e Observabilidade | QA/Dev | Parcial | Fluxos integrados disponiveis |

## 6. Modelo De Dados Mautic

### T-001: Criar Campos Customizados No Mautic

Workstream: WS1

Prioridade: P0

Pode rodar em paralelo com: T-010, T-030, T-040, T-050, T-060, T-062

Dependencias: nenhuma

Descricao: criar todos os campos customizados necessarios no Mautic, pois hoje nao ha campos customizados relevantes.

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
- Todos os campos aparecem disponiveis para segmentacao ou automacao quando aplicavel.
- Campos booleanos, datas e textos longos usam tipos adequados.
- Campos sensiveis de reuniao nao ficam publicos para usuarios sem permissao.

### T-002: Criar Tags Padronizadas No Mautic

Workstream: WS1

Prioridade: P0

Pode rodar em paralelo com: T-001

Dependencias: nenhuma

Descricao: criar tags padronizadas para origem, modulo de interesse, temperatura e status operacional.

Tags iniciais:

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

- Tags seguem nomenclatura unica e previsivel.
- Tags podem ser usadas em campanhas e segmentos.
- Tags de modulo correspondem aos valores previstos em `modulo_interesse`.

### T-003: Criar Segmentos Basicos No Mautic

Workstream: WS1

Prioridade: P0

Pode rodar em paralelo com: T-004, T-050

Dependencias: T-001, T-002

Descricao: criar segmentos operacionais para nutricao, priorizacao e enriquecimento.

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

- Segmentos populam automaticamente conforme campos/tags.
- Lead incompleto nao entra em campanhas comerciais de conversao.
- Lead quente entra em segmento `Pronto para comercial`.

## 7. Scoring E Temperatura

### T-004: Implementar Score De Interesse No Mautic

Workstream: WS1

Prioridade: P0

Pode rodar em paralelo com: T-010, T-020, T-040

Dependencias: T-001

Descricao: configurar scoring por eventos de LinkedIn, Mautic e LetsWork CRM.

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

Eventos comerciais:

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

- `interesse_score` e atualizado por eventos relevantes.
- Eventos repetidos possuem regra clara para evitar pontuacao infinita indevida.
- Eventos negativos reduzem score sem gerar valores inconsistentes.
- Score pode ser recalculado a partir do historico de eventos.

### T-005: Implementar Temperatura Do Lead

Workstream: WS1

Prioridade: P0

Pode rodar em paralelo com: T-003 apos T-001

Dependencias: T-004

Descricao: mapear score para temperatura operacional.

Tabela:

| Score | Temperatura | Acao |
|---:|---|---|
| 0 a 20 | Frio | Nutricao leve |
| 21 a 60 | Morno | Conteudo + abordagem consultiva |
| 61 a 100 | Quente | Comercial deve agir |
| 100+ | Prioritario | Follow-up imediato |

Criterios de aceite:

- Campo `temperatura` atualiza automaticamente apos mudanca de score.
- Tags de temperatura sao sincronizadas com o campo.
- Leads ganhos saem de campanhas de prospeccao.
- Leads perdidos podem entrar em reativacao futura.

## 8. APIs LetsWork CRM

### T-010: Definir Contrato De API Para Lead/Oportunidade

Workstream: WS2

Prioridade: P0

Pode rodar em paralelo com: T-001, T-004, T-020, T-040

Dependencias: nenhuma

Descricao: definir payload minimo para criar/atualizar lead e oportunidade no LetsWork CRM.

Payload minimo de entrada:

```json
{
  "external_source": "mautic",
  "external_id": "123",
  "email": "lead@empresa.com",
  "phone": "+5511999999999",
  "name": "Nome Lead",
  "company": "Empresa",
  "cargo": "RH",
  "segmento": "Servicos",
  "origem_principal": "linkedin_roboze",
  "score": 75,
  "temperatura": "quente",
  "modulo_interesse": "ponto_eletronico",
  "dor_principal": "fechamento de ponto",
  "historico_resumido": "Resumo do lead",
  "proxima_acao": "agendar diagnostico",
  "responsavel_recebimento_lead": 10
}
```

Criterios de aceite:

- Contrato documentado no repo ou ferramenta da equipe.
- Campos obrigatorios definidos.
- Campos opcionais definidos.
- Erros de validacao possuem formato padrao.

### T-011: Criar Endpoint De Criacao/Atualizacao De Lead No LetsWork CRM

Workstream: WS2

Prioridade: P1

Pode rodar em paralelo com: T-012, T-013

Dependencias: T-010

Descricao: criar endpoint simples para upsert de lead vindo do Mautic.

Regras:

- Deduplicar por e-mail, telefone e LinkedIn URL quando disponivel.
- Aceitar `responsavel_recebimento_lead` selecionado pelo usuario.
- Registrar origem externa e ID externo.
- Nao sobrescrever dados preenchidos com valores vazios.

Criterios de aceite:

- Cria lead novo.
- Atualiza lead existente.
- Registra responsavel escolhido.
- Retorna ID interno do LetsWork CRM.
- Registra log de integracao.

### T-012: Criar Endpoint De Evento Comercial No LetsWork CRM

Workstream: WS2

Prioridade: P1

Pode rodar em paralelo com: T-011, T-013

Dependencias: T-010

Descricao: criar endpoint para registrar eventos comerciais e permitir retorno ao Mautic.

Eventos suportados:

- `reuniao_agendada`
- `reuniao_realizada`
- `proposta_enviada`
- `negociacao_ativa`
- `ganho`
- `perdido`
- `motivo_perda`
- `sem_resposta`
- `followup_futuro`

Criterios de aceite:

- Evento e salvo no historico do lead/oportunidade.
- Evento pode disparar callback ou fila para o Mautic.
- Eventos invalidos sao recusados com mensagem clara.
- Eventos criticos respeitam permissao do usuario.

### T-013: Criar Endpoint De Reuniao Transcrita No LetsWork CRM

Workstream: WS2

Prioridade: P1

Pode rodar em paralelo com: T-011, T-012, T-050

Dependencias: T-010

Descricao: criar endpoint para receber transcricao, resumo e analise de reuniao da extensao/servico de IA.

Payload minimo:

```json
{
  "lead_id": 123,
  "oportunidade_id": 456,
  "meeting_url": "https://meet.google.com/...",
  "platform": "google_meet",
  "started_at": "2026-05-21T10:00:00-03:00",
  "duration_seconds": 1800,
  "transcricao": "Texto completo transcrito",
  "resumo": "Resumo executivo",
  "dores": ["fechamento de ponto manual"],
  "objecoes": ["preco"],
  "modulos_interesse": ["ponto_eletronico"],
  "proximos_passos": ["enviar proposta ate sexta"],
  "followup_at": "2026-05-24T09:00:00-03:00"
}
```

Criterios de aceite:

- Transcricao fica salva no cadastro do lead/oportunidade.
- Resumo aparece na timeline do CRM.
- Dores e modulos atualizam campos comerciais quando confirmado ou seguro.
- Follow-up vira tarefa para o responsavel comercial.
- Audio bruto nao e armazenado nesse endpoint.

## 9. Integracao Mautic Para LetsWork

### T-020: Criar Envio Manual De Lead Do Mautic Para LetsWork CRM

Workstream: WS1 + WS2

Prioridade: P1

Pode rodar em paralelo com: T-030, T-040, T-050

Dependencias: T-001, T-010, T-011

Descricao: permitir que marketing envie lead qualificado do Mautic para o LetsWork CRM selecionando quem vai receber.

Gatilhos elegiveis:

- Temperatura quente.
- Score acima de 60.
- Pediu informacao.
- Aceitou reuniao.
- Visitou pagina de preco.
- Respondeu positivamente no LinkedIn.
- Preencheu formulario de interesse.

Criterios de aceite:

- Usuario consegue selecionar responsavel comercial.
- Lead e criado/atualizado no LetsWork CRM.
- Mautic registra envio e responsavel escolhido.
- Erros de API sao visiveis para operador.

### T-021: Criar Envio Automatico Opcional De Lead Quente

Workstream: WS1 + WS2

Prioridade: P2

Pode rodar em paralelo com: T-060, T-061

Dependencias: T-020, T-005

Descricao: automatizar envio de leads quentes quando houver regra de responsavel definida.

Criterios de aceite:

- Automacao pode ser ligada/desligada.
- Regra de responsavel e configuravel.
- Lead nao e enviado duplicado em curto periodo.
- Transferencia registra quem recebeu e motivo do envio.

## 10. Integracao LetsWork Para Mautic

### T-022: Criar Retorno De Eventos Comerciais Para Mautic

Workstream: WS2 + WS1

Prioridade: P1

Pode rodar em paralelo com: T-030, T-040, T-050

Dependencias: T-012, T-004

Descricao: enviar eventos comerciais do LetsWork CRM para o Mautic para atualizar score, temperatura e campanhas.

Eventos retornados:

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

- Evento enviado atualiza score no Mautic.
- Evento enviado atualiza temperatura quando necessario.
- Lead ganho sai de campanhas de prospeccao.
- Lead perdido entra em fluxo adequado de reativacao ou arquivamento.

## 11. Roboze Para Mautic

### T-030: Definir Payload De Exportacao Do Roboze

Workstream: WS3

Prioridade: P0

Pode rodar em paralelo com: T-001, T-010, T-040, T-050

Dependencias: nenhuma

Descricao: definir payload dos contatos e eventos do LinkedIn enviados pelo Roboze.

Payload minimo de contato:

```json
{
  "source": "linkedin_roboze",
  "roboze_contact_id": "abc123",
  "name": "Nome Lead",
  "email": null,
  "phone": null,
  "company": "Empresa",
  "cargo": "RH",
  "linkedin_url": "https://linkedin.com/in/...",
  "segmento": "Servicos",
  "campaign": "campanha-maio",
  "last_interaction_at": "2026-05-21T10:00:00-03:00"
}
```

Payload minimo de evento:

```json
{
  "source": "linkedin_roboze",
  "roboze_contact_id": "abc123",
  "event": "respondeu_mensagem",
  "event_at": "2026-05-21T10:00:00-03:00",
  "message": "Tenho interesse em entender melhor",
  "linkedin_url": "https://linkedin.com/in/..."
}
```

Criterios de aceite:

- Payload suporta contatos completos e incompletos.
- Payload preserva LinkedIn URL como identificador alternativo.
- Payload permite rastrear campanha do Roboze.
- Eventos possuem nomes padronizados.

### T-031: Implementar Exportacao/API Do Roboze Para Mautic

Workstream: WS3

Prioridade: P1

Pode rodar em paralelo com: T-011, T-012, T-040, T-050

Dependencias: T-001, T-030

Descricao: implementar envio de contatos e eventos do Roboze para o Mautic usando a API mais simples possivel.

Eventos suportados:

- Contato encontrado.
- Conexao enviada.
- Conexao aceita.
- Mensagem enviada.
- Resposta recebida.
- Resposta positiva.
- Resposta negativa.
- Lead pediu informacao.
- Lead aceitou reuniao.

Criterios de aceite:

- Contato completo cria/atualiza lead no Mautic.
- Contato sem e-mail ou telefone cria lead incompleto.
- Evento atualiza score e campos LinkedIn.
- Resposta recebida aciona classificacao por IA ou marca para processamento.
- Falhas de envio ficam registradas para retentativa.

### T-032: Criar Fila De Leads Incompletos

Workstream: WS1 + WS3 + WS6

Prioridade: P1

Pode rodar em paralelo com: T-060, T-061

Dependencias: T-001, T-003, T-031

Descricao: criar processo operacional para leads sem e-mail ou telefone.

Regras:

- Lead incompleto recebe `lead_incompleto = sim`.
- `motivo_lead_incompleto` indica se falta e-mail, telefone ou ambos.
- Lead incompleto entra no segmento `Leads incompletos - enriquecimento`.
- Lead incompleto nao entra em campanha comercial de conversao.
- Operador pode completar dados e liberar para nutricao.

Criterios de aceite:

- Leads incompletos ficam pesquisaveis.
- Marketing consegue revisar e completar dados.
- Ao completar e-mail e telefone, lead sai da fila incompleta.
- Historico de origem e preservado.

## 12. IA

### T-040: Definir Schemas De IA

Workstream: WS4

Prioridade: P0

Pode rodar em paralelo com: T-001, T-010, T-030, T-050

Dependencias: nenhuma

Descricao: definir entradas e saidas das funcoes de IA para LinkedIn, resumo de lead, sugestao de mensagem e reunioes.

Classificacao de resposta LinkedIn:

```json
{
  "intencao": "dor_identificada",
  "dor_principal": "fechamento de ponto manual",
  "modulo_interesse": "ponto_eletronico",
  "objecao": null,
  "score_event": "resposta_com_dor_identificada",
  "proxima_acao": "oferecer diagnostico",
  "mensagem_sugerida": "..."
}
```

Analise de reuniao:

```json
{
  "resumo": "Resumo executivo",
  "dores": ["banco de horas em planilha"],
  "objecoes": ["preco"],
  "modulos_interesse": ["banco_horas"],
  "nivel_urgencia": "alto",
  "decisores": ["Diretora de RH"],
  "proximos_passos": ["enviar proposta"],
  "followup_at": "2026-05-24T09:00:00-03:00",
  "mensagem_followup": "...",
  "alteracoes_criticas": []
}
```

Criterios de aceite:

- Schemas sao versionados.
- Saida da IA e JSON valido.
- Campos obrigatorios estao documentados.
- Erros de IA possuem fallback seguro.

### T-041: Implementar Classificacao De Respostas Do LinkedIn

Workstream: WS4

Prioridade: P1

Pode rodar em paralelo com: T-011, T-031, T-050

Dependencias: T-040

Descricao: classificar respostas recebidas pelo Roboze.

Classificacoes:

- Sem interesse.
- Curioso.
- Dor identificada.
- Objecao.
- Pediu informacao.
- Pronto para reuniao.
- Lead desqualificado.

Criterios de aceite:

- Classificacao retorna intencao e motivo.
- Retorna `dor_principal` quando houver dor.
- Retorna `modulo_interesse` quando inferivel.
- Retorna sugestao de proxima mensagem editavel.
- Nao envia mensagem automaticamente no MVP.

### T-042: Implementar Resumo De Lead E Proxima Acao

Workstream: WS4

Prioridade: P1

Pode rodar em paralelo com: T-041, T-050

Dependencias: T-040

Descricao: gerar resumo consolidado do lead para marketing e comercial.

Criterios de aceite:

- Resumo inclui origem, interacoes, dores, modulo, objecoes, score e temperatura.
- Proxima acao e uma das opcoes permitidas: nutrir, enviar case, pedir reuniao, passar para comercial, reativar depois, desqualificar.
- Resumo pode ser enviado ao LetsWork CRM junto com lead quente.

### T-043: Implementar Analise De Transcricao De Reuniao

Workstream: WS4

Prioridade: P1

Pode rodar em paralelo com: T-050, T-013

Dependencias: T-040

Descricao: analisar transcricoes de reuniao para atualizar CRM e sugerir follow-up.

Criterios de aceite:

- Gera resumo curto para timeline.
- Extrai dores, objecoes, concorrentes, modulos e proximos passos.
- Sugere tarefa de follow-up com data quando possivel.
- Sugere mensagem de follow-up.
- Alteracoes criticas exigem confirmacao humana.

## 13. Extensao Chrome Google Meet

### T-050: Definir Arquitetura Da Extensao

Workstream: WS5

Prioridade: P0

Pode rodar em paralelo com: T-001, T-010, T-030, T-040

Dependencias: nenhuma

Descricao: definir arquitetura tecnica da extensao Chrome para Google Meet.

Decisoes MVP:

- Suportar apenas Google Meet.
- Capturar audio do browser como lead/participantes.
- Capturar audio do microfone como vendedor.
- Enviar audio para backend do LetsWork para transcricao.
- Remover audio bruto apos transcricao.
- Salvar transcricao no cadastro do lead/oportunidade.

Criterios de aceite:

- Permissoes Chrome necessarias estao mapeadas.
- Fluxo de consentimento esta definido.
- Limites de captura e privacidade estao documentados.
- Integracao com endpoint T-013 esta prevista.

### T-051: Criar MVP Da Extensao Para Vincular Reuniao Ao CRM

Workstream: WS5

Prioridade: P1

Pode rodar em paralelo com: T-013, T-043

Dependencias: T-050, T-011

Descricao: permitir selecionar lead/oportunidade ao entrar no Google Meet.

Criterios de aceite:

- Extensao detecta URL do Google Meet.
- Usuario busca ou seleciona lead/oportunidade.
- Extensao registra `lead_id` ou `oportunidade_id` antes da captura.
- Usuario consegue cancelar a vinculacao.

### T-052: Implementar Captura De Audio Browser E Microfone

Workstream: WS5

Prioridade: P1

Pode rodar em paralelo com: T-043 apos T-050

Dependencias: T-050, T-051

Descricao: capturar audio do browser e microfone durante reuniao.

Criterios de aceite:

- Usuario inicia captura manualmente.
- Extensao exibe estado de gravacao ativo.
- Browser e microfone sao capturados separadamente quando possivel.
- Captura para ao finalizar ou cancelar.
- Falha de permissao e tratada com mensagem clara.

### T-053: Enviar Audio Para Transcricao E Remover Bruto

Workstream: WS5 + WS2 + WS4

Prioridade: P1

Pode rodar em paralelo com: T-022

Dependencias: T-013, T-043, T-052

Descricao: enviar audio para backend, transcrever, analisar, remover audio bruto e salvar transcricao no CRM.

Criterios de aceite:

- Audio chega ao backend vinculado ao lead/oportunidade.
- Transcricao e gerada.
- Audio bruto e removido apos sucesso.
- Transcricao e resumo ficam no CRM.
- Evento de reuniao transcrita retorna ao Mautic.

## 14. Campanhas E Operacao

### T-060: Criar Playbooks De Abordagem Por Dor

Workstream: WS6 + WS7

Prioridade: P1

Pode rodar em paralelo com: T-001, T-010, T-030, T-040, T-050

Dependencias: nenhuma

Descricao: criar mensagens editaveis para abordagem por dor e modulo.

Playbooks iniciais:

- Ponto eletronico: fechamento de ponto e controle de jornada.
- Banco de horas: banco de horas, horas extras e atrasos.
- Escalas: escalas, turnos e equipes externas.
- Ferias: controle de ferias vencidas e saldos.
- Documentos: holerites, documentos e assinaturas.
- ATS: triagem de candidatos e organizacao do processo seletivo.
- Avaliacoes: avaliacoes de desempenho e feedbacks.
- OKRs: acompanhamento de metas e indicadores.
- Timesheet: controle de horas, projetos e rentabilidade.

Criterios de aceite:

- Cada playbook tem mensagem inicial, checklist, convite para diagnostico e reativacao.
- Mensagens usam variaveis `{nome}`, `{empresa}`, `{segmento}`, `{dor_principal}`, `{modulo_interesse}`.
- Comercial consegue editar antes de enviar.

### T-061: Criar Campanhas De Nutricao No Mautic

Workstream: WS7

Prioridade: P1

Pode rodar em paralelo com: T-020, T-031, T-041

Dependencias: T-001, T-003, T-060

Descricao: criar campanhas para leads frios, mornos e reativacao.

Campanhas iniciais:

- Lead frio: conteudo educativo, checklist, case, pergunta consultiva, diagnostico, tentativa leve de reuniao.
- Lead morno: case por segmento, checklist avancado, convite para diagnostico, alerta para marketing.
- Reativacao 15 dias.
- Reativacao 30 dias.
- Reativacao 60 dias.

Criterios de aceite:

- Lead incompleto nao entra em campanha comercial.
- Lead quente sai de campanhas frias.
- Campanhas usam segmento e modulo de interesse quando disponivel.
- Campanhas atualizam score conforme abertura, clique e resposta.

### T-062: Definir Lista De Usuarios Que Recebem Leads

Workstream: WS6 + WS2

Prioridade: P0

Pode rodar em paralelo com: T-001, T-010, T-030

Dependencias: nenhuma

Descricao: definir quais usuarios do LetsWork CRM podem receber leads.

Criterios de aceite:

- Lista inicial de responsaveis esta definida.
- Cada responsavel tem ID interno no LetsWork CRM.
- Envio manual do Mautic consegue selecionar um responsavel valido.
- Usuarios inativos nao aparecem como opcao.

## 15. Deduplicacao

### T-070: Implementar Deduplicacao MVP

Workstream: WS1 + WS2 + WS3

Prioridade: P1

Pode rodar em paralelo com: T-061

Dependencias: T-011, T-031

Descricao: aplicar deduplicacao simples nos fluxos de entrada.

Prioridade:

1. E-mail.
2. Telefone.
3. LinkedIn URL.
4. Nome + empresa.
5. Dominio da empresa.

Criterios de aceite:

- Lead com mesmo e-mail atualiza registro existente.
- Lead com mesmo telefone atualiza registro existente.
- Lead com mesma LinkedIn URL e sem e-mail/telefone atualiza registro incompleto.
- Dados vazios nao sobrescrevem dados preenchidos.
- Conflitos ficam rastreaveis.

## 16. QA, Logs E Observabilidade

### T-080: Criar Plano De Testes Integrados

Workstream: WS8

Prioridade: P1

Pode rodar em paralelo com: T-060, T-061

Dependencias: T-010, T-030, T-040, T-050

Descricao: criar cenarios de teste ponta a ponta.

Cenarios obrigatorios:

- Roboze envia contato completo e Mautic cria lead.
- Roboze envia contato incompleto e Mautic cria lead em enriquecimento.
- Resposta LinkedIn com dor aumenta score e sugere mensagem.
- Lead quente e enviado manualmente ao LetsWork CRM com responsavel selecionado.
- Reuniao realizada no LetsWork retorna evento ao Mautic.
- Extensao envia reuniao transcrita e CRM salva transcricao.
- Audio bruto e removido apos transcricao.
- Lead ganho sai de campanhas de prospeccao.

Criterios de aceite:

- Plano cobre caminho feliz e falhas principais.
- Cada cenario tem dados de entrada e resultado esperado.
- Falhas de integracao geram log rastreavel.

### T-081: Implementar Logs De Integracao

Workstream: WS2 + WS3 + WS5

Prioridade: P1

Pode rodar em paralelo com: T-011, T-031, T-053

Dependencias: contratos de API definidos

Descricao: registrar logs de entrada, saida, erro e retentativa.

Criterios de aceite:

- Logs nao armazenam audio bruto.
- Logs nao expõem tokens ou dados sensiveis desnecessarios.
- Cada request possui correlation ID.
- Operador consegue diagnosticar falhas de envio.

## 17. Ondas De Execucao Paralela

### Onda 0: Contratos E Fundacao

Objetivo: desbloquear times.

Pode executar em paralelo:

- T-001 Criar campos customizados no Mautic.
- T-002 Criar tags padronizadas no Mautic.
- T-010 Definir contrato de API para lead/oportunidade.
- T-030 Definir payload de exportacao do Roboze.
- T-040 Definir schemas de IA.
- T-050 Definir arquitetura da extensao.
- T-060 Criar playbooks de abordagem por dor.
- T-062 Definir lista de usuarios que recebem leads.

Saida da onda:

- Campos, payloads, schemas e responsaveis definidos.
- Times podem implementar sem bloquear uns aos outros.

### Onda 1: Implementacoes Independentes

Objetivo: construir pecas principais.

Pode executar em paralelo:

- T-003 Criar segmentos basicos no Mautic.
- T-004 Implementar score de interesse no Mautic.
- T-011 Criar endpoint de criacao/atualizacao de lead.
- T-012 Criar endpoint de evento comercial.
- T-013 Criar endpoint de reuniao transcrita.
- T-031 Implementar exportacao/API do Roboze para Mautic.
- T-041 Implementar classificacao de respostas do LinkedIn.
- T-042 Implementar resumo de lead e proxima acao.
- T-051 Criar MVP da extensao para vincular reuniao ao CRM.

Saida da onda:

- Sistemas conseguem trocar dados basicos.
- IA ja classifica respostas e resume leads.
- Extensao ja vincula reuniao ao CRM.

### Onda 2: Fluxos Integrados

Objetivo: conectar os sistemas de ponta a ponta.

Pode executar em paralelo:

- T-005 Implementar temperatura do lead.
- T-020 Criar envio manual de lead do Mautic para LetsWork CRM.
- T-022 Criar retorno de eventos comerciais para Mautic.
- T-032 Criar fila de leads incompletos.
- T-043 Implementar analise de transcricao de reuniao.
- T-052 Implementar captura de audio browser e microfone.
- T-061 Criar campanhas de nutricao no Mautic.
- T-070 Implementar deduplicacao MVP.
- T-081 Implementar logs de integracao.

Saida da onda:

- Lead percorre Roboze, Mautic e LetsWork CRM.
- Reuniao pode ser capturada e analisada.
- Leads incompletos possuem processo operacional.

### Onda 3: Reunioes E Fechamento Do MVP

Objetivo: completar fluxo de reuniao e validar criterios.

Pode executar em paralelo:

- T-021 Criar envio automatico opcional de lead quente.
- T-053 Enviar audio para transcricao e remover bruto.
- T-080 Criar plano de testes integrados.

Saida da onda:

- Transcricao e resumo ficam no CRM.
- Audio bruto e removido.
- Testes ponta a ponta validam MVP.

### Onda 4: Otimizacoes Pos-MVP

Objetivo: melhorar automacao e inteligencia.

Tarefas futuras:

- Dashboard de conversao por origem.
- Relatorio de segmentos que mais convertem.
- Analise automatica de motivos de perda.
- Deduplicacao avancada.
- Enriquecimento automatico de empresas.
- Suporte futuro a Zoom, Teams e outras plataformas.

## 18. Matriz De Dependencias

| Tarefa | Depende De | Libera |
|---|---|---|
| T-001 | nenhuma | T-003, T-004, T-020, T-031, T-061 |
| T-002 | nenhuma | T-003, T-005 |
| T-003 | T-001, T-002 | T-032, T-061 |
| T-004 | T-001 | T-005, T-022 |
| T-005 | T-004 | T-020, T-021 |
| T-010 | nenhuma | T-011, T-012, T-013, T-020 |
| T-011 | T-010 | T-020, T-051, T-070 |
| T-012 | T-010 | T-022 |
| T-013 | T-010 | T-053 |
| T-020 | T-001, T-010, T-011 | T-021 |
| T-022 | T-012, T-004 | T-080 |
| T-030 | nenhuma | T-031 |
| T-031 | T-001, T-030 | T-032, T-070 |
| T-040 | nenhuma | T-041, T-042, T-043 |
| T-041 | T-040 | T-031 completo com IA |
| T-042 | T-040 | T-020 enriquecido |
| T-043 | T-040 | T-053 |
| T-050 | nenhuma | T-051, T-052 |
| T-051 | T-050, T-011 | T-052 |
| T-052 | T-050, T-051 | T-053 |
| T-053 | T-013, T-043, T-052 | T-080 |
| T-060 | nenhuma | T-061 |
| T-061 | T-001, T-003, T-060 | T-080 |
| T-062 | nenhuma | T-020 |
| T-070 | T-011, T-031 | T-080 |
| T-080 | contratos e fluxos principais | aceite final |
| T-081 | contratos de API | diagnostico e suporte |

## 19. Criterios De Aceite Do MVP

- Mautic possui todos os campos customizados definidos.
- Mautic possui segmentos, tags, score e temperatura funcionando.
- Roboze cria/atualiza leads completos no Mautic.
- Roboze preserva leads incompletos em fila de enriquecimento.
- Respostas do LinkedIn sao classificadas por IA.
- Lead quente pode ser enviado do Mautic para LetsWork CRM com responsavel selecionado.
- LetsWork CRM registra eventos comerciais e retorna eventos ao Mautic.
- Extensao Chrome funciona no Google Meet.
- Extensao captura audio do browser e microfone com acao explicita do usuario.
- Audio e transcrito, analisado e removido apos sucesso.
- Transcricao e resumo ficam no cadastro do lead/oportunidade.
- IA sugere proximos passos sem aplicar alteracoes criticas automaticamente.
- Logs permitem diagnosticar falhas de integracao.

## 20. Riscos Tecnicos E Mitigacoes

| Risco | Impacto | Mitigacao |
|---|---|---|
| API Mautic exigir mais configuracao que o esperado | Atraso no WS1/WS3 | Comecar com API mais simples e payload minimo |
| Endpoints LetsWork inexistentes | Bloqueio de integracao | Priorizar T-010 a T-013 na Onda 0/1 |
| Captura de audio no Chrome ter limitacoes | Risco no fluxo de reuniao | Prototipar T-050/T-052 cedo |
| Leads incompletos acumularem | Perda operacional | Criar fila e rotina semanal de enriquecimento |
| IA gerar sugestao incorreta | Risco comercial | Exigir revisao humana em acoes criticas |
| Audio bruto conter dados sensiveis | Risco legal | Remover apos transcricao e restringir logs |
| Deduplicacao incorreta | Dados duplicados ou mesclados errado | Comecar por e-mail/telefone e auditar conflitos |

## 21. Sequencia Recomendada Para Inicio Imediato

1. Executar Onda 0 inteira em paralelo.
2. Assim que T-010 estiver pronto, iniciar T-011, T-012 e T-013.
3. Assim que T-001 estiver pronto, iniciar T-003, T-004 e T-031.
4. Assim que T-040 estiver pronto, iniciar T-041, T-042 e T-043.
5. Assim que T-050 estiver pronto, iniciar T-051 e validar permissoes da extensao.
6. Integrar primeiro Roboze para Mautic, depois Mautic para LetsWork, depois reuniao transcrita.
7. Fechar MVP com T-080 e validacao ponta a ponta.
