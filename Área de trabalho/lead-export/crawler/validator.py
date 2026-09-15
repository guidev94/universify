import re
import phonenumbers
from email_validator import validate_email, EmailNotValidError
from urllib.parse import urlparse

BLOCKED_DOMAINS = {
    "google.com", "google.com.br", "about.google",
    "facebook.com", "instagram.com", "youtube.com", "twitter.com", "x.com",
    "linkedin.com", "tiktok.com", "pinterest.com",
    "wikipedia.org", "wikimedia.org",
    "correios.com.br", "sptrans.com.br",
    "gov.br", "jus.br", "mil.br", "leg.br", "mp.br",
    "folha.uol.com.br", "globo.com", "uol.com.br", "terra.com.br",
    "reclameaqui.com.br", "jusbrasil.com.br",
    "mercadolivre.com.br", "olx.com.br", "amazon.com.br",
    "amazon.com", "ebay.com", "alibaba.com",
    "yelp.com", "tripadvisor.com", "booking.com",
}

BR_TLDS = (".com.br", ".org.br", ".net.br", ".ind.br", ".srv.br")
BR_PHONE_PREFIX = ("+55", "55")

BLOCKED_PATTERNS = (".gov.", ".gov/", "prefeitura", "tribunal", "receita.fazenda", "policia", "exército", "marinha", "aeronáutica", "blogspot.com", "wordpress.com", "sites.google.com", "wixsite.com")

BLOCKED_NAMES = {
    "prefeitura", "governo", "tribunal", "secretaria", "ministério", "senado", "câmara", 
    "fórum", "delegacia", "polícia", "exército", "marinha", "aeronáutica", "paróquia", "igreja",
    "privacy policy", "termos de uso", "contato", "home", "página inicial", "quem somos",
    "política de privacidade", "cookies", "trabalhe conosco", "vagas", "login", "entrar",
    "esqueceu a senha", "create account", "register", "blog", "notícias", "news"
}


def _is_blocked(website, name):
    if website:
        try:
            domain = urlparse(website).netloc.lower().replace("www.", "")
        except Exception:
            domain = website.lower()
        if domain in BLOCKED_DOMAINS:
            return True
        for pattern in BLOCKED_PATTERNS:
            if pattern in website.lower():
                return True
    if name:
        name_lower = name.lower()
        for blocked in BLOCKED_NAMES:
            if blocked in name_lower:
                return True
    return False


def validate_lead(lead):
    if not lead or not isinstance(lead, dict):
        return None

    name = clean_name(lead.get("name", ""))
    
    # Reject names with too many non-latin characters (Chinese, Arabic, etc.)
    if re.search(r"[\u4e00-\u9fff]", name):
        return None
        
    email = validate_email_address(lead.get("email", ""))
    phone = validate_phone(lead.get("phone", ""))
    whatsapp = validate_phone(lead.get("whatsapp", "")) or phone
    website = lead.get("website", "").strip()
    source = lead.get("source", "")
    category = lead.get("category", "")

    if not name or (not email and not phone):
        return None

    if _is_blocked(website, name):
        return None

    if email and _is_blocked_email(email):
        email = ""
        if not phone:
            return None

    if not _is_brazilian(website, phone, email):
        return None

    return {
        "name": name,
        "email": email,
        "phone": phone,
        "whatsapp": whatsapp,
        "website": website,
        "source": source,
        "category": category,
    }


def _is_blocked_email(email):
    domain = email.split("@")[-1].lower() if "@" in email else ""
    if any(domain.endswith(b) for b in (".gov.br", ".jus.br", ".mil.br", ".leg.br")):
        return True
    return False


def _is_brazilian(website, phone, email):
    # 1. Check for Brazilian TLDs - Highest confidence
    if website:
        site_lower = website.lower()
        if any(site_lower.endswith(tld) or f"{tld}/" in site_lower for tld in BR_TLDS):
            return True
        
        # Explicitly reject clearly foreign TLDs
        foreign_tlds = [".us", ".uk", ".fr", ".de", ".it", ".es", ".ru", ".cn", ".jp", ".ca", ".au", ".in", ".mx", ".ar", ".cl", ".co"]
        if any(site_lower.endswith(tld) or f"{tld}/" in site_lower for tld in foreign_tlds):
            return False

    # 2. Check phone number (Brazilian numbers are very distinct)
    has_br_phone = False
    if phone:
        digits = re.sub(r"\D", "", phone)
        # Brazilian numbers: 55 + 2 digits DDD + 8 or 9 digits
        if digits.startswith("55"):
            if len(digits) in (12, 13):
                has_br_phone = True
        elif len(digits) in (10, 11):
            # DDDs in Brazil are between 11 and 99
            if 11 <= int(digits[:2]) <= 99:
                has_br_phone = True

    # 3. Check email domain
    if email:
        domain = email.split("@")[-1].lower()
        if any(domain.endswith(tld) for tld in BR_TLDS):
            return True
        
        foreign_tlds = [".us", ".uk", ".fr", ".de", ".it", ".es", ".ru"]
        if any(domain.endswith(tld) for tld in foreign_tlds):
            return False

    # 4. Strictness: if we have a website that is NOT .br AND no Brazilian phone, reject it.
    # This avoids generic global junk leads.
    if website and not any(website.lower().endswith(tld) for tld in BR_TLDS):
        if not has_br_phone:
            return False

    # Default: must have either a BR phone or a BR domain to be considered Brazilian
    return has_br_phone or (website and any(website.lower().endswith(tld) for tld in BR_TLDS))


def clean_name(name):
    if not name:
        return ""
    name = re.sub(r"[^\w\s\-àáâãéêíóôõúüç.]", "", name, flags=re.IGNORECASE | re.UNICODE)
    name = " ".join(name.split())
    if len(name) < 2 or len(name) > 120:
        return ""
    return name.title()


def validate_email_address(email):
    if not email:
        return ""
    email = email.strip().lower()

    generic_prefixes = {"noreply", "no-reply", "info", "admin", "webmaster", "postmaster", "mailer-daemon"}
    local_part = email.split("@")[0] if "@" in email else ""
    if local_part in generic_prefixes:
        return ""

    try:
        result = validate_email(email, check_deliverability=False)
        return result.normalized
    except EmailNotValidError:
        return ""


def validate_phone(phone):
    if not phone:
        return ""

    digits = re.sub(r"\D", "", phone)

    if not digits:
        return ""

    if not digits.startswith("55") and 10 <= len(digits) <= 11:
        digits = "55" + digits

    try:
        parsed = phonenumbers.parse(f"+{digits}")
        if phonenumbers.is_valid_number(parsed):
            return phonenumbers.format_number(parsed, phonenumbers.PhoneNumberFormat.E164)
    except phonenumbers.NumberParseException:
        pass

    return ""
