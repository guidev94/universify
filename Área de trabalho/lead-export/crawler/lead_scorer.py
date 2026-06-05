def calculate_score(lead):
    score = 0

    has_email = lead.get("email") and "@" in lead["email"]
    has_phone = lead.get("phone") and len(lead.get("phone", "")) >= 10
    has_whatsapp = lead.get("whatsapp", "") and len(lead.get("whatsapp", "")) >= 12
    has_website = bool(lead.get("website", "").strip())
    has_instagram = bool(lead.get("instagram", "").strip())

    if has_email:
        score += 25
    if has_phone:
        score += 20
    if has_whatsapp:
        score += 15

    if not has_website:
        score += 20
    elif lead.get("site_quality") == "poor":
        score += 15
    elif lead.get("ssl_valid") == 0 and lead.get("site_verified"):
        score += 10

    if has_instagram and not has_website:
        score += 15

    if lead.get("category") and len(lead.get("category", "")) > 2:
        score += 10

    name = lead.get("name", "")
    if name and len(name) >= 3 and " " in name:
        score += 5

    score = min(100, score)

    if score >= 75:
        tier = "hot"
    elif score >= 45:
        tier = "warm"
    else:
        tier = "cold"

    return score, tier
