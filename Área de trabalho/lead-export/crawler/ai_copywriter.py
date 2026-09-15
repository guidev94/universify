import logging

logger = logging.getLogger("leadhunter.ai")

class AICopywriter:
    """Generates personalized sales approach based on lead intelligence."""

    def generate_message(self, lead):
        name = lead.get("name", "amigo")
        first_name = name.split()[0].title()
        niche = (lead.get("category") or "seu segmento").lower()
        cms = lead.get("cms")
        has_pixel = lead.get("has_pixel")
        has_ads = lead.get("has_ads")
        quality = lead.get("site_quality")
        partners = lead.get("partners") or ""
        
        # Determine the "Hook" (Pain Point)
        hooks = []
        if quality == "poor" and lead.get("website"):
            hooks.append("percebi que seu site atual é um pouco antigo e não está otimizado para dispositivos móveis")
        if not has_pixel and has_ads:
            hooks.append("vi que você investe em anúncios mas ainda não tem o Pixel de rastreio instalado, o que faz você perder dinheiro")
        if cms == "Wix":
            hooks.append("vi que seu site é feito em Wix, o que pode estar limitando seu desempenho e SEO")
        if not lead.get("website"):
            hooks.append("vi que sua empresa ainda não possui um site oficial, o que acaba reduzindo sua autoridade frente aos concorrentes")
        if lead.get("ssl_valid") == 0 and lead.get("website"):
            hooks.append("notei que seu site não possui certificado de segurança SSL, o que afasta clientes e prejudica o Google")

        main_hook = hooks[0] if hooks else "gostaria de apresentar uma solução para aumentar sua visibilidade e vendas online"

        # Owner/Decision Maker Logic
        owner_name = ""
        if "Detectado:" in partners:
            owner_name = partners.split("Detectado:")[1].split(";")[0].split("(")[0].strip()
        elif partners and ";" in partners: # From CNPJ
            owner_name = partners.split(";")[0].split("(")[0].strip().title()
        elif partners:
             owner_name = partners.split("(")[0].strip().title()

        if owner_name:
            greeting = f"Olá {owner_name}! Falo com o responsável pela {lead.get('name')}?"
        else:
            greeting = f"Olá! Tudo bem? Gostaria de falar com o responsável pela {lead.get('name')}."

        # Assemble Message
        message = (
            f"{greeting}\n\n"
            f"Estava analisando o posicionamento digital de empresas de {niche} e {main_hook}.\n\n"
            "Trabalhamos com tecnologia e design focados em conversão. Acredito que pequenos ajustes "
            "no seu ecossistema digital podem trazer um retorno imediato em vendas.\n\n"
            "Você teria 5 minutos para uma conversa rápida por aqui?"
        )
        
        return message

    def generate_email_subject(self, lead):
        niche = lead.get("category") or "seu negócio"
        if lead.get("site_quality") == "poor":
            return f"Otimização do site: {lead.get('name')}"
        return f"Proposta estratégica para {niche}"
