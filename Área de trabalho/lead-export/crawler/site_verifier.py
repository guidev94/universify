import ssl
import socket
import threading
import requests
import urllib3
from concurrent.futures import ThreadPoolExecutor, as_completed
from crawler.lead_scorer import calculate_score
from config import Config

# Desabilita avisos de SSL inseguro
urllib3.disable_warnings(urllib3.exceptions.InsecureRequestWarning)


class SiteVerifier:
    """Verificador de sites com alta paralelização e timeouts agressivos.

    Otimizações:
    - Timeout reduzido de 12s para 5s (sites BR que demoram mais estão offline)
    - SSL check com timeout de 3s (não 10s)
    - Batch timeout global de 90s (não 120s)
    - Sem retry com sleep (se falhou, marca como falha)
    - DB update com retry apenas para lock
    """

    # Session reutilizável para evitar overhead de TLS handshake repetido
    _session = None
    _session_lock = threading.Lock()

    def __init__(self, max_workers=None):
        self.max_workers = max_workers or Config.PIPELINE_VERIFY_WORKERS

    @classmethod
    def _get_session(cls):
        if cls._session is None:
            with cls._session_lock:
                if cls._session is None:
                    cls._session = requests.Session()
                    cls._session.headers.update({
                        "User-Agent": "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36",
                        "Accept": "text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8",
                        "Accept-Language": "pt-BR,pt;q=0.9,en;q=0.8",
                    })
                    # Adapter com pool de conexões para reutilizar TCP sockets
                    adapter = requests.adapters.HTTPAdapter(
                        pool_connections=50,
                        pool_maxsize=50,
                        max_retries=0,  # Sem retry automático — velocidade > confiabilidade
                    )
                    cls._session.mount("http://", adapter)
                    cls._session.mount("https://", adapter)
        return cls._session

    def verify_batch(self, leads):
        """Verifica um batch de leads e retorna os resultados para salvamento externo."""
        if not leads:
            return []
            
        results = []
        with ThreadPoolExecutor(max_workers=self.max_workers) as executor:
            futures = {
                executor.submit(self._verify_single, lead, update_db=False): lead
                for lead in leads if lead.get("website")
            }

            for future in as_completed(futures, timeout=120):
                try:
                    res = future.result()
                    if res:
                        results.append(res)
                except Exception:
                    pass
        return results

    def verify_leads(self, leads, on_complete=None, on_lead_complete=None):
        """Verificação blocking de uma lista de leads."""
        if not leads:
            return

        with ThreadPoolExecutor(max_workers=self.max_workers) as executor:
            futures = {
                executor.submit(self._verify_single, lead, on_lead_complete): lead
                for lead in leads if lead.get("website")
            }

            for future in as_completed(futures, timeout=90):
                try:
                    future.result()
                except Exception:
                    pass

        if on_complete:
            on_complete()

    def verify_leads_async(self, leads, on_complete=None, on_lead_complete=None):
        """Verificação non-blocking."""
        thread = threading.Thread(
            target=self.verify_leads,
            args=(leads, on_complete, on_lead_complete),
            daemon=True,
        )
        thread.start()

    def _verify_single(self, lead, on_lead_complete=None, update_db=True):
        from database import get_connection
        import time

        lid = lead.get("id")
        url = lead.get("website", "")
        
        # ... (continua o resto igual até o update_db)

        if not url.startswith(("http://", "https://")):
            url = "https://" + url

        site_ok = False
        ssl_valid = 0
        ssl_expired = 0
        copyright_year = 0
        is_responsive = 1
        response = None
        # Site offline recebe quality "unknown" (NÃO "poor"): fora do ar e "site feio"
        # são oportunidades de venda distintas e não devem ser confundidos.
        tech_data = {
            "quality": "unknown", "score": 0, "cms": "Offline",
            "has_pixel": 0, "has_analytics": 0, "has_ads": 0, "ads_platforms": "",
            "copyright_year": 0, "is_responsive": 1,
        }

        # Timeout 8s + 1 retry: hospedagem BR compartilhada é lenta; a tentativa única
        # de 5s marcava sites bons como "offline"/"poor" e poluía os leads de renovação.
        for _attempt in range(2):
            try:
                session = self._get_session()
                response = session.get(
                    url,
                    timeout=8,
                    allow_redirects=True,
                    verify=False,
                )
                site_ok = response.status_code < 400
                break
            except Exception:
                site_ok = False
                if _attempt == 0:
                    time.sleep(0.3)
                continue

        if site_ok and response is not None:
            tech_data = self._analyze_html(response.text, response)
            copyright_year = tech_data.get("copyright_year", 0)
            is_responsive = tech_data.get("is_responsive", 1)
            # SSL check apenas para HTTPS — distingue expirado de ausente
            if url.startswith("https"):
                ssl_valid, ssl_expired = self._check_ssl(url)

        site_status = "live" if site_ok else "offline"

        # Retorna os dados para processamento em batch se update_db for False
        result_data = None
        if not update_db or on_lead_complete:
            # Prepara os dados que seriam salvos
            lead_data_to_save = {
                "id": lid,
                "site_verified": 1 if site_ok else 0,
                "site_status": site_status,
                "ssl_valid": ssl_valid,
                "ssl_expired": ssl_expired,
                "copyright_year": copyright_year,
                "is_responsive": is_responsive,
                "site_quality": tech_data["quality"],
                "cms": tech_data["cms"],
                "has_pixel": tech_data["has_pixel"],
                "has_analytics": tech_data["has_analytics"],
                "has_ads": tech_data["has_ads"],
                "ads_platforms": tech_data["ads_platforms"],
                "tech_score": tech_data["score"],
            }
            result_data = lead_data_to_save

        if update_db:
            # DB Update com retry para lock (max 3 tentativas, sem sleep longo)
            for attempt in range(3):
                conn = get_connection()
                try:
                    row = conn.execute("SELECT * FROM leads WHERE id = ?", (lid,)).fetchone()
                    if row:
                        lead_data = dict(row)
                        lead_data.update({
                            "site_verified": 1 if site_ok else 0,
                            "site_status": site_status,
                            "ssl_valid": ssl_valid,
                            "ssl_expired": ssl_expired,
                            "copyright_year": copyright_year,
                            "is_responsive": is_responsive,
                            "site_quality": tech_data["quality"],
                            "cms": tech_data["cms"],
                            "has_pixel": tech_data["has_pixel"],
                            "has_ads": tech_data["has_ads"],
                        })
                        new_score, new_tier, pitch_hook = calculate_score(lead_data)

                        conn.execute(
                            """UPDATE leads SET
                               site_verified = ?, site_status = ?, ssl_valid = ?, ssl_expired = ?,
                               copyright_year = ?, is_responsive = ?, site_quality = ?,
                               cms = ?, has_pixel = ?, has_analytics = ?, has_ads = ?,
                               ads_platforms = ?, tech_score = ?, score = ?, tier = ?,
                               pitch_hook = ?, updated_at = datetime('now', 'localtime')
                               WHERE id = ?""",
                            (1 if site_ok else 0, site_status, ssl_valid, ssl_expired,
                             copyright_year, is_responsive, tech_data["quality"],
                             tech_data["cms"], tech_data["has_pixel"], tech_data["has_analytics"],
                             tech_data["has_ads"], tech_data["ads_platforms"], tech_data["score"],
                             new_score, new_tier, pitch_hook, lid)
                        )
                    conn.commit()
                    conn.close()
                    break
                except Exception as e:
                    conn.close()
                    if "locked" in str(e).lower() and attempt < 2:
                        time.sleep(0.2 * (attempt + 1))
                        continue
                    break

        if on_lead_complete:
            on_lead_complete(lid, 1 if site_ok else 0)
        
        return result_data

    def _analyze_html(self, html_content, response):
        """Análise técnica rápida do HTML."""
        html = html_content[:25000].lower()
        tech_score = 100
        cms = "Custom/Other"
        has_pixel = 0
        has_analytics = 0

        # CMS Detection
        if 'wp-content' in html or '/wp-includes/' in html:
            cms = "WordPress"
            if 'elementor' in html:
                cms = "WordPress (Elementor)"
            elif 'woocommerce' in html:
                cms = "WordPress (WooCommerce)"
        elif 'wix.com' in html or 'wix-style' in html:
            cms = "Wix"
        elif 'shopify.com' in html or 'cdn.shopify.com' in html:
            cms = "Shopify"
        elif 'vtex' in html:
            cms = "VTEX"
        elif 'rdstation' in html:
            cms = "RD Station"
        elif 'nuvemshop' in html:
            cms = "Nuvemshop"

        # Pixel & Analytics
        if 'fbevents.js' in html or 'connect.facebook.net' in html:
            has_pixel = 1
        if 'googletagmanager.com' in html or 'google-analytics.com' in html:
            has_analytics = 1

        # Ads Platforms
        ads_platforms = []
        if 'googleadservices.com' in html or 'googletagmanager.com/gtag/js?id=aw-' in html:
            ads_platforms.append("Google Ads")
        if has_pixel:
            ads_platforms.append("Meta Ads")
        if 'ads-twitter' in html:
            ads_platforms.append("X Ads")
        if 'linkedin.com/profile/insight' in html:
            ads_platforms.append("LinkedIn Ads")
        if 'analytics.tiktok.com' in html:
            ads_platforms.append("TikTok Ads")

        has_ads = 1 if ads_platforms else 0

        # Quality scoring
        has_viewport = 'viewport' in html
        if not has_viewport:
            tech_score -= 30
        if '<table' in html and 'cellpadding=' in html:
            tech_score -= 30
        if 'frameset' in html:
            tech_score -= 40
        if not response.url.startswith("https"):
            tech_score -= 10
        if response.elapsed.total_seconds() > 4:
            tech_score -= 10
        if len(html) < 800:
            tech_score -= 20

        # Sinais fortes de site abandonado/legado (ótimos ganchos de renovação)
        if 'jquery-1.' in html or 'jquery/1.' in html or 'jquery.min.js?ver=1' in html:
            tech_score -= 15   # jQuery 1.x = site muito antigo
        if 'shockwave-flash' in html or '.swf' in html or 'application/x-shockwave' in html:
            tech_score -= 25   # Flash = tecnologia morta

        # Bonus for modern features
        if 'hubspot' in html or 'pipedrive' in html or 'rdstation' in html:
            tech_score += 15
        if 'trabalhe conosco' in html or 'vagas' in html:
            tech_score += 10

        # Responsividade: viewport meta é o sinal mais confiável
        is_responsive = 1 if has_viewport else 0

        # Ano de copyright no rodapé — "© 2015" é um argumento de venda imediato
        copyright_year = 0
        try:
            import re as _re
            years = _re.findall(r'(?:©|&copy;|copyright)[^\d]{0,12}(20\d{2})', html)
            valid_years = [int(y) for y in years if 2000 <= int(y) <= 2100]
            if valid_years:
                copyright_year = max(valid_years)
        except Exception:
            copyright_year = 0

        quality = "good" if tech_score >= 75 else ("regular" if tech_score >= 55 else "poor")

        return {
            "quality": quality,
            "score": tech_score,
            "cms": cms,
            "has_pixel": has_pixel,
            "has_analytics": has_analytics,
            "has_ads": has_ads,
            "ads_platforms": ", ".join(ads_platforms),
            "copyright_year": copyright_year,
            "is_responsive": is_responsive,
        }

    def _check_ssl(self, url):
        """Verifica SSL e distingue certificado expirado de ausente/inválido.

        Retorna (ssl_valid:int, ssl_expired:int).
        - (1, 0): certificado válido
        - (0, 1): certificado EXPIRADO — gancho de segurança urgente
        - (0, 0): sem HTTPS válido / cadeia inválida
        """
        from urllib.parse import urlparse
        hostname = urlparse(url).hostname
        if not hostname:
            return 0, 0

        context = ssl.create_default_context()
        try:
            with socket.create_connection((hostname, 443), timeout=3) as sock:
                with context.wrap_socket(sock, server_hostname=hostname) as ssock:
                    return (1 if ssock.getpeercert() else 0), 0
        except ssl.SSLCertVerificationError as e:
            # "certificate has expired" é o caso mais valioso para vendas
            if "expired" in str(e).lower():
                return 0, 1
            return 0, 0
        except Exception:
            return 0, 0
