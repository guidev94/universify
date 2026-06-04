import re
import phonenumbers
from email_validator import validate_email, EmailNotValidError


def validate_lead(lead):
    if not lead or not isinstance(lead, dict):
        return None

    name = clean_name(lead.get("name", ""))
    email = validate_email_address(lead.get("email", ""))
    phone = validate_phone(lead.get("phone", ""))
    whatsapp = validate_phone(lead.get("whatsapp", "")) or phone
    website = lead.get("website", "").strip()
    source = lead.get("source", "")
    category = lead.get("category", "")

    if not name or (not email and not phone):
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
