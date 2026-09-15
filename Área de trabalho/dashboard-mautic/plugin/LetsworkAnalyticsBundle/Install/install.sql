-- LetsworkAnalyticsBundle — Script de instalação
-- Execute este SQL no banco do Mautic ANTES de ativar o plugin
-- Ou crie os campos via Mautic Admin > Custom Fields

ALTER TABLE leads ADD COLUMN IF NOT EXISTS temperatura VARCHAR(20) DEFAULT NULL;
ALTER TABLE leads ADD COLUMN IF NOT EXISTS origem_principal VARCHAR(100) DEFAULT NULL;
ALTER TABLE leads ADD COLUMN IF NOT EXISTS segmento VARCHAR(100) DEFAULT NULL;
ALTER TABLE leads ADD COLUMN IF NOT EXISTS interesse_score INT DEFAULT 0;
ALTER TABLE leads ADD COLUMN IF NOT EXISTS icp_score INT DEFAULT 0;
ALTER TABLE leads ADD COLUMN IF NOT EXISTS status_comercial VARCHAR(50) DEFAULT NULL;
ALTER TABLE leads ADD COLUMN IF NOT EXISTS motivo_perda VARCHAR(255) DEFAULT NULL;
ALTER TABLE leads ADD COLUMN IF NOT EXISTS modulo_interesse VARCHAR(100) DEFAULT NULL;
ALTER TABLE leads ADD COLUMN IF NOT EXISTS dor_principal VARCHAR(255) DEFAULT NULL;
ALTER TABLE leads ADD COLUMN IF NOT EXISTS data_ultima_interacao DATETIME DEFAULT NULL;
ALTER TABLE leads ADD COLUMN IF NOT EXISTS data_proximo_followup DATETIME DEFAULT NULL;
ALTER TABLE leads ADD COLUMN IF NOT EXISTS linkedin_status VARCHAR(50) DEFAULT NULL;
ALTER TABLE leads ADD COLUMN IF NOT EXISTS linkedin_respondeu TINYINT(1) DEFAULT 0;
ALTER TABLE leads ADD COLUMN IF NOT EXISTS linkedin_intencao VARCHAR(50) DEFAULT NULL;
ALTER TABLE leads ADD COLUMN IF NOT EXISTS cargo VARCHAR(100) DEFAULT NULL;
ALTER TABLE leads ADD COLUMN IF NOT EXISTS valor_potencial DECIMAL(10,2) DEFAULT NULL;
ALTER TABLE leads ADD COLUMN IF NOT EXISTS lead_incompleto VARCHAR(10) DEFAULT NULL;
ALTER TABLE leads ADD COLUMN IF NOT EXISTS motivo_lead_incompleto VARCHAR(100) DEFAULT NULL;
ALTER TABLE leads ADD COLUMN IF NOT EXISTS proxima_acao VARCHAR(255) DEFAULT NULL;
ALTER TABLE leads ADD COLUMN IF NOT EXISTS objecao_identificada VARCHAR(255) DEFAULT NULL;
ALTER TABLE leads ADD COLUMN IF NOT EXISTS dor_identificada VARCHAR(255) DEFAULT NULL;

-- Campos de origem (PRD §7)
ALTER TABLE leads ADD COLUMN IF NOT EXISTS linkedin_url VARCHAR(255) DEFAULT NULL;
ALTER TABLE leads ADD COLUMN IF NOT EXISTS origens TEXT DEFAULT NULL;
ALTER TABLE leads ADD COLUMN IF NOT EXISTS campanha_origem VARCHAR(100) DEFAULT NULL;
ALTER TABLE leads ADD COLUMN IF NOT EXISTS data_primeira_origem DATETIME DEFAULT NULL;
ALTER TABLE leads ADD COLUMN IF NOT EXISTS data_ultima_origem DATETIME DEFAULT NULL;

-- Campos LinkedIn adicionais (PRD §7)
ALTER TABLE leads ADD COLUMN IF NOT EXISTS linkedin_ultima_interacao DATETIME DEFAULT NULL;
ALTER TABLE leads ADD COLUMN IF NOT EXISTS linkedin_ultima_mensagem TEXT DEFAULT NULL;
ALTER TABLE leads ADD COLUMN IF NOT EXISTS linkedin_ultima_resposta TEXT DEFAULT NULL;

-- Campos comerciais adicionais (PRD §7)
ALTER TABLE leads ADD COLUMN IF NOT EXISTS responsavel_marketing VARCHAR(100) DEFAULT NULL;
ALTER TABLE leads ADD COLUMN IF NOT EXISTS responsavel_comercial VARCHAR(100) DEFAULT NULL;
ALTER TABLE leads ADD COLUMN IF NOT EXISTS responsavel_recebimento_lead INT DEFAULT NULL;
ALTER TABLE leads ADD COLUMN IF NOT EXISTS ultima_reuniao_resumo TEXT DEFAULT NULL;
ALTER TABLE leads ADD COLUMN IF NOT EXISTS ultima_reuniao_dores TEXT DEFAULT NULL;
ALTER TABLE leads ADD COLUMN IF NOT EXISTS ultima_reuniao_objecoes TEXT DEFAULT NULL;
ALTER TABLE leads ADD COLUMN IF NOT EXISTS ultima_reuniao_proximos_passos TEXT DEFAULT NULL;
ALTER TABLE leads ADD COLUMN IF NOT EXISTS ultima_reuniao_transcricao_url VARCHAR(500) DEFAULT NULL;

-- Nota: MySQL 8.0+ suporta IF NOT EXISTS no ALTER TABLE
-- =====================================================
-- Tabela: Cadências (Sequences)
-- =====================================================
CREATE TABLE IF NOT EXISTS letswork_cadencias (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    descricao TEXT,
    steps JSON NOT NULL COMMENT '[{day:0,channel:"email",template:""},{day:2,channel:"linkedin"}]',
    is_active TINYINT(1) DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS letswork_cadencia_leads (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cadencia_id INT NOT NULL,
    lead_id INT NOT NULL,
    current_step INT DEFAULT 0,
    status ENUM('ativa','pausada','concluida','respondeu') DEFAULT 'ativa',
    started_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    next_action_at DATETIME,
    completed_at DATETIME,
    INDEX idx_cadencia_lead (cadencia_id, lead_id),
    INDEX idx_status (status),
    INDEX idx_next_action (next_action_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Para MySQL 5.7 ou MariaDB, remova "IF NOT EXISTS" e ignore erros de "Duplicate column"

-- =====================================================
-- Tabelas nativas do Mautic utilizadas pelo plugin
-- (já existem na instalação padrão — NÃO criar):
-- =====================================================
-- leads              → Base central de contatos
-- lead_notes         → Notas/timeline do lead
-- lead_lists         → Segmentos (listas) do Mautic
-- lead_lists_leads   → Associação lead ↔ segmento
-- lead_tags          → Tags disponíveis
-- lead_tags_xref     → Associação lead ↔ tag
-- email_stats        → Estatísticas de e-mail (envios, aberturas)
-- emails             → Campanhas de e-mail
-- page_hits          → Visitas a páginas rastreadas
-- form_submissions   → Formulários preenchidos
-- asset_downloads    → Downloads de materiais
-- campaigns          → Campanhas de automação
-- campaign_leads     → Leads em campanhas
-- notifications      → Notificações internas
