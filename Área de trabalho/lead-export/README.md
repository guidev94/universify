# LeadHunter

Sistema profissional de extração e gestão de leads com crawlers multi-fonte, dashboard glassmorphism, scoring inteligente, pipeline de vendas, campanhas automatizadas e automação completa.

## Features

- **10 Crawlers simultâneos** — DuckDuckGo, Bing, Google, Maps (Selenium), Diretórios BR, Nicho, Yellow Pages, Enrichment
- **Search Engine Rotation** — fallback automático DDG → Bing → Google
- **Lead Scoring 0-100** — Quente (80+), Morno (50-79), Frio (<50)
- **Dedup inteligente** — por domínio, merge de contatos (múltiplos emails por site)
- **Auto-Crawl** — a cada 3h busca 7 keywords × 30 leads = 210+ leads/ciclo
- **Dashboard glassmorphism** — stats tempo real, gráficos, refresh 30s
- **Pipeline de vendas** — funil visual com modal detalhado, troca de status inline, ações rápidas
- **Campanhas** — criar, editar, extrair automaticamente até atingir meta, visualizar leads
- **Modal premium** — score gauge SVG, múltiplos contatos, copiar, status dropdown, notas
- **Exportação** — CSV/XLSX com filtros por tier, status, keyword, preview
- **Verificação automática** — HTTP + SSL a cada 1h (100 sites/ciclo)
- **Enriquecimento** — Instagram, Facebook, emails adicionais (30 leads/h)
- **Logging** — arquivo + console + Sentry (opcional)

## Requisitos

- Python 3.10+
- Chrome/Chromium (opcional, para crawler Maps via Selenium)

## Instalação

```bash
cd lead-export
pip install -r requirements.txt
python3 app.py
```

Acesse http://localhost:5000

## Variáveis de Ambiente (.env)

| Variável | Padrão | Descrição |
|----------|--------|-----------|
| `FLASK_SECRET_KEY` | dev-secret | Chave de sessão |
| `REQUEST_DELAY_MS` | 1500 | Delay entre requests (ms) |
| `MAX_CONCURRENT_REQUESTS` | 5 | Workers paralelos |
| `SCHEDULER_CRAWL_INTERVAL` | 10800 | Intervalo auto-crawl (3h) |
| `SENTRY_DSN` | (vazio) | DSN Sentry para erros |

## Testes

```bash
# Unitários (23 testes)
python3 -m pytest tests/test_models.py -v

# E2E com Playwright (24 testes)
pip install playwright pytest-playwright
playwright install chromium
python3 -m pytest tests/test_app.py -v

# Todos
python3 -m pytest -v
```

## API

| Método | Endpoint | Descrição |
|--------|----------|-----------|
| GET | `/api/dashboard/stats` | Métricas gerais |
| GET | `/api/dashboard/chart-data` | Dados gráficos (30d) |
| GET | `/api/leads?page=1&tier=hot` | Listar leads com filtros |
| GET | `/api/leads/:id` | Detalhes lead |
| PATCH | `/api/leads/:id` | Alterar status |
| PATCH | `/api/leads/:id/close` | Fechar negócio |
| PATCH | `/api/leads/:id/notes` | Atualizar notas |
| DELETE | `/api/leads/:id` | Excluir lead |
| POST | `/api/leads/bulk-delete` | Excluir múltiplos |
| GET | `/api/leads/pipeline` | Stats por estágio |
| GET | `/api/campaigns` | Listar campanhas |
| POST | `/api/campaigns` | Criar campanha |
| PATCH | `/api/campaigns/:id` | Editar campanha |
| DELETE | `/api/campaigns/:id` | Excluir campanha |
| POST | `/api/campaigns/:id/extract` | Extrair leads |
| GET | `/api/campaigns/:id/leads` | Leads da campanha |
| POST | `/api/crawl` | Iniciar extração |
| GET | `/api/crawl/status/:uuid` | Status do job |
| POST | `/api/export` | Exportar CSV/XLSX |
| GET | `/api/system/health` | Status scheduler |

## Produção

```bash
pip install gunicorn
gunicorn -w 2 -b 0.0.0.0:5000 app:app
```

Ou usar `./run.sh` (reinicia automaticamente).

## Arquitetura

```
app.py → Flask + Sentry
scheduler.py → Auto-crawl 3h + Verificação 1h + Cleanup stuck jobs
crawler/ → 10 fontes (DDG, Bing, Google, Maps, Diretórios, Nicho, YP, Enrichment)
models.py → Lead, CrawlJob, Campaign, Keyword, MessageTemplate
blueprints/ → dashboard, leads, crawl, export
templates/ → Jinja2 (dashboard, leads, pipeline, campaigns, crawl, export, logs)
static/ → CSS glassmorphism + JS (animations, charts, GSAP)
tests/ → pytest unitários (23) + Playwright E2E (24)
```

## Pipeline de Vendas

```
Novo → Contatado → Negociando → Fechado (Won/Lost)
```

Clique em qualquer lead no pipeline para abrir o modal com ações completas.

## Campanhas

1. Crie uma campanha com palavra-chave, localização e meta de leads
2. O sistema extrai leads automaticamente (30 por batch) até atingir a meta
3. Edite a campanha a qualquer momento para ajustar meta ou keyword
4. Acompanhe o progresso em tempo real com barra e indicadores
