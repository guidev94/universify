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

BLOCKED_PATTERNS = (".gov.", ".gov/", "prefeitura", "tribunal", "receita.fazenda")

BLOCKED_NAMES = {"prefeitura", "governo", "tribunal", "secretaria", "ministério", "senado", "câmara"}


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
    # If it's a known Brazilian TLD, it's Brazilian
    if website:
        site_lower = website.lower()
        if any(site_lower.endswith(tld) or f"{tld}/" in site_lower for tld in BR_TLDS):
            return True
        
        # Check for foreign TLDs - explicitly reject them
        foreign_tlds = [".us", ".uk", ".fr", ".de", ".it", ".es", ".ru", ".cn", ".jp", ".ca", ".au"]
        if any(site_lower.endswith(tld) or f"{tld}/" in site_lower for tld in foreign_tlds):
            return False

    # Check phone number
    if phone:
        digits = re.sub(r"\D", "", phone)
        # Brazilian numbers: 55 + 2 digits DDD + 8 or 9 digits
        if digits.startswith("55"):
            if len(digits) in (12, 13):
                return True
            else:
                # If it's another country code, it's not Brazilian
                return False
        elif len(digits) in (10, 11):
            return True

    # Check email domain
    if email:
        domain = email.split("@")[-1].lower()
        if any(domain.endswith(tld) for tld in BR_TLDS):
            return True
        
        # Common global domains like gmail.com are neutral, 
        # so we don't return True just because of them unless we have other clues.
        # But if it's a foreign country email, reject.
        foreign_tlds = [".us", ".uk", ".fr", ".de", ".it", ".es"]
        if any(domain.endswith(tld) for tld in foreign_tlds):
            return False

    # If we got here and have a website that isn't clearly Brazilian or foreign (like a .com),
    # we need at least a Brazilian phone or email to be sure.
    # If we have NO website but have a phone/email, we already checked those above.
    
    # If we have a .com website and no other info, it might be Brazilian, 
    # but the user wants "sem site gringo", so we should be more conservative.
    if website and not (phone or email):
        # .com without phone/email might be anything. Let's allow it for now but it will get lower score.
        # Actually, let's check if it's a very common global site that isn't in our blocked list
        return True

    # Default to True if we have some contact info that wasn't explicitly foreign
    if phone or email:
        return True

    return False


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
