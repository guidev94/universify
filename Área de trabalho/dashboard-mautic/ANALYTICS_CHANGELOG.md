# LetsworkAnalyticsBundle — Changelog & Funcionalidades

## Histórico de Implementação (Ordem Cronológica)

---

### v1.0.0 — Bootstrap do Plugin
**Data:** 2026-05-21

- Criação da estrutura do bundle Mautic 5 (`LetsworkAnalyticsBundle`)
- Registro de rota `/s/analytics/{tab}`
- Menu "Analytics" no sidebar com ícone `ri-bar-chart-box-fill`
- Permissão `letsworkAnalytics:analytics:view`
- Tradução pt_BR

---

### v1.1.0 — Dashboard Base + 5 Tabs
**Data:** 2026-05-21

**Tabs implementadas:**
1. **Visão Geral** — KPIs + gráficos resumo + tabela leads quentes
2. **Conversão por Origem** — Funil de conversão + win rate por origem
3. **Segmentos** — Ranking de conversão + performance por segmento
4. **Pipeline** — Temperatura + tempo médio até reunião/proposta
5. **Motivos de Perda** — Pie chart + tendência + por origem/segmento

**Gráficos (Chart.js):**
- Doughnut: distribuição por temperatura
- Line: leads ao longo do tempo
- Bar: funil de conversão, ranking segmentos, win rate

**Design:**
- CSS premium com variáveis do Mautic (dark mode compatível)
- Animações de entrada `fadeInUp` escalonadas
- Cards com gradient top-bar colorida por tipo
- Responsivo (1024px → 768px → 480px)

---

### v1.2.0 — KPIs Corrigidos + Clicáveis
**Data:** 2026-05-21

**Renomeação de KPIs (conforme PRD):**
- "Leads Centralizados" → **Prospecção Ativa**
- Adicionado: **Engajados**, **Reuniões**, **Propostas Enviadas**, **Clientes Ganhos**
- "Reuniões Mautic" removido (não existe no escopo)
- **Sem Follow-up** com destaque vermelho

**Interatividade:**
- KPIs clicáveis → abre painel lateral com lista de leads
- Hover: `translateY(-3px)` + seta animada
- Cada KPI com descrição contextual

**Integração e-mail (Mautic nativo):**
- KPIs de E-mails Enviados, Abertos, Taxa Abertura
- Query direta em `email_stats`
- Seção só aparece quando há dados

---

### v1.3.0 — Modal de Lead + Ações Reais
**Data:** 2026-05-21

**Modal completo ao clicar no lead:**
- Avatar com inicial + cor por temperatura
- Dados: nome, cargo, empresa, email, telefone
- Grid: origem, segmento, módulo, score, status, valor potencial
- Dor principal em destaque

**Contato direto (links funcionais):**
- E-mail → abre `mailto:`
- Telefone → abre `tel:`
- WhatsApp → abre `wa.me/55{número}`

**Ações funcionais:**
- **Enviar E-mail** — formulário real com assunto/corpo, registra em `email_stats` + `lead_notes`
- **Agendar Follow-up** — data picker + observação, atualiza `data_proximo_followup`, cria nota, cria notificação 1 dia antes
- Ver Ficha → link direto para `/s/contacts/view/{id}`
- Editar Lead → link para `/s/contacts/edit/{id}`
- Enviar para CRM LetsWork (placeholder)

---

### v1.4.0 — Integração IA (Gemini)
**Data:** 2026-05-21

**API:** Google Gemini 2.0 Flash

**Funcionalidades IA:**
- Botão "Sugestão IA" no modal do lead
- Botão "Gerar com IA" no formulário de e-mail (preenche corpo automaticamente)
- Resposta estruturada:
  - Próxima ação recomendada
  - Classificação de intenção (Sem interesse → Pronto para reunião)
  - Probabilidade de conversão (Alta/Média/Baixa)
  - Dica comercial para o vendedor
  - Mensagem de follow-up personalizada
- Botão "Copiar Mensagem" para colar no WhatsApp/email

**Contexto enviado à IA:**
- Nome, empresa, cargo, segmento
- Módulo de interesse, dor principal
- Temperatura, score, status, origem

---

### v1.5.0 — Painel Lateral Refatorado + Dados Expandidos
**Data:** 2026-05-21

**Painel lateral (slide-in) melhorado:**
- Overlay com backdrop-blur ao abrir
- Loading spinner animado
- Lista com avatar colorido por temperatura:
  - Frio → azul (#3b82f6)
  - Morno → amarelo (#f59e0b)
  - Quente → vermelho (#ef4444)
  - Prioritário → roxo (#8b5cf6)
- Cada lead mostra: nome, badge temperatura, cargo, empresa
- Tags: origem + score
- 3 botões de ação por lead (aparecem no hover):
  - Ver (modal completo)
  - E-mail (formulário)
  - Follow-up (agendamento)
- Animação de entrada escalonada por lead
- Fecha com clique no overlay ou tecla Escape

**Dados mockados expandidos:**
- 125 leads totais
- Todos os segmentos: Serviços (29), Saúde (29), Engenharia (26), Alimentação (21), Comércio (20)
- Todas as origens: linkedin_roboze (43), letswork_site (31), apollo (24), indicação (18), lista importada (9)
- Pipeline completo: prospecção (35) → engajado (22) → reunião (16) → proposta (6) → negociação (6) → ganho (15) → perdido (25)
- 5 motivos de perda: "Escolheu concorrente", "Sem budget", "Preço acima", "Timing ruim", "Já tem sistema", "Decisor não avançou"
- Leads com telefone para teste de WhatsApp

---

## Funcionalidades Completas

### Dashboard Overview
| Funcionalidade | Status |
|---|---|
| KPI Prospecção Ativa (clicável) | ✅ |
| KPI Engajados (clicável) | ✅ |
| KPI Reuniões (clicável) | ✅ |
| KPI Propostas Enviadas (clicável) | ✅ |
| KPI Clientes Ganhos (clicável) | ✅ |
| KPI Sem Follow-up (clicável) | ✅ |
| KPI E-mails Enviados | ✅ |
| KPI E-mails Abertos | ✅ |
| KPI Taxa de Abertura | ✅ |
| Gráfico Temperatura (doughnut) | ✅ |
| Gráfico Leads ao Longo do Tempo (line) | ✅ |
| Tabela Leads Quentes com ações | ✅ |
| Date range filter (90 dias default) | ✅ |

### Conversão por Origem
| Funcionalidade | Status |
|---|---|
| Gráfico Funil de Conversão (bar) | ✅ |
| Gráfico Win Rate por Origem (bar) | ✅ |
| Tabela Conversão por Origem | ✅ |
| Origem "letswork_site" | ✅ |

### Segmentos
| Funcionalidade | Status |
|---|---|
| Gráfico Ranking Conversão (bar) | ✅ |
| Tabela Performance por Segmento | ✅ |

### Pipeline
| Funcionalidade | Status |
|---|---|
| Gráfico Temperatura (doughnut) | ✅ |
| KPI Tempo Médio até Reunião | ✅ |
| KPI Tempo Médio até Proposta | ✅ |
| Gráfico Progressão de Score (line) | ✅ |
| Valor Total Pipeline | ✅ |

### Motivos de Perda
| Funcionalidade | Status |
|---|---|
| Gráfico Motivos (doughnut) | ✅ |
| Gráfico Tendência de Perdas (line) | ✅ |
| Tabela Perdas por Origem | ✅ |
| Tabela Perdas por Segmento | ✅ |

### Ações e Interações
| Funcionalidade | Status |
|---|---|
| Modal lead com dados completos | ✅ |
| Enviar e-mail real (form + envio + log) | ✅ |
| Agendar follow-up (form + nota + notificação) | ✅ |
| Contato via WhatsApp | ✅ |
| Contato via telefone | ✅ |
| Contato via e-mail (mailto) | ✅ |
| Enviar para CRM LetsWork | ⏳ (placeholder) |
| Sugestão IA (Gemini) | ✅ |
| Gerar e-mail com IA | ✅ |
| Copiar mensagem IA | ✅ |

### Integração IA (Gemini)
| Funcionalidade | Status |
|---|---|
| Classificação de intenção | ✅ |
| Sugestão de próxima ação | ✅ |
| Mensagem de follow-up personalizada | ✅ |
| Probabilidade de conversão | ✅ |
| Dica comercial para vendedor | ✅ |
| Geração automática de corpo de e-mail | ✅ |

### UI/UX Premium
| Funcionalidade | Status |
|---|---|
| Animações de entrada (fadeInUp stagger) | ✅ |
| Números animados (count up) | ✅ |
| Hover em cards (translateY + shadow) | ✅ |
| KPI seta animada no hover | ✅ |
| Badge temperatura com cores | ✅ |
| Avatar colorido por temperatura | ✅ |
| Painel lateral slide-in + overlay blur | ✅ |
| Modal com scale animation | ✅ |
| Toast de confirmação (3s auto-dismiss) | ✅ |
| Ações appear-on-hover na listagem | ✅ |
| Chart.js com gradientes e tooltips | ✅ |
| Dark mode via CSS variables | ✅ |
| Responsivo (1024/768/480) | ✅ |
| Tecla Escape fecha painéis | ✅ |

---

## Stack Técnica

| Componente | Tecnologia |
|---|---|
| Backend | Mautic 5 Plugin (PHP 8.2, Symfony 6) |
| Database | MySQL 8.0 (DBAL queries) |
| Frontend | Twig + CSS custom properties + jQuery |
| Charts | Chart.js 2.9 (nativo Mautic) |
| Icons | Remixicon |
| IA | Google Gemini 2.0 Flash |
| Infra | Docker (container: mautic, porta: 6001) |

---

## Rotas Registradas

| Rota | Método | Descrição |
|---|---|---|
| `/s/analytics/{tab}` | GET | Página principal (5 tabs) |
| `/s/analytics/kpi-detail/{kpi}` | GET | JSON com leads do KPI |
| `/s/analytics/lead/{leadId}` | GET | JSON com dados completos do lead |
| `/s/analytics/ai/suggest/{leadId}` | GET | Sugestão IA (Gemini) |
| `/s/analytics/action/followup` | POST | Agendar follow-up real |
| `/s/analytics/action/send-email` | POST | Enviar e-mail real |

---

## Campos Customizados (tabela `leads`)

| Campo | Tipo | Uso |
|---|---|---|
| temperatura | varchar(20) | frio/morno/quente/prioritario |
| origem_principal | varchar(100) | linkedin_roboze, letswork_site, apollo, etc |
| segmento | varchar(100) | Servicos, Engenharia, Comercio, Alimentacao, Saude |
| interesse_score | int | Score de engajamento |
| icp_score | int | Score de fit com ICP |
| status_comercial | varchar(50) | prospeccao → ganho/perdido |
| motivo_perda | varchar(255) | Razão da perda |
| modulo_interesse | varchar(100) | Módulo LetsWork de interesse |
| dor_principal | varchar(255) | Dor identificada |
| data_ultima_interacao | datetime | Última atividade |
| data_proximo_followup | datetime | Próximo follow-up agendado |
| cargo | varchar(100) | Cargo do contato |
| valor_potencial | decimal(10,2) | Valor estimado do deal |

---

## Acesso

- **URL:** http://localhost:6001/s/analytics
- **Credenciais:** admin / Mautic@2026!
- **API Gemini:** REMOVED_KEY
