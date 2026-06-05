# Changelog

## v2.2 (2026-06-04)

### Crawler Reliability
- **Timeout global de 4 min** — jobs nunca ficam stuck indefinidamente
- **Maps Crawler hardened** — timeout 90s, max 15 scrolls, break em 3 tentativas sem novos resultados
- **ThreadPoolExecutor reducido** — `as_completed(120s)` + `future.result(60s)` + cancel pending
- **active_jobs auto-cleanup** — entries removidas após 10min, stale detection no status endpoint
- **Scheduler cleanup 2min** — reduzido de 3min, limpa dict + DB

### Novas Funcionalidades
- **Retry de jobs** — `POST /api/crawl/retry/<id>` recria job com mesmos parâmetros
- **Abort forçado** — `POST /api/crawl/abort/<uuid>` mata job e remove do tracking
- **Cleanup em massa** — `POST /api/crawl/cleanup` marca todos stuck como error
- **Logs UI completa** — botões Retry (erro), Abort (running), Limpar Stuck, filtro por status, auto-refresh 15s
- **Status animado** — running com dot pulsante, shows found/requested

### Correções
- Fix: 8 jobs stuck em "running" limpos do DB
- Fix: 4 jobs com status 'erro' (inconsistente) normalizados para 'error'
- Fix: `TimeoutError` capturado gracefully no executor (não crasha o job)
- Fix: futures pendentes cancelados após timeout (não ficam rodando em background)

### Testes
- +3 unit tests (crawl_job create/fail, complete, cleanup_stuck)
- +4 E2E tests (crawl cleanup, abort, retry, logs UI actions)
- Total: 26 unit tests + 27 E2E tests

---

## v2.1 (2026-06-04)

### Funcionalidades Novas
- **Pipeline com Modal de Lead** — clique em qualquer lead para ver detalhes completos: score gauge, contatos (copiar), status dropdown, ações (WhatsApp, Email, Fechar Negócio), notas
- **Edição de Campanhas** — CRUD completo com modal: editar nome, keyword, localização e meta de leads
- **Extração Contínua** — campanhas extraem até atingir a meta (calcula restantes = meta - leads atuais)
- **Indicador de Meta Atingida** — badge visual quando campanha alcança 100%, botão extrair desabilitado
- **Modal de Leads por Campanha** — visualização em modal separado com score, tier e ações de contato
- **SEO & Acessibilidade** — meta description, og:tags, h1, aria-labels, roles semânticos

### Correções
- Fix: extração de campanha agora usa `min(30, restantes)` em vez de `min(30, meta)` no endpoint manual
- Fix: campanha não extrai quando meta já atingida (retorna erro 400 com mensagem)
- Fix: atualização de nome de campanha cascateia para leads associados
- Fix: stuck jobs agora limpam em 3min (antes 5min)
- Fix: paginação no pipeline com navegação prev/next

### Testes
- +8 testes unitários (campaign update, get_by_id, get_remaining, extraction logic, pipeline stats, status transitions)
- +10 testes E2E (pipeline page, campaigns CRUD, SEO validation, accessibility h1, logs page)
- Total: 23 unit tests + 24 E2E tests

### Infra
- Migration: colunas `updated_at`, `description`, `last_extracted_at`, `extraction_status` na tabela campaigns
- Modelo: `Campaign.update()`, `Campaign.get_by_id()`, `Campaign.get_remaining()`
- Endpoint: `PATCH /api/campaigns/<id>` para edição completa
- SEO: meta description, og:title, og:description, favicon, robots

---

## v2.0 (2026-06-04)

### Funcionalidades Principais
- **10 fontes de crawler** — DuckDuckGo, Bing, Google, Maps, Telelistas, GuiaMais, Cylex, HotFrog, Hagah, Enrichment
- **Search Engine Rotation** — fallback automático DDG → Bing → Google
- **Auto-Crawl 210+ leads/ciclo** — 7 keywords × 30 leads a cada 3h
- **Enriquecimento automático** — Instagram, Facebook, emails extras (30 leads/h)
- **Dedup inteligente** — por domínio, merge de contatos
- **Pipeline de vendas** — funil visual Novo→Contatado→Negociando→Fechado
- **Campanhas** — criar, extrair automaticamente, listar leads, excluir
- **Modal premium** — score gauge SVG, múltiplos contatos, copiar, status dropdown
- **Export com filtros** — tier, status, keyword, preview, CSV/XLSX
- **Glassmorphism** — background com gradients, glass cards, noise texture
- **Dashboard tempo real** — refresh 30s, gráfico 7+ dias, widget fontes

### Melhorias de UX
- Logo clicável → home
- Botão copiar email/phone na tabela
- Filtro por nicho/keyword nos leads
- Confirmação por modal glassmorphism (não alert nativo)
- Botão Exportar na dashboard e leads
- Ordenação por "mais recentes" como padrão
- Coluna data/hora na tabela de leads
- Tabs Pipeline e Campanhas no sidebar

### Infra & Qualidade
- 15 testes unitários (pytest)
- Testes E2E com Playwright
- Sentry SDK (opcional)
- Logging em arquivo + console
- Scheduler com persistência de estado
- Retry com backoff exponencial

---

## Próximos Passos

### Prioridade Alta
- [ ] Sequência automática de contato (WhatsApp template → espera 24h → email)
- [ ] Score dinâmico com decaimento temporal
- [ ] Verificação de email via SMTP (não só formato)
- [ ] Proxy rotation para crawlers em escala

### Prioridade Média
- [ ] Multi-usuário com login
- [ ] Integração CRM (HubSpot, Pipedrive)
- [ ] Slack/Telegram notificações de novos leads hot
- [ ] Relatório diário por email
- [ ] Custom fields por lead

### Prioridade Baixa
- [ ] Dark/light theme toggle
- [ ] Importar CSV de leads existentes
- [ ] API pública com token
- [ ] Zapier/Webhook integrations
- [ ] Mobile PWA
