def is_brazilian_domain(domain):
    if not domain:
        return False
    domain = domain.lower()
    if domain.endswith(".com.br") or domain.endswith(".net.br") or domain.endswith(".br"):
        return True
    
    # Check for gov/org that we want to avoid
    if ".gov.br" in domain or ".org.br" in domain or "jus.br" in domain:
        return False
    
    # Common global domains used in Brazil are neutral, but we'll check phone/whatsapp later
    return False

def is_gringo_domain(domain):
    if not domain:
        return False
    domain = domain.lower()
    
    # Gov/Org are treated as gringo to be disqualified
    if ".gov" in domain or ".org" in domain or ".edu" in domain or "prefeitura" in domain:
        return True

    # List of clearly foreign TLDs that are unlikely for a Brazilian target
    foreign_tlds = [
        ".us", ".uk", ".fr", ".de", ".it", ".es", ".ru", ".cn", ".jp", ".in",
        ".ca", ".au", ".mx", ".ar", ".cl", ".co", ".pe", ".py", ".uy", ".ie"
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

    # Hard disqualification for public/foreign entities
    if is_foreign:
        return 0, "cold", "Disqualificado (Estrangeiro/Governamental)"

    has_email = lead.get("email") and "@" in lead["email"]
    has_phone = lead.get("phone") and len(lead.get("phone", "")) >= 10
    has_whatsapp = lead.get("whatsapp", "") and len(lead.get("whatsapp", "")) >= 12
    has_website = bool(website.strip())
    has_instagram = bool(lead.get("instagram", "").strip())
    
    # Check for SP phone
    is_sp = False
    phone_raw = str(lead.get("phone", "")) + str(lead.get("whatsapp", ""))
    if "11" in phone_raw or "São Paulo" in lead.get("location", "") or "SP" in lead.get("location", ""):
        is_sp = True
        score += 25
    
    # Bonus for Brazilian context
    if is_br_domain:
        score += 35  # Aumentado de 20 para 35 para dar muito peso ao .com.br
    elif website and ".com" in website:
        score += 5   # .com genérico ganha pouco bônus

    if has_email:
        score += 15
        # Extra points for .br email
        if ".com.br" in lead["email"].lower():
            score += 15
            
    if has_phone:
        score += 15
        # Brazilian phone pattern check
        if any(c.isdigit() for c in lead.get("phone", "")):
            digits = "".join(filter(str.isdigit, str(lead.get("phone", ""))))
            if digits.startswith("55"):
                score += 15
            elif len(digits) in (10, 11):
                score += 10
            
    if has_whatsapp:
        score += 15
        if lead.get("whatsapp", "").startswith("55"):
            score += 20  # WhatsApp brasileiro é forte indicativo de negócio real
            
    # Bonus for having both WhatsApp AND Email (very contactable)
    if has_whatsapp and has_email:
        score += 15
        pitch_hooks.append("Contato multicanal disponível (WhatsApp + Email)")

    # PENALTIES FOR LOW QUALITY NAMES
    name_lower = lead.get("name", "").lower()
    garbage_names = ["empresa", "site", "home", "página", "contato", "index", "website", "oficial", "click here"]
    if any(name_lower == g for g in garbage_names) or len(name_lower) < 3:
        score -= 30
        pitch_hooks.append("Nome genérico ou incompleto (validar realismo)")

    # BONUS FOR TECH SALES OPPORTUNITIES (Sites, Apps, Systems)
    site_status = lead.get("site_status", "")
    has_reachable = has_whatsapp or has_email  # canal para de fato prospectar
    has_ads = bool(lead.get("has_ads"))

    if not has_website:
        if has_reachable and len(name_lower) > 5 and name_lower not in garbage_names:
            # Sem site + contato alcançável + nome real = GOLD para venda de site
            score += 45  # Aumentado para priorizar quem não tem site
            pitch_hooks.append("Sem site oficial (oportunidade site/presença digital)")
        else:
            score += 5
    elif site_status == "offline":
        score -= 15
        pitch_hooks.append("Site fora do ar (possível lead inválido ou domínio expirado)")
    elif lead.get("site_quality") == "poor":
        # Site ONLINE porém ruim/lento = GOLD para redesign (negócio real e ativo)
        if has_reachable:
            score += 40
            if has_ads:
                score += 25 # CRÍTICO: Jogando dinheiro fora com anúncios e site ruim
                pitch_hooks.append("🔥 ALTA URGÊNCIA: Investe em anúncios mas o site é ruim")
            else:
                pitch_hooks.append("Site antigo ou lento (oportunidade redesign)")
        else:
            score += 15
            pitch_hooks.append("Site com baixa qualidade (precisa encontrar contato)")
    
    # Bônus por Anúncios em site regular/bom (oportunidade de sistemas/LP de alta conversão)
    if has_ads and lead.get("site_quality") != "poor":
        score += 20
        pitch_hooks.append("Investe em anúncios (empresa com verba de marketing)")
    elif lead.get("site_quality") == "regular":
        # Site mediano online = upsell de modernização
        score += 18
        pitch_hooks.append("Site mediano (oportunidade de modernização/upsell)")
    elif lead.get("site_quality") == "good" and lead.get("site_verified"):
        # Negócio real com site bom: ainda é cliente para manutenção/evolução
        score += 8

    # Sinais adicionais de site desatualizado (apenas para sites que carregaram)
    if site_status == "live":
        import datetime as _dt
        current_year = _dt.datetime.now().year
        cy = lead.get("copyright_year") or 0
        if cy and cy < current_year - 4:
            score += 12
            pitch_hooks.append(f"Rodapé desatualizado (© {cy}) — site parece abandonado")

        if lead.get("ssl_expired"):
            score += 22
            pitch_hooks.append("Certificado SSL expirado (gancho de segurança urgente)")
        elif lead.get("ssl_valid") == 0 and lead.get("site_verified"):
            score += 18
            pitch_hooks.append("Site não seguro/sem SSL (gancho de segurança)")

        if lead.get("is_responsive") == 0:
            score += 10
            pitch_hooks.append("Site não responsivo no mobile (perda de clientes)")

    # Systems & Integrations Opportunities
    # Anuncia mas o site (online) é ruim = desperdício de verba -> ALTA PRIORIDADE
    if lead.get("has_ads") and (not has_website or lead.get("site_quality") == "poor"):
        score += 25
        pitch_hooks.append("Investe em anúncios mas o site é ruim (alta urgência)")

    # High-value sectors that need SYSTEMS and INTEGRATIONS
    systems_sectors = [
        "logística", "indústria", "distribuidor", "clínica", "hospital", 
        "transportadora", "atacadista", "fábrica", "contabilidade", 
        "engenharia", "arquitetura", "software", "tecnologia", "e-commerce"
    ]
    name_cat = (lead.get("name", "") + " " + lead.get("category", "")).lower()
    if any(sector in name_cat for sector in systems_sectors):
        score += 20
        pitch_hooks.append(f"Setor estratégico: {lead.get('category', 'business')} (demanda por sistemas/integrações)")
        # Extra points if it's a sector that needs complex systems
        if any(s in name_cat for s in ["logística", "indústria", "atacadista", "fábrica"]):
            score += 15

    if has_instagram and not has_website:
        score += 10
        pitch_hooks.append("Possui apenas Instagram (venda de landing page)")

    if lead.get("is_qualified"):
        score += 30
        pitch_hooks.append("Lead qualificado manualmente")

    if lead.get("category") and len(lead.get("category", "")) > 2:
        score += 10

    # Bônus por detecção de CMS/Tecnologia (Proposta de melhoria)
    cms = lead.get("cms", "")
    if cms and cms != "Custom/Other":
        if lead.get("site_quality") == "poor":
            score += 15
            pitch_hooks.append(f"Usa {cms} legado (oportunidade migração)")
        else:
            score += 8
            pitch_hooks.append(f"Tecnologia detectada: {cms}")
            
    # Detecção de E-commerce de Alto Ticket
    ecommerce_platforms = ["Shopify", "VTEX", "Nuvemshop", "WooCommerce", "Magento"]
    if any(p in cms for p in ecommerce_platforms):
        score += 15
        pitch_hooks.append(f"Plataforma E-commerce: {cms}")

    # Bônus por Pixel de Conversão (Lead que já sabe o valor do tráfego pago)
    if lead.get("has_pixel"):
        score += 10
        pitch_hooks.append("Pixel de rastreio detectado (empresa digitalizada)")

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
