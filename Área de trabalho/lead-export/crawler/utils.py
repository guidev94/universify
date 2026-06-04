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
        r"\+55\s*\(?\d{2}\)?\s*\d{4,5}[\-\s]?\d{4}",
        r"\(?\d{2}\)?\s*\d{4,5}[\-\s]?\d{4}",
        r"\d{2}\s\d{5}\s\d{4}",
    ]

    phones = set()
    for pattern in patterns:
        for match in re.findall(pattern, text):
            digits = re.sub(r"\D", "", match)
            if 10 <= len(digits) <= 13:
                phones.add(match.strip())

    return list(phones)[:5]


def extract_whatsapp_from_html(html):
    import re
    patterns = [
        r"wa\.me/(\d+)",
        r"api\.whatsapp\.com/send\?phone=(\d+)",
        r"whatsapp.*?(\d{12,13})",
    ]

    for pattern in patterns:
        match = re.search(pattern, html, re.IGNORECASE)
        if match:
            number = match.group(1)
            if len(number) >= 12:
                return number

    return ""


def extract_business_name(soup, domain):
    og_title = soup.find("meta", property="og:site_name")
    if og_title and og_title.get("content"):
        return og_title["content"].strip()

    title = soup.find("title")
    if title and title.string:
        name = title.string.strip()
        for sep in [" | ", " - ", " — ", " – ", " :: "]:
            if sep in name:
                name = name.split(sep)[0].strip()
                break
        if len(name) <= 60:
            return name

    domain_name = domain.replace("www.", "").split(".")[0]
    return domain_name.title()
