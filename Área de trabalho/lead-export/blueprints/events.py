import queue
import time
import json
import threading
import logging
from flask import Blueprint, Response, stream_with_context

logger = logging.getLogger("leadhunter.events")

bp = Blueprint("events", __name__)


class EventBus:
    """In-process pub/sub for Server-Sent Events."""

    def __init__(self):
        self._subscribers = []
        self._lock = threading.Lock()

    def subscribe(self):
        q = queue.Queue(maxsize=100)
        with self._lock:
            self._subscribers.append(q)
        return q

    def unsubscribe(self, q):
        with self._lock:
            self._subscribers = [s for s in self._subscribers if s is not q]

    def publish(self, event_type, data):
        message = json.dumps({"type": event_type, "data": data, "ts": time.time()})
        with self._lock:
            dead = []
            for q in self._subscribers:
                try:
                    q.put_nowait(message)
                except queue.Full:
                    dead.append(q)
            for q in dead:
                self._subscribers.remove(q)

    @property
    def subscriber_count(self):
        with self._lock:
            return len(self._subscribers)


event_bus = EventBus()


@bp.route("/api/events/stream")
def event_stream():
    def generate():
        q = event_bus.subscribe()
        try:
            yield "data: {\"type\":\"connected\"}\n\n"
            while True:
                try:
                    message = q.get(timeout=25)
                    yield f"data: {message}\n\n"
                except queue.Empty:
                    yield ": keepalive\n\n"
        except GeneratorExit:
            event_bus.unsubscribe(q)

    return Response(
        stream_with_context(generate()),
        mimetype="text/event-stream",
        headers={
            "Cache-Control": "no-cache",
            "X-Accel-Buffering": "no",
            "Connection": "keep-alive",
        },
    )
