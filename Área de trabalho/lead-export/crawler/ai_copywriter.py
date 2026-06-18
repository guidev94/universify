import logging

logger = logging.getLogger("leadhunter.ai")

class AICopywriter:
    """Generates personalized sales approach based on lead intelligence."""

    def generate_message(self, lead):
        name = lead.get("name", "amigo")
        first_name = name.split()[0]
        niche = lead.get("category") or "seu segmento"
        cms = lead.get("cms")
        has_pixel = lead.get("has_pixel")
        has_ads = lead.get("has_ads")
        quality = lead.get("site_quality")
        partners = lead.get("partners") or ""
        
        # Determine the "Hook" (Pain Point)
        hooks = []
        if quality == "poor":
            hooks.append("percebi que seu site atual é um pouco antigo e não está otimizado para dispositivos móveis")
        if not has_pixel and has_ads:
            hooks.append("vi que você investe em anúncios mas ainda não tem o Pixel de rastreio instalado, o que faz você perder dinheiro")
        if cms == "Wix":
            hooks.append("vi que seu site é feito em Wix, o que pode estar limitando seu ranqueamento no Google")
        if not lead.get("website"):
            hooks.append("vi que sua empresa ainda não possui um site oficial, o que afeta sua autoridade no mercado")

        main_hook = hooks[0] if hooks else "gostaria de apresentar uma solução para aumentar suas vendas online"

        # Owner mention
        owner_part = ""
        if "Detectado:" in partners:
            owner_name = partners.split("Detectado:")[1].split(";")[0].strip()
            owner_part = f"Poderia falar com o {owner_name}? "

        # Assemble Message
        message = (
            f"Olá {first_name}! {owner_part}Tudo bem?\n\n"
            f"Estava analisando o posicionamento digital de empresas de {niche} e {main_hook}.\n\n"
            "Desenvolvemos projetos focados em conversão que poderiam ajudar muito o seu negócio a escalar. "
            "Você teria 5 minutos para uma conversa rápida amanhã?"
        )
        
        return message

    def generate_email_subject(self, lead):
        niche = lead.get("category") or "seu negócio"
        if lead.get("site_quality") == "poor":
            return f"Otimização do site: {lead.get('name')}"
        return f"Proposta estratégica para {niche}"
