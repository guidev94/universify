import os
import logging
from flask import Flask, session, request, g
from config import Config
from database import init_db

sentry_dsn = os.getenv("SENTRY_DSN", "")
if sentry_dsn:
    import sentry_sdk
    from sentry_sdk.integrations.flask import FlaskIntegration
    sentry_sdk.init(dsn=sentry_dsn, integrations=[FlaskIntegration()], traces_sample_rate=0.3)


def create_app():
    app = Flask(__name__)
    app.secret_key = Config.SECRET_KEY

    init_db()

    from blueprints.dashboard import bp as dashboard_bp
    from blueprints.leads import bp as leads_bp
    from blueprints.crawl import bp as crawl_bp
    from blueprints.export import bp as export_bp

    app.register_blueprint(dashboard_bp)
    app.register_blueprint(leads_bp)
    app.register_blueprint(crawl_bp)
    app.register_blueprint(export_bp)

    @app.context_processor
    def inject_csrf():
        return {"csrf_token": ""}

    from scheduler import scheduler
    scheduler.start()

    return app


app = create_app()

if __name__ == "__main__":
    app.run(debug=True, port=5000)
