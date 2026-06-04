def calculate_score(lead):
    score = 0

    if lead.get("email") and "@" in lead["email"]:
        score += 25

    if lead.get("phone") and len(lead.get("phone", "")) >= 10:
        score += 20

    whatsapp = lead.get("whatsapp", "")
    if whatsapp and len(whatsapp) >= 12:
        score += 20

    if lead.get("site_verified"):
        score += 15

    if lead.get("category") and len(lead.get("category", "")) > 2:
        score += 10

    name = lead.get("name", "")
    if name and len(name) >= 3 and " " in name:
        score += 10
    elif name and len(name) >= 3:
        score += 5

    if score >= 80:
        tier = "hot"
    elif score >= 50:
        tier = "warm"
    else:
        tier = "cold"

    return score, tier
