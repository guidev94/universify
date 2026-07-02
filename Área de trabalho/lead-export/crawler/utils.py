import time
import logging
import functools
import requests

logger = logging.getLogger("leadhunter.crawler")


def retry(max_attempts=3, backoff_factor=2, initial_delay=1):
    def decorator(func):
        @functools.wraps(func)
        def wrapper(*args, **kwargs):
            delay = initial_delay
            last_exception = None

            for attempt in range(1, max_attempts + 1):
                try:
                    return func(*args, **kwargs)
                except (requests.exceptions.RequestException, requests.exceptions.Timeout) as e:
                    last_exception = e
                    status = getattr(getattr(e, "response", None), "status_code", None)

                    if status == 429:
                        retry_after = int(
                            getattr(e, "response", None)
                            and e.response.headers.get("Retry-After", "30")
                            or "30"
                        )
                        logger.warning(f"[retry] 429 em {func.__name__} — espera {retry_after}s (tentativa {attempt}/{max_attempts})")
                        time.sleep(retry_after)
                    elif attempt < max_attempts:
                        logger.info(f"[retry] {func.__name__} falhou ({e.__class__.__name__}) — tentativa {attempt}/{max_attempts}, espera {delay}s")
                        time.sleep(delay)
                        delay *= backoff_factor
                    else:
                        logger.error(f"[retry] {func.__name__} falhou após {max_attempts} tentativas: {e}")

            raise last_exception

        return wrapper
    return decorator


def safe_request(session, url, method="get", timeout=15, delay=0, **kwargs):
    try:
        response = getattr(session, method)(url, timeout=timeout, **kwargs)

        if response.status_code == 429:
            retry_after = int(response.headers.get("Retry-After", "30"))
            logger.warning(f"[rate-limit] 429 em {url[:80]} — espera {retry_after}s")
            time.sleep(retry_after)
            response = getattr(session, method)(url, timeout=timeout, **kwargs)

        if delay > 0:
            time.sleep(delay)

        return response

    except requests.exceptions.RequestException as e:
        logger.debug(f"[request] falha em {url[:80]}: {e.__class__.__name__}")
        return None


def get_metropolitan_areas(city="São Paulo"):
    # Cidades satélites e regiões metropolitanas estratégicas
    areas = {
        "São Paulo": [
            "Guarulhos", "Osasco", "Santo André", "São Bernardo do Campo", 
            "São Caetano do Sul", "Diadema", "Barueri", "Alphaville", 
            "Santana de Parnaíba", "Mogi das Cruzes", "Jundiaí", "Campinas"
        ],
        "Rio de Janeiro": [
            "Niterói", "Duque de Caxias", "Nova Iguaçu", "São Gonçalo", 
            "Belford Roxo", "Petrópolis", "Macaé"
        ],
        "Belo Horizonte": ["Contagem", "Betim", "Nova Lima", "Ribeirão das Neves"],
        "Curitiba": ["São José dos Pinhais", "Colombo", "Araucária", "Pinhais"],
        "Porto Alegre": ["Canoas", "Novo Hamburgo", "São Leopoldo", "Gravataí"]
    }
    return areas.get(city, [])

def expand_metropolitan_search(keyword, location):
    # Detect city from location
    city = "São Paulo"
    loc_lower = location.lower()
    if "rio" in loc_lower: city = "Rio de Janeiro"
    elif "belo" in loc_lower or "bh" in loc_lower: city = "Belo Horizonte"
    elif "curitiba" in loc_lower: city = "Curitiba"
    elif "porto" in loc_lower: city = "Porto Alegre"
        
    satellites = get_metropolitan_areas(city)
    expanded = []
    
    # Original
    expanded.append(f"{keyword} {location}")
    
    # Add satellites
    for sat in satellites[:10]:
        expanded.append(f"{keyword} {sat}")
        
    return expanded

def get_neighborhoods(city="São Paulo"):
    neighborhoods = {
        "São Paulo": [
            "Centro", "Moema", "Itaim Bibi", "Vila Olímpia", "Pinheiros", "Jardins", 
            "Morumbi", "Tatuapé", "Santana", "Lapa", "Brooklin", "Vila Mariana", 
            "Perdizes", "Butantã", "Ipiranga", "Santo Amaro", "Mooca", "Vila Madalena",
            "Bela Vista", "Consolação", "Liberdade", "Cambuci", "Aclimação", "Saúde"
        ],
        "Rio de Janeiro": [
            "Barra da Tijuca", "Copacabana", "Ipanema", "Leblon", "Botafogo", 
            "Centro", "Tijuca", "Recreio", "Flamengo", "Gávea", "Laranjeiras",
            "Humaitá", "Urca", "Catete", "Glória", "Santa Teresa", "Cosme Velho"
        ],
        "Belo Horizonte": [
            "Savassi", "Lourdes", "Pampulha", "Buritis", "Sion", "Belvedere",
            "Funcionários", "Santo Agostinho", "Anchieta", "Cruzeiro"
        ],
        "Curitiba": [
            "Batel", "Centro", "Água Verde", "Bigorrilho", "Cabral", "Juvevê",
            "Mercês", "Santa Felicidade", "Vila Izabel", "Rebouças"
        ],
        "Porto Alegre": [
            "Moinhos de Vento", "Petrópolis", "Centro Histórico", "Menino Deus",
            "Bela Vista", "Três Figueiras", "Mont'Serrat", "Rio Branco"
        ]
    }
    return neighborhoods.get(city, neighborhoods["São Paulo"])

def expand_local_keywords(keyword, location):
    # Detect city from location
    city = "São Paulo"
    if "rio" in location.lower():
        city = "Rio de Janeiro"
    elif "belo" in location.lower() or "bh" in location.lower():
        city = "Belo Horizonte"
    elif "curitiba" in location.lower():
        city = "Curitiba"
    elif "porto" in location.lower():
        city = "Porto Alegre"
        
    neighborhoods = get_neighborhoods(city)
    expanded = []
    
    # Base keyword
    expanded.append(f"{keyword} {location}")
    
    # Add variations for neighborhoods
    for nh in neighborhoods[:15]: # Limit to 15 to not explode
        expanded.append(f"{keyword} {nh} {location}")
        
    return expanded

def extract_emails_from_text(text, html=""):
    import re
    pattern = r"[a-zA-Z0-9._%+\-]+@[a-zA-Z0-9.\-]+\.[a-zA-Z]{2,}"
    found = set(re.findall(pattern, text))
    if html:
        found.update(re.findall(pattern, html))

    excluded_extensions = {".png", ".jpg", ".jpeg", ".gif", ".svg", ".webp", ".css", ".js"}
    excluded_domains = {"example.com", "email.com", "sentry.io", "wixpress.com", "w3.org", "sentry-next.wixpress.com"}
    excluded_domain_parts = ("sentry", "wixpress", "example.com", "noreply", "mailchimp", "sendgrid")
    excluded_prefixes = ("noreply", "no-reply", "mailer", "admin@", "info@example", "webmaster")

    valid = []
    for email in found:
        email_lower = email.lower()
        domain = email_lower.split("@")[1] if "@" in email_lower else ""
        ext = "." + domain.split(".")[-1] if domain else ""

        if ext in excluded_extensions:
            continue
        if domain in excluded_domains:
            continue
        if any(part in domain for part in excluded_domain_parts):
            continue
        if any(email_lower.startswith(p) for p in excluded_prefixes):
            continue

        valid.append(email_lower)

    return list(valid)[:5]


def extract_phones_from_text(text):
    import re
    patterns = [
        r"\+55\s*\(?\d{2}\)?\s*9\d{4}[\-\s]?\d{4}", # Celular com 9
        r"\+55\s*\(?\d{2}\)?\s*\d{4}[\-\s]?\d{4}",   # Fixo
        r"\(?\d{2}\)?\s*9\d{4}[\-\s]?\d{4}",         # Celular local
        r"\(?\d{2}\)?\s*\d{4}[\-\s]?\d{4}",           # Fixo local
        r"\d{2}\s9\d{4}\s\d{4}",
        r"\d{2}\s\d{4}\s\d{4}",
        r"0800\s\d{3}\s\d{4}",                        # 0800
    ]

    phones = set()
    for pattern in patterns:
        for match in re.findall(pattern, text):
            digits = re.sub(r"\D", "", match)
            if 10 <= len(digits) <= 13 or digits.startswith("0800"):
                phones.add(match.strip())

    return list(phones)[:5]


def extract_whatsapp_from_html(html):
    import re
    patterns = [
        r"wa\.me/(\d+)",
        r"api\.whatsapp\.com/send\?phone=(\d+)",
        r"web\.whatsapp\.com/send\?phone=(\d+)",
        r"whatsapp.*?phone=(\d+)",
        r"whatsapp.*?(\d{12,13})",
        r"https?://vcard\.link/card/(\w+)", # VCard links sometimes used
    ]

    for pattern in patterns:
        match = re.search(pattern, html, re.IGNORECASE)
        if match:
            number = match.group(1)
            if len(number) >= 12:
                return number

    return ""


def extract_business_name(soup, domain):
    """Extrai o nome da empresa de forma inteligente a partir do HTML."""
    # 1. Tenta og:site_name (geralmente o nome limpo da marca)
    og_site = soup.find("meta", property="og:site_name")
    if og_site and og_site.get("content"):
        name = og_site["content"].strip()
        if len(name) > 2 and len(name) < 60:
            return name

    # 2. Tenta og:title
    og_title = soup.find("meta", property="og:title")
    if og_title and og_title.get("content"):
        name = og_title["content"].strip()
        # Limpa separadores comuns se o nome for longo
        for sep in [" | ", " - ", " — ", " – "]:
            if sep in name:
                parts = name.split(sep)
                # Pega a parte que não parece ser descrição
                if len(parts[0]) > 2: name = parts[0].strip()
                break
        if len(name) > 2 and len(name) < 60:
            return name

    # 3. Tenta a tag <title>
    title_el = soup.find("title")
    if title_el and title_el.string:
        name = title_el.string.strip()
        
        # Lista de palavras genéricas para ignorar/pular
        generic_terms = ["home", "início", "inicio", "página inicial", "welcome", "index", "website", "oficial"]
        
        for sep in [" | ", " - ", " — ", " – ", " :: "]:
            if sep in name:
                parts = name.split(sep)
                # Tenta a primeira parte
                candidate = parts[0].strip()
                if candidate.lower() not in generic_terms and len(candidate) > 2:
                    name = candidate
                    break
                # Se a primeira for genérica, tenta a segunda (ex: "Home - Empresa X")
                if len(parts) > 1:
                    candidate = parts[1].strip()
                    if candidate.lower() not in generic_terms and len(candidate) > 2:
                        name = candidate
                        break
        
        if len(name) > 2 and len(name) < 70 and name.lower() not in generic_terms:
            return name

    # 4. Tenta o primeiro H1 (muitas vezes é o logo ou nome da empresa)
    h1 = soup.find("h1")
    if h1:
        h1_text = h1.get_text().strip()
        if h1_text and len(h1_text) > 2 and len(h1_text) < 50:
            return h1_text

    # 5. Fallback: Nome do domínio formatado
    domain_name = domain.replace("www.", "").split(".")[0]
    # Se o domínio for muito curto ou estranho, capitaliza
    if len(domain_name) > 2:
        return domain_name.title()
    
    return "Empresa sem Nome"


def extract_social_from_html(html):
    import re
    social = {"instagram": "", "facebook": "", "linkedin": "", "youtube": "", "twitter": ""}

    ig_match = re.search(r'instagram\.com/([a-zA-Z0-9_.]+)', html)
    if ig_match:
        handle = ig_match.group(1).split('?')[0].rstrip('/')
        if handle not in ("p", "explore", "reel", "stories", "accounts"):
            social["instagram"] = f"@{handle}"

    fb_match = re.search(r'facebook\.com/([a-zA-Z0-9.]+)', html)
    if fb_match:
        handle = fb_match.group(1).split('?')[0].rstrip('/')
        if handle not in ("sharer", "share", "dialog", "plugins", "tr"):
            social["facebook"] = handle
            
    li_match = re.search(r'linkedin\.com/company/([a-zA-Z0-9\-]+)', html)
    if li_match:
        social["linkedin"] = li_match.group(1)
        
    yt_match = re.search(r'youtube\.com/(c/|channel/|user/)?([a-zA-Z0-9\-@_]+)', html)
    if yt_match:
        social["youtube"] = yt_match.group(2)

    return social
