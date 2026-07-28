import ssl
import socket
import threading
import requests
import urllib3
from concurrent.futures import ThreadPoolExecutor
from crawler.lead_scorer import calculate_score

# Desabilita avisos de SSL inseguro para limpar os logs do servidor
urllib3.disable_warnings(urllib3.exceptions.InsecureRequestWarning)


class SiteVerifier:
    def __init__(self, max_workers=5):
        self.max_workers = max_workers

    def verify_leads(self, leads, on_complete=None, on_lead_complete=None):
        """Blocking verification of a list of leads."""
        self._run_verification(leads, on_complete, on_lead_complete)

    def verify_leads_async(self, leads, on_complete=None, on_lead_complete=None):
        """Non-blocking verification of a list of leads."""
        thread = threading.Thread(
            target=self._run_verification,
            args=(leads, on_complete, on_lead_complete),
            daemon=True,
        )
        thread.start()

    def _run_verification(self, leads, on_complete, on_lead_complete=None):
        from concurrent.futures import as_completed
        with ThreadPoolExecutor(max_workers=self.max_workers) as executor:
            futures = {executor.submit(self._verify_single, lead, on_lead_complete): lead for lead in leads if lead.get("website")}
            
            for future in as_completed(futures, timeout=120): # Global timeout for the whole batch
                try:
                    future.result()
                except Exception as e:
                    lead = futures[future]
                    print(f"Error verifying lead {lead.get('id')}: {e}")

        if on_complete:
            on_complete()

    def _verify_single(self, lead, on_lead_complete=None):
        from models import Lead
        from database import get_connection
        import time

        lid = lead.get("id")
        url = lead.get("website")
        print(f"[site-verifier] Starting verify for lead {lid}: {url}")
        
        if not url.startswith(("http://", "https://")):
            url = "https://" + url

        site_ok = False
        tech_data = {"quality": "poor", "score": 0, "cms": "Offline", "has_pixel": 0, "has_analytics": 0, "has_ads": 0, "ads_platforms": ""}
        ssl_valid = 0

        try:
            # Single GET request with shorter timeout
            response = requests.get(
                url, 
                timeout=7, # Reduced from 12 to 7
                allow_redirects=True, 
                headers={"User-Agent": "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/121.0.0.0 Safari/537.36"},
                verify=False # Skip SSL verify for speed, we check it separately if needed
            )
            site_ok = response.status_code < 400
            
            if site_ok:
                tech_data = self._analyze_html(response.text, response)
                # Quick SSL check only if https
                if url.startswith("https"):
                    ssl_valid = 1 if self._check_ssl(url) else 0
        except Exception:
            site_ok = False

        # Unified DB Update with Retry
        for attempt in range(5):
            conn = get_connection()
            try:
                # Get current lead data to recalculate overall score
                row = conn.execute("SELECT * FROM leads WHERE id = ?", (lead["id"],)).fetchone()
                if row:
                    lead_data = dict(row)
                    # Update with new tech data for scoring
                    lead_data.update({
                        "site_verified": 1 if site_ok else 0,
                        "ssl_valid": ssl_valid,
                        "site_quality": tech_data["quality"],
                        "has_pixel": tech_data["has_pixel"],
                        "has_ads": tech_data["has_ads"]
                    })
                    new_score, new_tier, pitch_hook = calculate_score(lead_data)
                    
                    # Update all fields in one go
                    conn.execute(
                        """UPDATE leads SET 
                           site_verified = ?, 
                           ssl_valid = ?, 
                           site_quality = ?, 
                           cms = ?, 
                           has_pixel = ?, 
                           has_analytics = ?, 
                           has_ads = ?,
                           ads_platforms = ?,
                           tech_score = ?,
                           score = ?,
                           tier = ?,
                           pitch_hook = ?,
                           updated_at = datetime('now', 'localtime')
                           WHERE id = ?""",
                        (1 if site_ok else 0, ssl_valid, tech_data["quality"], tech_data["cms"], 
                         tech_data["has_pixel"], tech_data["has_analytics"], tech_data["has_ads"], 
                         tech_data["ads_platforms"], tech_data["score"], new_score, new_tier, pitch_hook, lead["id"])
                    )
                
                conn.commit()
                conn.close()
                break
            except Exception as e:
                if conn: conn.close()
                if "locked" in str(e).lower():
                    time.sleep(0.5 * (attempt + 1))
                    continue
                break

        if on_lead_complete:
            on_lead_complete(lead["id"], 1 if site_ok else 0)

    def _analyze_html(self, html_content, response):
        html = html_content[:30000].lower()
        issues = 0
        tech_score = 100
        cms = "Custom/Other"
        has_pixel = 0
        has_analytics = 0
        
        # CMS Detection
        if 'wp-content' in html or '/wp-includes/' in html:
            cms = "WordPress"
            if 'elementor' in html: cms = "WordPress (Elementor)"
            if 'woocommerce' in html: cms = "WordPress (WooCommerce)"
        elif 'wix.com' in html or 'wix-style' in html:
            cms = "Wix"
        elif 'shopify.com' in html or 'cdn.shopify.com' in html:
            cms = "Shopify"
        elif 'vtex' in html:
            cms = "VTEX"
        elif 'rdstation' in html or 'rd-js-station' in html:
            cms = "RD Station Landing Page"
        elif 'nuvemshop' in html:
            cms = "Nuvemshop"

        # Pixel & Analytics Detection
        if 'fbevents.js' in html or 'connect.facebook.net' in html:
            has_pixel = 1
        if 'googletagmanager.com' in html or 'google-analytics.com' in html:
            has_analytics = 1
        
        # Specific Ads Platform Detection
        ads_platforms = []
        if 'googleadservices.com' in html or 'aw-' in html or 'googletagmanager.com/gtag/js?id=aw-' in html:
            ads_platforms.append("Google Ads")
        if has_pixel:
            ads_platforms.append("Meta Ads")
        if 'ads-twitter' in html:
            ads_platforms.append("X Ads")
        if 'linkedin.com/profile/insight' in html:
            ads_platforms.append("LinkedIn Ads")
        if 'analytics.tiktok.com' in html:
            ads_platforms.append("TikTok Ads")
        
        # CRM & Sales Tools Detection
        crms = []
        if 'pipedrive.com' in html or 'pipedrive-assets' in html:
            crms.append("Pipedrive")
        if 'hubspot.com' in html or 'hs-scripts' in html:
            crms.append("HubSpot")
        if 'salesforce.com' in html:
            crms.append("Salesforce")
        if 'rdstation' in html or 'rd-js-station' in html:
            crms.append("RD Station CRM")
            
        if crms:
            tech_score += 20 # Business that invests in sales
            
        # B2B & High Value indicators
        b2b_keywords = ["atacadista", "distribuidor", "indústria", "soluções b2b", "corporativo", "revenda", "parceiros"]
        if any(kw in html for kw in b2b_keywords):
            tech_score += 15
            
        if 'trabalhe conosco' in html or 'vagas' in html:
            tech_score += 10 # Company is growing
            
        has_ads = 1 if ads_platforms else 0
        ads_platforms_str = ", ".join(ads_platforms)

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
        
        if 'viewport' not in html:
            tech_score -= 30
        
        if not response.url.startswith("https"):
            tech_score -= 10
        
        if response.elapsed.total_seconds() > 4:
            tech_score -= 10
        
        if len(html) < 800:
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
            "has_analytics": has_analytics,
            "has_ads": has_ads,
            "ads_platforms": ads_platforms_str
        }

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
