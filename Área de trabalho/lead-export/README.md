# LeadHunter

Sistema profissional de extração e gestão de leads com crawlers multi-fonte, dashboard glassmorphism, scoring inteligente, e automação completa.

## Features

- **6 Crawlers simultâneos** — DuckDuckGo, Bing, Google, Maps (Selenium), Diretórios BR, Nicho
- **Search Engine Rotation** — fallback automático DDG → Bing → Google
- **Lead Scoring 0-100** — Quente (80+), Morno (50-79), Frio (<50)
- **Dedup inteligente** — por domínio, merge de contatos (múltiplos emails por site)
- **Auto-Crawl** — a cada 3h busca 7 keywords × 30 leads = 210+ leads/ciclo
- **Dashboard glassmorphism** — stats tempo real, gráficos, refresh 30s
- **Modal premium** — score visual gauge, múltiplos contatos, copiar, alterar status
- **Exportação** — CSV/XLSX com filtros por tier, status, keyword, preview
- **Verificação automática** — HTTP + SSL a cada 1h (100 sites/ciclo)
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
# Unitários (12 testes)
python3 -m pytest tests/test_models.py -v

# E2E com Playwright (requer instalação)
pip install playwright pytest-playwright
playwright install chromium
python3 -m pytest tests/test_app.py -v
```

## API

| Método | Endpoint | Descrição |
|--------|----------|-----------|
| GET | `/api/dashboard/stats` | Métricas |
| GET | `/api/leads?page=1&tier=hot` | Listar leads |
| GET | `/api/leads/:id` | Detalhes lead |
| PATCH | `/api/leads/:id` | Alterar status |
| POST | `/api/crawl` | Iniciar extração |
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
scheduler.py → Auto-crawl 3h + Verificação 1h
crawler/ → 6 fontes (DDG, Bing, Maps, Diretórios, Nicho)
models.py → Lead (dedup, scoring, stats, export)
blueprints/ → dashboard, leads, crawl, export
static/ → CSS glassmorphism + JS (animations, charts)
tests/ → pytest unitários + Playwright E2E
```
