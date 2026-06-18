import ssl
import socket
import threading
import requests
from concurrent.futures import ThreadPoolExecutor
from crawler.lead_scorer import calculate_score


class SiteVerifier:
    def __init__(self, max_workers=5):
        self.max_workers = max_workers

    def verify_leads(self, leads, on_complete=None):
        thread = threading.Thread(
            target=self._run_verification,
            args=(leads, on_complete),
            daemon=True,
        )
        thread.start()

    def _run_verification(self, leads, on_complete):
        with ThreadPoolExecutor(max_workers=self.max_workers) as executor:
            futures = []
            for lead in leads:
                if lead.get("website"):
                    futures.append(executor.submit(self._verify_single, lead))

            for future in futures:
                future.result()

        if on_complete:
            on_complete()

    def _verify_single(self, lead):
        from models import Lead
        from database import get_connection

        url = lead["website"]
        site_ok = self._check_http(url)
        ssl_ok = self._check_ssl(url) if site_ok else False
        quality = self._check_quality(url) if site_ok else "poor"

        if not site_ok:
            quality = "poor"

        lead_data = Lead.get_by_id(lead["id"])
        if not lead_data:
            return

        lead_data["site_verified"] = 1 if site_ok else 0
        lead_data["ssl_valid"] = 1 if ssl_ok else 0

        score, tier = calculate_score(lead_data)

        Lead.update_verification(
            lead["id"],
            site_verified=1 if site_ok else 0,
            ssl_valid=1 if ssl_ok else 0,
            new_score=score,
            new_tier=tier,
        )

        conn = get_connection()
        conn.execute("UPDATE leads SET site_quality = ? WHERE id = ?", (quality, lead["id"]))
        conn.commit()
        conn.close()

    def _check_quality(self, url):
        try:
            if not url.startswith(("http://", "https://")):
                url = "https://" + url
            response = requests.get(url, timeout=8, allow_redirects=True)
            html = response.text[:10000].lower()

            issues = 0
            tech_score = 100
            
            is_legacy = False
            if '<table' in html and ('width=' in html or 'cellpadding=' in html):
                is_legacy = True
                tech_score -= 30
            if 'frameset' in html or 'frame src' in html:
                is_legacy = True
                tech_score -= 40
            if 'frontpage' in html or 'dreamweaver' in html:
                is_legacy = True
                tech_score -= 20
            if 'jquery/1.' in html or 'jquery-1.' in html:
                tech_score -= 15
            if 'flash' in html or '.swf' in html:
                is_legacy = True
                tech_score -= 50
            
            if 'viewport' not in html:
                tech_score -= 20
                issues += 1
            
            if not response.url.startswith("https"):
                issues += 1
                tech_score -= 10
            
            if response.elapsed.total_seconds() > 4:
                issues += 1
                tech_score -= 10
            
            if len(html) < 800:
                issues += 1
                tech_score -= 20

            if tech_score < 55 or is_legacy:
                return "poor"
            elif tech_score < 75:
                return "regular"
            return "good"
        except Exception:
            return "poor"

    def _check_http(self, url):
        try:
            if not url.startswith(("http://", "https://")):
                url = "https://" + url
            response = requests.head(url, timeout=10, allow_redirects=True)
            return response.status_code < 400
        except Exception:
            return False

    def _check_ssl(self, url):
        try:
            from urllib.parse import urlparse
            parsed = urlparse(url if url.startswith("http") else f"https://{url}")
            hostname = parsed.hostname
            if not hostname:
                return False

            context = ssl.create_default_context()
            with socket.create_connection((hostname, 443), timeout=10) as sock:
                with context.wrap_socket(sock, server_hostname=hostname) as ssock:
                    cert = ssock.getpeercert()
                    return cert is not None
        except Exception:
            return False
