def is_brazilian_domain(domain):
    if not domain:
        return False
    domain = domain.lower()
    if domain.endswith(".com.br") or domain.endswith(".net.br") or domain.endswith(".org.br") or domain.endswith(".br"):
        return True
    
    # Common global domains used in Brazil are neutral, but we'll check phone/whatsapp later
    return False

def is_gringo_domain(domain):
    if not domain:
        return False
    domain = domain.lower()
    
    # List of clearly foreign TLDs that are unlikely for a Brazilian target
    foreign_tlds = [
        ".us", ".uk", ".fr", ".de", ".it", ".es", ".ru", ".cn", ".jp", ".in",
        ".ca", ".au", ".mx", ".ar", ".cl", ".co", ".pe", ".py", ".uy"
    ]
    if any(domain.endswith(tld) for tld in foreign_tlds):
        return True
    
    # Common foreign generic domains that aren't usually used by Brazilian small businesses
    gringo_keywords = ["global", "international", "usa", "europe", "shop", "store"]
    # (Optional: check if domain has these, but be careful not to false positive)
    
    return False

def calculate_score(lead):
    score = 0
    is_br_domain = False
    is_foreign = False
    pitch_hooks = []

    website = lead.get("website", "").lower()
    if website:
        from urllib.parse import urlparse
        domain = urlparse(website).netloc or website.split("/")[0]
        is_br_domain = is_brazilian_domain(domain)
        is_foreign = is_gringo_domain(domain)

    has_email = lead.get("email") and "@" in lead["email"]
    has_phone = lead.get("phone") and len(lead.get("phone", "")) >= 10
    has_whatsapp = lead.get("whatsapp", "") and len(lead.get("whatsapp", "")) >= 12
    has_website = bool(website.strip())
    has_instagram = bool(lead.get("instagram", "").strip())
    
    # Bonus for Brazilian context
    if is_br_domain:
        score += 20
    
    # Penalty for foreign domains
    if is_foreign:
        score -= 50

    if has_email:
        score += 25
        # Extra points for .br email
        if ".com.br" in lead["email"].lower():
            score += 10
            
    if has_phone:
        score += 20
        # Brazilian phone pattern check (usually start with (DD) 9... or similar)
        if any(c.isdigit() for c in lead.get("phone", "")):
            score += 5
            
    if has_whatsapp:
        score += 15
        if lead.get("whatsapp", "").startswith("55"):
            score += 10

    # BONUS FOR TECH SALES OPPORTUNITIES (Sites, Apps, Systems)
    if not has_website:
        # Leads without website are GOLD for site sales
        score += 40 
        pitch_hooks.append("Sem site oficial (oportunidade site/presença digital)")
    elif lead.get("site_quality") == "poor":
        # Bad sites are GOLD for redesign sales
        score += 35
        pitch_hooks.append("Site antigo ou lento (oportunidade redesign)")
    elif lead.get("ssl_valid") == 0 and lead.get("site_verified"):
        # Security issues are a great hook for sales
        score += 20
        pitch_hooks.append("Site não seguro/sem SSL (gancho de segurança)")
    
    # Systems & Integrations Opportunities
    # If they have ads but a poor site, they are wasting money -> HIGH PRIORITY
    if lead.get("has_ads") and (not has_website or lead.get("site_quality") == "poor"):
        score += 25
        pitch_hooks.append("Investe em anúncios mas tem site ruim (alta urgência)")

    # High-value sectors that need SYSTEMS and INTEGRATIONS
    systems_sectors = ["logística", "indústria", "distribuidor", "clínica", "hospital", "transportadora", "atacadista", "fábrica"]
    name_cat = (lead.get("name", "") + " " + lead.get("category", "")).lower()
    if any(sector in name_cat for sector in systems_sectors):
        score += 20
        pitch_hooks.append(f"Setor {lead.get('category', 'estratégico')} (demanda por sistemas/integrações)")
        # Extra points if it's a sector that needs complex systems
        if "logística" in name_cat or "indústria" in name_cat:
            score += 15

    if has_instagram and not has_website:
        score += 10
        pitch_hooks.append("Possui apenas Instagram (venda de landing page)")

    if lead.get("category") and len(lead.get("category", "")) > 2:
        score += 10

    name = lead.get("name", "")
    if name and len(name) >= 3 and " " in name:
        score += 5

    # Cap score
    score = max(0, min(100, score))

    if score >= 80:
        tier = "hot"
    elif score >= 50:
        tier = "warm"
    else:
        tier = "cold"

    pitch_hook = " | ".join(pitch_hooks) if pitch_hooks else "Lead padrão"
    return score, tier, pitch_hook
