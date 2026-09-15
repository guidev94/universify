import os
import sqlite3
import threading
import logging
from queue import Queue, Empty
from config import Config

logger = logging.getLogger("leadhunter.database")

# ─────────────────────────────────────────────────────────────────────────────
# Connection Pool para SQLite — evita overhead de PRAGMAs repetidos e
# mantém conexões reutilizáveis por thread.
# ─────────────────────────────────────────────────────────────────────────────

class ConnectionPool:
    """Thread-safe SQLite connection pool com configuração lazy de PRAGMAs."""

    def __init__(self, database_path, pool_size=8, timeout=60):
        self._database_path = database_path
        self._pool_size = pool_size
        self._timeout = timeout
        self._pool = Queue(maxsize=pool_size)
        self._lock = threading.Lock()
        self._created = 0
        self._initialized = False

    def _create_connection(self):
        """Cria uma nova conexão com PRAGMAs otimizados para performance extrema."""
        conn = sqlite3.connect(self._database_path, timeout=self._timeout)
        conn.row_factory = sqlite3.Row
        
        # Otimizações críticas para SQLite em ambiente multi-thread
        conn.execute("PRAGMA journal_mode=WAL")          # Permite leituras concorrentes com escrita
        conn.execute("PRAGMA synchronous=NORMAL")      # Equilíbrio entre segurança e velocidade (NORMAL é seguro em WAL)
        conn.execute("PRAGMA cache_size=-128000")       # 128MB cache (em KB, valor negativo)
        conn.execute("PRAGMA temp_store=MEMORY")        # Tabelas temporárias na RAM
        conn.execute("PRAGMA mmap_size=268435456")      # 256MB Memory Map para I/O ultra rápido
        conn.execute("PRAGMA busy_timeout=60000")       # 60s timeout para evitar SQLITE_BUSY imediato
        conn.execute("PRAGMA wal_autocheckpoint=1000")  # Checkpoint do log a cada 1000 páginas
        conn.execute("PRAGMA foreign_keys=ON")          # Garante integridade referencial
        return conn

    def get(self):
        """Obtém uma conexão do pool ou cria uma nova."""
        try:
            conn = self._pool.get_nowait()
            # Testa se a conexão ainda é válida
            try:
                conn.execute("SELECT 1")
                return conn
            except Exception:
                # Conexão morta, cria nova
                with self._lock:
                    self._created -= 1
        except Empty:
            pass

        # Criar nova conexão se dentro do limite
        with self._lock:
            if self._created < self._pool_size:
                self._created += 1
                return self._create_connection()

        # Pool cheio — espera por uma conexão disponível
        try:
            conn = self._pool.get(timeout=self._timeout)
            try:
                conn.execute("SELECT 1")
                return conn
            except Exception:
                with self._lock:
                    self._created -= 1
                return self._create_connection()
        except Empty:
            # Fallback: cria mesmo assim para não travar o sistema
            logger.warning("[db-pool] Pool exausto, criando conexão extra")
            return self._create_connection()

    def put(self, conn):
        """Devolve uma conexão ao pool."""
        try:
            self._pool.put_nowait(conn)
        except Exception:
            # Pool cheio, fecha a conexão extra
            try:
                conn.close()
            except Exception:
                pass
            with self._lock:
                self._created -= 1

    def close_all(self):
        """Fecha todas as conexões do pool."""
        while not self._pool.empty():
            try:
                conn = self._pool.get_nowait()
                conn.close()
            except Exception:
                pass
        with self._lock:
            self._created = 0


# Pool global singleton
_pool = None
_pool_lock = threading.Lock()


def _get_pool():
    global _pool
    if _pool is None:
        with _pool_lock:
            if _pool is None:
                os.makedirs(os.path.dirname(Config.DATABASE_PATH), exist_ok=True)
                _pool = ConnectionPool(Config.DATABASE_PATH, pool_size=32, timeout=60)
    return _pool


def get_connection():
    """Retorna uma conexão do pool. IMPORTANTE: sempre chamar conn.close() ao terminar."""
    return PooledConnection(_get_pool())


class PooledConnection:
    """Wrapper que devolve a conexão ao pool quando close() é chamado."""

    def __init__(self, pool):
        self._pool = pool
        self._conn = pool.get()
        self._closed = False

    def execute(self, *args, **kwargs):
        return self._conn.execute(*args, **kwargs)

    def executemany(self, *args, **kwargs):
        return self._conn.executemany(*args, **kwargs)

    def executescript(self, *args, **kwargs):
        return self._conn.executescript(*args, **kwargs)

    def commit(self):
        self._conn.commit()

    def rollback(self):
        self._conn.rollback()

    def close(self):
        if not self._closed:
            self._closed = True
            try:
                self._conn.rollback()  # Rollback qualquer transação pendente
            except Exception:
                pass
            self._pool.put(self._conn)

    def cursor(self):
        return self._conn.cursor()

    @property
    def row_factory(self):
        return self._conn.row_factory

    @row_factory.setter
    def row_factory(self, value):
        self._conn.row_factory = value

    def __enter__(self):
        return self

    def __exit__(self, exc_type, exc_val, exc_tb):
        if exc_type:
            self.rollback()
        else:
            self.commit()
        self.close()
        return False


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
        CREATE INDEX IF NOT EXISTS idx_leads_tier_score ON leads(tier, score DESC);
        CREATE INDEX IF NOT EXISTS idx_leads_status_score ON leads(status, score DESC);
        CREATE INDEX IF NOT EXISTS idx_leads_created ON leads(created_at DESC);
        CREATE INDEX IF NOT EXISTS idx_leads_campaign_score ON leads(campaign, score DESC);
        CREATE INDEX IF NOT EXISTS idx_leads_category_score ON leads(category, score DESC);
        CREATE INDEX IF NOT EXISTS idx_leads_website ON leads(website) WHERE website != '';
        CREATE INDEX IF NOT EXISTS idx_leads_whatsapp ON leads(whatsapp) WHERE whatsapp != '';
        CREATE INDEX IF NOT EXISTS idx_leads_email ON leads(email) WHERE email != '';
        CREATE INDEX IF NOT EXISTS idx_leads_ads_score ON leads(has_ads, score DESC);
        CREATE INDEX IF NOT EXISTS idx_leads_qualified_score ON leads(is_qualified, score DESC);
        CREATE INDEX IF NOT EXISTS idx_crawl_jobs_status ON crawl_jobs(status, started_at DESC);
        CREATE INDEX IF NOT EXISTS idx_keywords_keyword ON keywords(keyword, location);
        CREATE INDEX IF NOT EXISTS idx_leads_dedup ON leads(dedup_hash);
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
        # ── Sinais de qualidade de site para vendas de site/renovação ──
        ("site_status", "ALTER TABLE leads ADD COLUMN site_status TEXT DEFAULT ''"),      # live | offline
        ("ssl_expired", "ALTER TABLE leads ADD COLUMN ssl_expired INTEGER DEFAULT 0"),
        ("copyright_year", "ALTER TABLE leads ADD COLUMN copyright_year INTEGER DEFAULT 0"),
        ("is_responsive", "ALTER TABLE leads ADD COLUMN is_responsive INTEGER DEFAULT 1"),
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
        CREATE INDEX IF NOT EXISTS idx_leads_site_status ON leads(site_status, score DESC);

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
