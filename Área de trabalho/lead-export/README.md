# LeadHunter

Web crawler profissional para extração de leads qualificados a partir de websites e Google Maps. Dashboard analytics com scoring, filtros, validação de sites e exportação CSV/XLSX.

## Features

- **Extração por palavra-chave** — busca em Google Search e Google Maps
- **Lead Scoring 0-100** — classifica leads em Hot (80+), Warm (50-79), Cold (<50)
- **Validação de sites** — verifica HTTP 200 + certificado SSL automaticamente
- **Banco de dados SQLite** — persistência completa com deduplicação
- **Dashboard analytics** — stats, gráficos Chart.js, histórico
- **Exportação** — CSV e XLSX com filtros por tier, status, quantidade
- **Scheduler** — atualiza verificação de sites a cada 1 hora
- **UI Glassmorphism** — design system preto + azul, animações GSAP

## Instalação

```bash
# Clone o projeto
cd lead-export

# Crie um virtual environment
python3 -m venv venv
source venv/bin/activate

# Instale dependências
pip install -r requirements.txt

# Configure variáveis de ambiente
cp .env.example .env
# Edite .env com sua GOOGLE_PLACES_API_KEY
```

## Como Usar

```bash
source venv/bin/activate
python app.py
# Acesse http://localhost:5000
```

### Fluxo de Uso

1. **Dashboard** (`/dashboard`) — visão geral de métricas
2. **Nova Extração** (`/crawl`) — insira keyword + localização + quantidade
3. **Leads** (`/leads`) — veja, filtre e gerencie leads capturados
4. **Exportar** (`/export`) — selecione filtros e baixe CSV/XLSX

## Arquitetura

```
┌─────────────┐     ┌──────────────┐     ┌─────────────┐
│   Frontend  │────▸│  Flask API   │────▸│   SQLite    │
│  (Tailwind  │     │ (Blueprints) │     │  (WAL mode) │
│  + Chart.js │     └──────┬───────┘     └─────────────┘
│  + GSAP)    │            │
└─────────────┘     ┌──────┴───────┐
                    │   Crawlers   │
                    ├──────────────┤
                    │ WebsiteCrawl │──▸ Google Search → Parse HTML
                    │ MapsCrawler  │──▸ Places API / Selenium
                    │ SiteVerifier │──▸ HTTP + SSL check
                    │ LeadScorer   │──▸ Score 0-100
                    └──────────────┘
```

## API Reference

### Dashboard
| Método | Endpoint | Descrição |
|--------|----------|-----------|
| GET | `/api/dashboard/stats` | Totais por tier, com email/phone/whatsapp |
| GET | `/api/dashboard/chart-data` | Leads/dia (30 dias) + distribuição tier |
| GET | `/api/dashboard/recent-jobs` | Últimas 5 extrações |

### Leads
| Método | Endpoint | Descrição |
|--------|----------|-----------|
| GET | `/api/leads?page=1&tier=hot&status=new&search=&sort=score` | Lista paginada |
| PATCH | `/api/leads/<id>` | Atualizar status (new/contacted/converted) |
| DELETE | `/api/leads/<id>` | Excluir lead |
| POST | `/api/leads/bulk-delete` | Excluir múltiplos `{"ids": [1,2,3]}` |

### Crawl
| Método | Endpoint | Descrição |
|--------|----------|-----------|
| POST | `/api/crawl` | Iniciar extração `{"source","query","location","quantity"}` |
| GET | `/api/crawl/status/<uuid>` | Progresso do job |
| GET | `/api/crawl/history` | Histórico paginado |

### Export
| Método | Endpoint | Descrição |
|--------|----------|-----------|
| POST | `/api/export` | Download `{"format":"csv","tier":"hot,warm","status":"new","quantity":100}` |
| POST | `/api/export/preview` | Conta leads que batem os filtros |

## Lead Scoring

| Critério | Pontos |
|----------|--------|
| Email válido | +25 |
| Telefone válido | +20 |
| WhatsApp confirmado | +20 |
| Site verificado (HTTP 200) | +15 |
| Categoria definida | +10 |
| Nome completo (2+ palavras) | +10 |

**Tiers:** Hot (80-100) | Warm (50-79) | Cold (<50)

## Configuração (.env)

```env
GOOGLE_PLACES_API_KEY=sua_key_aqui    # Google Cloud → Places API habilitada
FLASK_SECRET_KEY=qualquer-string      # Secret para sessões Flask
MAX_CONCURRENT_REQUESTS=5             # Workers para validação de sites
REQUEST_DELAY_MS=1500                 # Delay entre requests (ms)
```

### Google Maps API Setup

1. Acesse [Google Cloud Console](https://console.cloud.google.com/)
2. Crie um projeto ou selecione existente
3. Ative a **Places API** (não a "Places API New")
4. Crie uma API Key em Credentials
5. Cole no `.env` como `GOOGLE_PLACES_API_KEY`

## Testes

```bash
pip install pytest pytest-playwright
playwright install chromium
pytest tests/ -v
```

## Stack Técnica

- **Backend:** Python 3 + Flask + SQLite (WAL mode)
- **Frontend:** Tailwind CSS + Chart.js + Lucide Icons + GSAP
- **Crawling:** Requests + BeautifulSoup + Selenium (fallback)
- **Validação:** phonenumbers + email-validator + HTTP/SSL checks

## Estrutura de Arquivos

```
├── app.py              # App factory + blueprints
├── config.py           # Configuração centralizada
├── database.py         # SQLite schema + conexão
├── models.py           # Data access (Lead, CrawlJob, Keyword)
├── scheduler.py        # Cron job de verificação (1h)
├── blueprints/         # Rotas organizadas por domínio
├── crawler/            # Crawlers + validator + scorer + exporter
├── templates/          # Jinja2 HTML (base + 4 páginas)
├── static/css/         # Design system CSS
├── static/js/          # Lógica frontend (fetch + charts + GSAP)
├── tests/              # Playwright E2E tests
└── data/leads.db       # Banco SQLite (auto-criado)
```

## Troubleshooting

| Problema | Solução |
|----------|---------|
| Google Maps retorna REQUEST_DENIED | Ative a "Places API" no Google Cloud Console |
| Leads sem website | O crawler captura se disponível; sem website = score menor |
| Excluir lead não funciona | Verifique que está rodando a versão mais recente do app.py |
| Selenium não funciona | Instale Chrome/Chromium; o WebDriver Manager baixa automaticamente |
| Porta 5000 ocupada | Use `python app.py` com env `FLASK_RUN_PORT=5001` |
