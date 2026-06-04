import sys
import os
import threading
import time
import pytest

sys.path.insert(0, os.path.dirname(os.path.dirname(__file__)))

from app import app as flask_app


@pytest.fixture(scope="session")
def app_server():
    flask_app.config["TESTING"] = True
    server_thread = threading.Thread(
        target=lambda: flask_app.run(port=5099, use_reloader=False),
        daemon=True,
    )
    server_thread.start()
    time.sleep(2)
    yield "http://localhost:5099"
