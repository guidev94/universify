import os
import atexit
import logging
from flask import Flask
from config import Config
from database import init_db

sentry_dsn = os.getenv("SENTRY_DSN", "")
if sentry_dsn:
    import sentry_sdk
    from sentry_sdk.integrations.flask import FlaskIntegration
    sentry_sdk.init(dsn=sentry_dsn, integrations=[FlaskIntegration()], traces_sample_rate=0.3)


def create_app():
    print("[app] Starting create_app...")
    app = Flask(__name__)
    app.secret_key = Config.SECRET_KEY

    print("[app] Initializing DB...")
    init_db()

    print("[app] Registering blueprints...")
    from blueprints.dashboard import bp as dashboard_bp
    from blueprints.leads import bp as leads_bp
    from blueprints.crawl import bp as crawl_bp
    from blueprints.export import bp as export_bp
    from blueprints.events import bp as events_bp, event_bus

    app.register_blueprint(dashboard_bp)
    app.register_blueprint(leads_bp)
    app.register_blueprint(crawl_bp)
    app.register_blueprint(export_bp)
    app.register_blueprint(events_bp)

    @app.context_processor
    def inject_csrf():
        return {"csrf_token": ""}

    print("[app] Starting LeadPipeline...")
    from pipeline import LeadPipeline
    pipeline = LeadPipeline(event_bus=event_bus)
    pipeline.start()
    app.config["PIPELINE"] = pipeline

    print("[app] Starting Scheduler...")
    from scheduler import scheduler
    scheduler.pipeline = pipeline
    scheduler.event_bus = event_bus
    scheduler.start()

    print("[app] App ready!")
    def graceful_shutdown():
        logging.disable(logging.CRITICAL)
        scheduler.stop()
        pipeline.stop()

    atexit.register(graceful_shutdown)

    return app


app = create_app()

if __name__ == "__main__":
    debug_mode = os.getenv("FLASK_DEBUG", "false").lower() == "true"
    app.run(debug=debug_mode, port=5000, threaded=True)
