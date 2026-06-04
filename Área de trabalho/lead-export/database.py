import os
import sqlite3
from config import Config


def get_connection():
    os.makedirs(os.path.dirname(Config.DATABASE_PATH), exist_ok=True)
    conn = sqlite3.connect(Config.DATABASE_PATH, timeout=30)
    conn.row_factory = sqlite3.Row
    conn.execute("PRAGMA journal_mode=WAL")
    conn.execute("PRAGMA foreign_keys=ON")
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
