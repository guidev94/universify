import time
import json
import threading
import logging
from database import get_connection

logger = logging.getLogger("leadhunter.jobs")


class JobManager:
    """Thread-safe job state manager backed by SQLite with in-memory cache."""

    def __init__(self):
        self._lock = threading.Lock()
        self._cache = {}

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
        self._persist(job_uuid, state)

    def update(self, job_uuid, **kwargs):
        with self._lock:
            if job_uuid not in self._cache:
                self._cache[job_uuid] = self._load_from_db(job_uuid) or {}
            self._cache[job_uuid].update(kwargs)
            state = dict(self._cache[job_uuid])
        self._persist(job_uuid, state)

    def get(self, job_uuid):
        with self._lock:
            if job_uuid in self._cache:
                return dict(self._cache[job_uuid])
        state = self._load_from_db(job_uuid)
        if state:
            with self._lock:
                self._cache[job_uuid] = state
        return state

    def complete(self, job_uuid, found_count):
        self.update(job_uuid, status="completed", progress=found_count)
        threading.Timer(600, self._evict, args=(job_uuid,)).start()

    def fail(self, job_uuid):
        self.update(job_uuid, status="error")
        threading.Timer(600, self._evict, args=(job_uuid,)).start()

    def get_all_running(self):
        with self._lock:
            return {k: dict(v) for k, v in self._cache.items() if v.get("status") == "running"}

    def cleanup_stale(self, max_age=300):
        now = time.time()
        stale = []
        with self._lock:
            for uuid, state in list(self._cache.items()):
                started = state.get("started_at", 0)
                if state.get("status") == "running" and now - started > max_age:
                    stale.append(uuid)
        for uuid in stale:
            self.update(uuid, status="error")
            logger.warning(f"[jobs] marked stale job {uuid[:8]} as error")
        return stale

    def _persist(self, job_uuid, state):
        try:
            conn = get_connection()
            conn.execute(
                "UPDATE crawl_jobs SET job_state = ? WHERE job_uuid = ?",
                (json.dumps(state), job_uuid),
            )
            conn.commit()
            conn.close()
        except Exception as e:
            logger.warning(f"[jobs] persist failed for {job_uuid[:8]}: {e}")

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
