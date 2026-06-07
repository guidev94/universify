# LeadHunter — Product Requirements Document (PRD)

## Visao do Produto

LeadHunter e uma plataforma de extracao automatizada e gestao de leads B2B para PMEs brasileiras. Combina web crawling multi-fonte com um CRM leve, pipeline de vendas e campanhas automatizadas para capturar, qualificar e converter leads sem dependencia de ferramentas caras.

**Proposta de valor:** Automacao end-to-end do processo de prospeccao — da captura ao fechamento — com inteligencia de scoring e enriquecimento automatico.

---

## Stack Tecnica

| Camada | Tecnologia |
|--------|-----------|
| Backend | Flask 3.1, Python 3.12 |
| Database | SQLite (WAL mode) |
| Crawlers | Selenium, BeautifulSoup, Requests (7 fontes) |
| Scheduler | ParallelScheduler (ThreadPoolExecutor, timers independentes) |
| Pipeline | LeadPipeline (verify + enrich workers com filas) |
| Real-time | SSE (Server-Sent Events) via EventBus |
| Frontend | Jinja2, Tailwind CSS, GSAP, Chart.js, Lucide Icons |
| Validacao | email-validator, phonenumbers, custom validators |

---

## Arquitetura

```
[Browser] ←→ SSE Stream (real-time)
    ↕
[Flask App] → Blueprints (dashboard, leads, crawl, export, events)
    ↕
[ParallelScheduler]
  ├── Timer(campaign_fill, 5min)
  ├── Timer(verify, 30min)
  ├── Timer(auto_crawl, 3h)
  ├── Timer(decay, 6h)
  ├── Timer(enrich, 45min)
  └── Timer(cleanup, 30s)
    ↕
[LeadPipeline]
  ├── verify_queue → SiteVerifier (batch 20)
  └── enrich_queue → EnrichmentCrawler (batch 10)
    ↕
[SQLite WAL] ← JobManager (persistent state)
```

---

## Features Implementadas

### Extracao de Leads
- 7 crawlers paralelos (DuckDuckGo, Bing, Maps, Diretórios BR, Nicho, YellowPages, Website)
- ThreadPoolExecutor(4) por job, CrawlSemaphore(3) global
- Validacao rigorosa (email, telefone BR, blacklist gov/genericos)
- Deduplicacao inteligente (domain hash → email → phone → name)
- Scoring automatico (0-100) com tier assignment (hot/warm/cold)

### Pipeline Automatizada
- Verificacao de sites (HTTP + SSL) em background
- Enriquecimento social (Instagram, Facebook, emails adicionais)
- Score decay para leads antigos nao contatados
- Campaign auto-fill com extracao continua ate meta

### Dashboard Real-Time
- SSE para updates instantaneos (sidebar, topbar, activity feed)
- Progress bars live para extracoes em andamento
- Toasts para eventos (novo lead, job completo, erro)
- Metricas: total, hot/warm/cold, verificados, revenue, conversao

### Gestao de Leads
- Filtros avancados: tier, status, fonte, categoria, score, periodo
- Filtros de contato: "Com Email", "Com Telefone", "Com Site"
- Presets rapidos: "Quentes Novos", "Esta Semana", "Sem Contato"
- Pesquisa expandida (nome, email, telefone, site, whatsapp, notas)
- Bulk operations: delete, status change, export, delete by score
- Pipeline visual: novo → contatado → negociando → fechado

### Campanhas
- Meta de leads por campanha
- Auto-assign de leads existentes
- Extracao continua ate atingir goal
- Dry-round detection (para apos 2 batches vazios)

### Exportacao
- CSV (delimitador ;, UTF-8 BOM, locale BR)
- XLSX formatado (header colorido, tier highlight, auto-filter)
- Filtros por tier, status, quantidade, keyword

### Sistema
- Logs enriquecidos (progress bars, source badges, duracao, taxa sucesso)
- ParallelScheduler com 6 tasks independentes
- Job persistence (survives restart)
- Graceful shutdown
- RotatingFileHandler (5MB, 3 backups)
- Health endpoint com status completo

---

## Roadmap — Proximas Iteracoes

### v1.1 — Autenticacao e Multi-User
- [ ] Flask-Login + session management
- [ ] Roles (admin, vendedor, viewer)
- [ ] Audit trail por usuario
- [ ] Rate limiting por user

### v1.2 — WhatsApp Integration
- [ ] WhatsApp Business API (Cloud API)
- [ ] Templates de mensagem com variaveis
- [ ] Sequencia automatizada (msg1 → espera 24h → msg2)
- [ ] Status de entrega/leitura

### v1.3 — Webhooks e Integracoes
- [ ] Webhook export (Pipedrive, HubSpot, Salesforce)
- [ ] Zapier/Make trigger on new lead
- [ ] Import CSV/XLSX
- [ ] API key authentication

### v1.4 — Analytics Avancado
- [ ] Funil de conversao por fonte
- [ ] Revenue por campanha
- [ ] Tempo medio de fechamento
- [ ] ROI por keyword
- [ ] Heatmap de melhor horario de captacao

### v1.5 — Mobile PWA
- [ ] Progressive Web App (offline-capable)
- [ ] Push notifications (novo lead hot)
- [ ] Interface touch-optimized
- [ ] Quick actions (WhatsApp, ligar)

### v2.0 — SaaS Multi-Tenant
- [ ] Organizacoes / workspaces
- [ ] Planos (free/starter/pro/enterprise)
- [ ] Billing (Stripe BR)
- [ ] Custom domains
- [ ] API rate limits por plano
- [ ] Onboarding wizard

---

## API Reference

### Leads
| Method | Endpoint | Descricao |
|--------|----------|-----------|
| GET | /api/leads | Listar com filtros (tier, status, source, search, score_min/max, has_email/phone/website, created_days) |
| GET | /api/leads/:id | Detalhes |
| PATCH | /api/leads/:id | Mudar status |
| PATCH | /api/leads/:id/close | Fechar negocio (deal_value, notes) |
| DELETE | /api/leads/:id | Excluir |
| POST | /api/leads/bulk-delete | Excluir em massa (ids) |
| POST | /api/leads/bulk-delete-by-score | Excluir por score (max_score, dry_run) |
| POST | /api/leads/bulk-contact | Marcar como contatado (tier, limit) |
| GET | /api/leads/today | Contagem hoje |
| GET | /api/leads/pipeline | Contagem por status |
| GET | /api/leads/categories | Categorias unicas |

### Extracoes
| Method | Endpoint | Descricao |
|--------|----------|-----------|
| POST | /api/crawl | Iniciar extracao (query, source, quantity, location) |
| GET | /api/crawl/status/:uuid | Progresso do job |
| GET | /api/crawl/history | Historico paginado |
| POST | /api/crawl/retry/:id | Refazer extracao |
| POST | /api/crawl/abort/:uuid | Abortar |
| POST | /api/crawl/cleanup | Limpar jobs travados |

### Dashboard
| Method | Endpoint | Descricao |
|--------|----------|-----------|
| GET | /api/dashboard/stats | Metricas gerais |
| GET | /api/dashboard/chart-data | Dados para graficos |
| GET | /api/dashboard/activity | Feed de atividade em tempo real |
| GET | /api/dashboard/recent-jobs | Ultimas extracoes |
| GET | /api/system/health | Status do scheduler + pipeline |

### Campanhas
| Method | Endpoint | Descricao |
|--------|----------|-----------|
| GET | /api/campaigns | Listar |
| POST | /api/campaigns | Criar |
| PATCH | /api/campaigns/:id | Editar |
| DELETE | /api/campaigns/:id | Excluir |
| POST | /api/campaigns/:id/extract | Iniciar extracao |
| GET | /api/campaigns/:id/leads | Leads da campanha |

### Real-Time
| Method | Endpoint | Descricao |
|--------|----------|-----------|
| GET | /api/events/stream | SSE stream (lead_new, job_progress, job_completed, stats_updated, etc) |

---

## Metricas de Sucesso

- **Taxa de extracao:** >50% dos leads brutos passam validacao
- **Deduplicacao:** <5% de duplicatas no banco
- **Uptime scheduler:** >99% (graceful restart, cleanup automatico)
- **Latencia SSE:** <2s entre evento e update no frontend
- **Score accuracy:** Leads hot (80+) tem email + telefone em >90% dos casos
