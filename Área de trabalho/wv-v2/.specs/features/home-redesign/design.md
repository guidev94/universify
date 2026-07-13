# Design: Arquitetura do Redesign Premium

## 1. Estrutura de Arquivos (Protótipo)
```text
/
├── index.html          # Estrutura principal
├── assets/
│   ├── css/
│   │   └── main.css    # Tailwind + Custom Styles (Glassmorphism)
│   ├── js/
│   │   ├── main.js     # Entry point & Lenis Setup
│   │   ├── animations.js # Lógica GSAP isolada
│   │   └── components.js # Micro-interações (Magnetic, Hover)
│   └── img/            # Assets otimizados
```

## 2. Componentes Core (Tailwind + GSAP Classes)

### Header (Glassmorphic)
- **Classe**: `fixed top-0 w-full z-50 backdrop-blur-md bg-slate-900/10 border-b border-white/10`
- **Comportamento**: Encolhe ao scrollar (`gsap.to(header, { py: 2 })`).

### Hero Title
- **Classe**: `text-[clamp(3rem,8vw,6rem)] font-black tracking-tighter leading-none`
- **Target**: `.reveal-text`

### Bento Card
- **Classe**: `bg-white/5 backdrop-blur-lg border border-white/10 rounded-[2.5rem] p-8 overflow-hidden relative`
- **Target**: `.bento-item`

## 3. Estratégia de Animação (GSAP Timeline)

### Lógica de Scroll Progress
Usaremos uma timeline global controlada pelo ScrollTrigger para garantir que a coreografia seja síncrona.

```javascript
const tl = gsap.timeline({
  scrollTrigger: {
    trigger: "#main-wrapper",
    start: "top top",
    end: "bottom bottom",
    scrub: 1
  }
});
```

## 4. Mockups & Assets
- **Imagens**: Usar placeholders de alta qualidade (Picsum/Unsplash) simulando os projetos reais (Coca-Cola, etc).
- **Ícones**: Phosphor Icons (para um look moderno e limpo).
