import os
import sqlite3
from config import Config


def get_connection():
    conn = sqlite3.connect(Config.DATABASE_PATH)
    conn.row_factory = sqlite3.Row
    conn.execute("PRAGMA journal_mode=WAL")
    conn.execute("PRAGMA synchronous=NORMAL")
    conn.execute("PRAGMA cache_size=-128000") # 128MB cache
    conn.execute("PRAGMA temp_store=MEMORY")
    conn.execute("PRAGMA mmap_size=268435456") # 256MB MMAP
    return conn



def init_db():
    conn = get_connection()
    cursor = conn.cursor()

    cursor.executescript("""
        CREATE TABLE IF NOT EXISTS crawl_jobs (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            job_uuid TEXT UNIQUE NOT NULL,
            keyword TEXT NOT NULL,
            location TEXT DEFAULT '',
            source TEXT NOT NULL,
            quantity_requested INTEGER NOT NULL,
            quantity_found INTEGER DEFAULT 0,
            status TEXT DEFAULT 'pending',
            progress INTEGER DEFAULT 0,
            errors TEXT DEFAULT '[]',
            started_at TEXT DEFAULT (datetime('now', 'localtime')),
            completed_at TEXT
        );

        CREATE TABLE IF NOT EXISTS leads (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            email TEXT DEFAULT '',
            phone TEXT DEFAULT '',
            whatsapp TEXT DEFAULT '',
            website TEXT DEFAULT '',
            category TEXT DEFAULT '',
            source TEXT DEFAULT '',
            score INTEGER DEFAULT 0,
            tier TEXT DEFAULT 'cold',
            site_verified INTEGER DEFAULT 0,
            ssl_valid INTEGER DEFAULT 0,
            status TEXT DEFAULT 'new',
            crawl_job_id INTEGER REFERENCES crawl_jobs(id),
            dedup_hash TEXT UNIQUE,
            campaign TEXT DEFAULT '',
            instagram TEXT DEFAULT '',
            facebook TEXT DEFAULT '',
            site_quality TEXT DEFAULT '',
            location TEXT DEFAULT '',
            created_at TEXT DEFAULT (datetime('now', 'localtime')),
            updated_at TEXT DEFAULT (datetime('now', 'localtime'))
        );

        CREATE TABLE IF NOT EXISTS keywords (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            keyword TEXT NOT NULL,
            location TEXT DEFAULT '',
            times_used INTEGER DEFAULT 1,
            total_leads_found INTEGER DEFAULT 0,
            last_used_at TEXT DEFAULT (datetime('now', 'localtime'))
        );

        CREATE INDEX IF NOT EXISTS idx_leads_score ON leads(score DESC);
        CREATE INDEX IF NOT EXISTS idx_leads_tier ON leads(tier);
        CREATE INDEX IF NOT EXISTS idx_leads_status ON leads(status);
        CREATE INDEX IF NOT EXISTS idx_leads_created ON leads(created_at DESC);
        CREATE INDEX IF NOT EXISTS idx_leads_campaign ON leads(campaign);
        CREATE INDEX IF NOT EXISTS idx_leads_category ON leads(category);
        CREATE INDEX IF NOT EXISTS idx_leads_website ON leads(website) WHERE website != '';
        CREATE INDEX IF NOT EXISTS idx_leads_verified ON leads(site_verified) WHERE site_verified = 0;
        CREATE INDEX IF NOT EXISTS idx_leads_instagram ON leads(instagram) WHERE instagram = '' OR instagram IS NULL;
        CREATE INDEX IF NOT EXISTS idx_crawl_jobs_status ON crawl_jobs(status);
        CREATE INDEX IF NOT EXISTS idx_keywords_keyword ON keywords(keyword, location);
    """)

    _migrate(cursor)
    conn.commit()
    conn.close()


def _migrate(cursor):
    columns = {row[1] for row in cursor.execute("PRAGMA table_info(leads)").fetchall()}

    migrations = [
        ("deal_value", "ALTER TABLE leads ADD COLUMN deal_value REAL DEFAULT 0"),
        ("deal_notes", "ALTER TABLE leads ADD COLUMN deal_notes TEXT DEFAULT ''"),
        ("contacted_at", "ALTER TABLE leads ADD COLUMN contacted_at TEXT"),
        ("closed_at", "ALTER TABLE leads ADD COLUMN closed_at TEXT"),
        ("notes", "ALTER TABLE leads ADD COLUMN notes TEXT DEFAULT ''"),
        ("tags", "ALTER TABLE leads ADD COLUMN tags TEXT DEFAULT ''"),
        ("campaign", "ALTER TABLE leads ADD COLUMN campaign TEXT DEFAULT ''"),
        ("next_action", "ALTER TABLE leads ADD COLUMN next_action TEXT DEFAULT ''"),
        ("next_action_at", "ALTER TABLE leads ADD COLUMN next_action_at TEXT"),
        ("instagram", "ALTER TABLE leads ADD COLUMN instagram TEXT DEFAULT ''"),
        ("facebook", "ALTER TABLE leads ADD COLUMN facebook TEXT DEFAULT ''"),
        ("site_quality", "ALTER TABLE leads ADD COLUMN site_quality TEXT DEFAULT ''"),
        ("location", "ALTER TABLE leads ADD COLUMN location TEXT DEFAULT ''"),
        ("cnpj", "ALTER TABLE leads ADD COLUMN cnpj TEXT"),
        ("partners", "ALTER TABLE leads ADD COLUMN partners TEXT"),
        ("company_value", "ALTER TABLE leads ADD COLUMN company_value REAL"),
        ("legal_nature", "ALTER TABLE leads ADD COLUMN legal_nature TEXT"),
        ("cms", "ALTER TABLE leads ADD COLUMN cms TEXT"),
        ("has_pixel", "ALTER TABLE leads ADD COLUMN has_pixel INTEGER DEFAULT 0"),
        ("has_analytics", "ALTER TABLE leads ADD COLUMN has_analytics INTEGER DEFAULT 0"),
        ("has_ads", "ALTER TABLE leads ADD COLUMN has_ads INTEGER DEFAULT 0"),
        ("ads_platforms", "ALTER TABLE leads ADD COLUMN ads_platforms TEXT DEFAULT ''"),
        ("tech_score", "ALTER TABLE leads ADD COLUMN tech_score INTEGER DEFAULT 0"),
        ("is_qualified", "ALTER TABLE leads ADD COLUMN is_qualified INTEGER DEFAULT 0"),
        ("pitch_hook", "ALTER TABLE leads ADD COLUMN pitch_hook TEXT DEFAULT ''"),
    ]

    for col, sql in migrations:
        if col not in columns:
            cursor.execute(sql)

    cursor.executescript("""
        CREATE TABLE IF NOT EXISTS lead_activities (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            lead_id INTEGER NOT NULL REFERENCES leads(id) ON DELETE CASCADE,
            action TEXT NOT NULL,
            details TEXT DEFAULT '',
            created_at TEXT DEFAULT (datetime('now', 'localtime'))
        );
        CREATE INDEX IF NOT EXISTS idx_activities_lead ON lead_activities(lead_id, created_at DESC);

        CREATE TABLE IF NOT EXISTS message_templates (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            channel TEXT NOT NULL DEFAULT 'whatsapp',
            subject TEXT DEFAULT '',
            body TEXT NOT NULL,
            variables TEXT DEFAULT '[]',
            created_at TEXT DEFAULT (datetime('now', 'localtime'))
        );

        CREATE TABLE IF NOT EXISTS campaigns (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            keyword TEXT DEFAULT '',
            location TEXT DEFAULT '',
            goal INTEGER DEFAULT 100,
            status TEXT DEFAULT 'active',
            created_at TEXT DEFAULT (datetime('now', 'localtime'))
        );
    """)

    camp_columns = {row[1] for row in cursor.execute("PRAGMA table_info(campaigns)").fetchall()}
    camp_migrations = [
        ("updated_at", "ALTER TABLE campaigns ADD COLUMN updated_at TEXT"),
        ("description", "ALTER TABLE campaigns ADD COLUMN description TEXT DEFAULT ''"),
        ("last_extracted_at", "ALTER TABLE campaigns ADD COLUMN last_extracted_at TEXT"),
        ("extraction_status", "ALTER TABLE campaigns ADD COLUMN extraction_status TEXT DEFAULT 'idle'"),
        ("expand_auto", "ALTER TABLE campaigns ADD COLUMN expand_auto INTEGER DEFAULT 0"),
    ]
    for col, sql in camp_migrations:
        if col not in camp_columns:
            cursor.execute(sql)

    job_columns = {row[1] for row in cursor.execute("PRAGMA table_info(crawl_jobs)").fetchall()}
    if "job_state" not in job_columns:
        cursor.execute("ALTER TABLE crawl_jobs ADD COLUMN job_state TEXT DEFAULT '{}'")
