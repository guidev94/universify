# Changelog

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
