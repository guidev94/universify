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
        
        tech_data = self._check_quality(url) if site_ok else {"quality": "poor", "score": 0, "cms": "Offline", "has_pixel": 0, "has_analytics": 0}

        lead_data = Lead.get_by_id(lead["id"])
        if not lead_data:
            return

        lead_data["site_verified"] = 1 if site_ok else 0
        lead_data["ssl_valid"] = 1 if (site_ok and self._check_ssl(url)) else 0
        lead_data["site_quality"] = tech_data["quality"]
        lead_data["cms"] = tech_data["cms"]
        lead_data["has_pixel"] = tech_data["has_pixel"]
        lead_data["has_analytics"] = tech_data["has_analytics"]

        score, tier = calculate_score(lead_data)

        # Bonus for specific sales opportunities
        if tech_data["quality"] == "poor" and site_ok:
            score = min(100, score + 15)
        if not tech_data["has_pixel"] and site_ok:
            score = min(100, score + 5)

        Lead.update_verification(
            lead["id"],
            site_verified=lead_data["site_verified"],
            ssl_valid=lead_data["ssl_valid"],
            new_score=score,
            new_tier=tier,
        )

        conn = get_connection()
        conn.execute(
            """UPDATE leads SET 
               site_quality = ?, 
               cms = ?, 
               has_pixel = ?, 
               has_analytics = ?, 
               tech_score = ? 
               WHERE id = ?""", 
            (tech_data["quality"], tech_data["cms"], tech_data["has_pixel"], tech_data["has_analytics"], tech_data["score"], lead["id"])
        )
        conn.commit()
        conn.close()

    def _check_quality(self, url):
        try:
            if not url.startswith(("http://", "https://")):
                url = "https://" + url
            response = requests.get(url, timeout=8, allow_redirects=True)
            html = response.text[:20000].lower()

            issues = 0
            tech_score = 100
            cms = "Custom/Other"
            has_pixel = 0
            has_analytics = 0
            
            # CMS Detection
            if 'wp-content' in html or '/wp-includes/' in html:
                cms = "WordPress"
                if 'elementor' in html: cms = "WordPress (Elementor)"
            elif 'wix.com' in html or 'wix-style' in html:
                cms = "Wix"
            elif 'shopify.com' in html or 'cdn.shopify.com' in html:
                cms = "Shopify"
            elif 'vtex' in html:
                cms = "VTEX"

            # Pixel & Analytics Detection
            if 'fbevents.js' in html or 'connect.facebook.net' in html:
                has_pixel = 1
            if 'googletagmanager.com' in html or 'google-analytics.com' in html:
                has_analytics = 1
            
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
                quality = "poor"
            elif tech_score < 75:
                quality = "regular"
            else:
                quality = "good"
                
            return {
                "quality": quality,
                "score": tech_score,
                "cms": cms,
                "has_pixel": has_pixel,
                "has_analytics": has_analytics
            }
        except Exception:
            return {"quality": "poor", "score": 0, "cms": "Unknown", "has_pixel": 0, "has_analytics": 0}

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
