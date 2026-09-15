import time
import json
import threading
import logging
from database import get_connection
from config import Config

logger = logging.getLogger("leadhunter.jobs")


class JobManager:
    """Thread-safe job state manager com cache em memória e persist batched.

    Otimizações:
    - Persist apenas em mudanças de estado significativas (não a cada update)
    - Cleanup mais agressivo (baseado em Config.JOB_STALE_TIMEOUT)
    - Batch eviction para não acumular timers
    """

    def __init__(self):
        self._lock = threading.Lock()
        self._cache = {}
        self._dirty = set()  # Jobs que precisam ser persistidos
        self._persist_timer = None
        self._start_persist_loop()

    def _start_persist_loop(self):
        """Persiste jobs dirty a cada 5s em batch (não a cada update)."""
        def _loop():
            while True:
                time.sleep(5)
                self._flush_dirty()
        t = threading.Thread(target=_loop, daemon=True, name="job-persist")
        t.start()

    def _flush_dirty(self):
        """Persiste todos os jobs marcados como dirty."""
        with self._lock:
            to_persist = list(self._dirty)
            self._dirty.clear()

        if not to_persist:
            return

        conn = get_connection()
        try:
            for job_uuid in to_persist:
                with self._lock:
                    state = self._cache.get(job_uuid)
                if state:
                    try:
                        conn.execute(
                            "UPDATE crawl_jobs SET job_state = ? WHERE job_uuid = ?",
                            (json.dumps(state), job_uuid),
                        )
                    except Exception:
                        pass
            conn.commit()
        except Exception as e:
            logger.debug(f"[jobs] batch persist error: {e}")
        finally:
            conn.close()

    def register(self, job_uuid, total, sources=None):
        state = {
            "status": "running",
            "progress": 0,
            "total": total,
            "sources": sources or {},
            "started_at": time.time(),
            "total_found": 0,
        }
        with self._lock:
            self._cache[job_uuid] = state
            self._dirty.add(job_uuid)

    def update(self, job_uuid, **kwargs):
        with self._lock:
            if job_uuid not in self._cache:
                self._cache[job_uuid] = self._load_from_db(job_uuid) or {}
            self._cache[job_uuid].update(kwargs)
            self._dirty.add(job_uuid)

    def get(self, job_uuid):
        with self._lock:
            if job_uuid in self._cache:
                return dict(self._cache[job_uuid])
        state = self._load_from_db(job_uuid)
        if state:
            with self._lock:
                self._cache[job_uuid] = state
        return state or {}

    def complete(self, job_uuid, found_count):
        with self._lock:
            if job_uuid in self._cache:
                self._cache[job_uuid]["status"] = "completed"
                self._cache[job_uuid]["progress"] = found_count
                self._cache[job_uuid]["total_found"] = found_count
                self._dirty.add(job_uuid)

        # Evict após 5 minutos
        threading.Timer(300, self._evict, args=(job_uuid,)).start()

    def fail(self, job_uuid):
        with self._lock:
            if job_uuid in self._cache:
                self._cache[job_uuid]["status"] = "error"
                self._dirty.add(job_uuid)
            else:
                self._cache[job_uuid] = {"status": "error"}
                self._dirty.add(job_uuid)

        threading.Timer(300, self._evict, args=(job_uuid,)).start()

    def get_all_running(self):
        with self._lock:
            return {k: dict(v) for k, v in self._cache.items() if v.get("status") == "running"}

    def cleanup_stale(self, max_age=None):
        """Marca jobs stale como erro. Usa Config.JOB_STALE_TIMEOUT por padrão."""
        if max_age is None:
            max_age = Config.JOB_STALE_TIMEOUT

        now = time.time()
        stale = []
        with self._lock:
            for uuid, state in list(self._cache.items()):
                started = state.get("started_at", 0)
                if state.get("status") == "running" and now - started > max_age:
                    stale.append(uuid)

        for uuid in stale:
            self.update(uuid, status="error")
            logger.warning(f"[jobs] marked stale job {uuid[:8]} as error (>{max_age}s)")

        return stale

    def _load_from_db(self, job_uuid):
        try:
            conn = get_connection()
            row = conn.execute(
                "SELECT job_state FROM crawl_jobs WHERE job_uuid = ?", (job_uuid,)
            ).fetchone()
            conn.close()
            if row and row["job_state"]:
                return json.loads(row["job_state"])
        except Exception:
            pass
        return None

    def _evict(self, job_uuid):
        with self._lock:
            self._cache.pop(job_uuid, None)


job_manager = JobManager()
