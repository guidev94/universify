# Let's Work — Design System & Developer Handoff

This document serves as the single source of truth for the Let's Work frontend design system. It details the typography, color palette, UI components, layout structures, and micro-interactions implemented in the core platform.

Built with **Tailwind CSS**, **GSAP**, and **Lucide Icons**, this design language focuses on a modern, high-performance, and native-feeling web experience (incorporating "Linear/Vercel" aesthetic principles combined with robust enterprise UX).

---

## 1. Typography

The typographic hierarchy uses a highly legible sans-serif stack, split between structural UI elements and impactful display headings.

### Display Font: `Plus Jakarta Sans`
Used for all major headings, marketing copy, and prominent numbers to convey a modern tech aesthetic.
*   **Tailwind Class:** `font-display`
*   **Weights:** 400 (Regular), 500 (Medium), 600 (SemiBold), 700 (Bold), 800 (ExtraBold), 900 (Black).
*   **Key Utilities:** 
    *   `.text-huge`: `clamp(3rem, 8vw, 7rem)` with `line-height: 0.95` and `letter-spacing: -0.04em`.
    *   Tracking tight (`tracking-tight` or `tracking-tighter`) is strictly applied to main headings (`h1`, `h2`).

### Body & UI Font: `Inter`
Used for data visualization, body copy, form inputs, and dense UI components to ensure maximum readability.
*   **Tailwind Class:** `font-sans` (Default body font)
*   **Weights:** 400 (Regular), 500 (Medium), 600 (SemiBold).

### Special Text Utilities
*   **`.gradient-text`**: Linear gradient text effect. 
    *   Light Mode: `#000` to `#666`
    *   Dark Mode: `#FFF` to `#888`

---

## 2. Color Palette

The color system is explicitly mapped for perfect Light/Dark mode transitions. We prioritize OLED blacks in dark mode for infinite contrast and battery-saving on mobile.

| Role | Light Mode | Dark Mode | Tailwind Configuration |
| :--- | :--- | :--- | :--- |
| **Primary (Brand)** | `#FF2D2D` | `#FF2D2D` | `bg-primary`, `text-primary` |
| **Background (Base)**| `#FFFFFF` | `#000000` (OLED) | `bg-white`, `bg-black` |
| **Surface** | `#F8FAFC` / `#FFFFFF`| `#0A0A0A` | `bg-surface-light`, `bg-surface-dark` |
| **Text (Default)** | `#0F172A` | `#FFFFFF` | `text-slate-900`, `text-white` |
| **Text (Subtle)** | `#475569` | `#A1A1AA` | `text-subtle-light`, `text-subtle-dark` |
| **Borders** | `rgba(0,0,0,0.08)` | `rgba(255,255,255,0.08)`| `border-borderline-light/dark` |

---

## 3. Shadows & Depth

Shadows are used sparingly. The design heavily relies on borders (`1px solid var(--border-color)`) for structural definition, using shadows primarily for floating/interactive elements or highlighting the primary brand color.

*   **Soft Shadow (`shadow-soft`)**: `0 20px 40px -15px rgba(0,0,0,0.05)`
    *   *Usage*: Cards, dropdowns, and resting floating elements.
*   **Glow Shadow (`shadow-glow`)**: `0 0 30px rgba(255,45,45,0.15)`
    *   *Usage*: Primary CTAs and active states.

---

## 4. UI Components

### 4.1. Cards (`.solid-card`)
A departure from overused glassmorphism, opting for a clean, solid, SaaS-like presentation.
*   **Background:** `#F8FAFC` (Light) / `#0A0A0A` (Dark).
*   **Border:** `1px solid var(--border-color)`.
*   **Hover State:** Border transitions to `#FF2D2D` (`border-color 0.3s ease`).
*   **Border Radii:** `rounded-2xl` is the standard for generic cards.

### 4.2. Buttons (`.btn-primary`)
*   **Base:** Background `#FF2D2D`, text `white`.
*   **Hover State:** Background becomes transparent, text becomes `#FF2D2D`, and an inset shadow (`box-shadow: inset 0 0 0 1px #FF2D2D`) creates an outlined look. Adds a slight scale (`transform: scale(1.02)`).
*   **Active/Focus:** Scales down (`transform: scale(0.98)`). Focus visible uses a customized red ring.

### 4.3. Radii & Shape Language
*   `rounded-xl` (12px): Small UI elements, icons wraps.
*   `rounded-2xl` (16px): Standard cards, dropdowns, typical buttons.
*   `rounded-[2rem]` (32px): Large content blocks, feature highlights.
*   `rounded-[3rem]` (48px): CSS-based mobile/iPhone mockups.

---

## 5. Animations & Micro-interactions

The system is highly interactive, utilizing CSS for lightweight animations and GSAP for complex scroll-linked or physics-based movements.

### 5.1. Magnetic Buttons (`.magnetic`)
Interactive elements that slightly "pull" towards the user's cursor on hover. 
*   *Implementation:* Driven by GSAP via a custom script binding to `mousemove` and `mouseleave` events, applying a mapped transform relative to the cursor's coordinates.

### 5.2. Marquees
*   **Flat Marquee (`.marquee-container`)**: Infinite horizontal scrolling for client logos (`animation: marquee 40s linear infinite`). Features a CSS mask-image fade on edges.
*   **3D Testimonial Marquee**: 
    *   *Base State:* Applies perspective (`perspective: 1500px`) and a 3D transform (`rotateX(10deg) rotateY(-5deg) rotateZ(2deg)`).
    *   *Hover State:* Unfolds to a flat plane (`rotateX(0deg) scale(1.02)`) and pauses the animation, allowing the user to read cards clearly.

### 5.3. Scroll Storytelling (Sticky Layouts)
Used in the "Soluções" section to pin graphical assets (CSS mockups) while text scrolls alongside them. 
*   *Tech:* Uses Tailwind `sticky top-0 h-screen` for the visual wrapper, syncing opacity and transforms via GSAP `ScrollTrigger`.

---

## 6. Backgrounds & Textures

*   **Noise Overlay**: A fixed SVG noise filter applied globally via `fixed inset-0 pointer-events-none z-50`.
    *   *Opacity:* `opacity-[0.03]` (Light Mode) / `opacity-[0.05]` (Dark Mode).
*   **Tech Grid (`.tech-grid`)**: A minimalist coordinate grid using linear gradients and masked by a radial gradient to fade out towards the edges, simulating a "developer/tech" blueprint feel.

---

## 7. Layout & Spacing System

*   **Global Containers**: `max-w-[1400px]` for standard sections, `max-w-[1200px]` for focused storytelling blocks (like the Chatbot section).
*   **Vertical Rhythm**: Standard padding for main sections is `py-20 lg:py-24`.
*   **Grid Systems**: Highly dependent on CSS Grid (`grid-cols-12` for asymmetrical hero sections, `grid-cols-4` for standard feature blocks).
*   **Gaps**: Standard structural gaps are `gap-6` (cards), `gap-12` (hero splits), and `gap-16` (large feature splits).

---

## 8. Iconography

**Lucide Icons** (`lucide@latest`) is the official icon library.
*   *Implementation:* Loaded via script tag, initialized globally. HTML nodes use the `data-lucide="icon-name"` attribute.
*   *Styling:* Controlled purely via Tailwind utility classes (`w-6 h-6 text-primary`, `w-4 h-4 opacity-50`, etc.).
*   *Stroke Width:* Standard 2px stroke is maintained across all UI elements.

---

## Usage Example: Feature Card Handoff

```html
<div class="solid-card shadow-soft p-8 rounded-2xl flex flex-col h-full group">
    <!-- Icon Container -->
    <div class="w-12 h-12 rounded-xl bg-primary/10 flex items-center justify-center text-primary mb-6 group-hover:scale-110 transition-transform">
        <i data-lucide="folder" class="w-6 h-6"></i>
    </div>
    
    <!-- Typography Context -->
    <h3 class="text-xl font-bold mb-3 text-black dark:text-white">Gestão de Documentos</h3>
    <p class="text-subtle-light dark:text-subtle-dark text-sm leading-relaxed mb-6 flex-grow">
        Organize atestados e holerites em um cofre digital seguro.
    </p>
    
    <!-- Inline Action -->
    <a href="#" class="text-primary font-bold text-sm inline-flex items-center gap-2 group-hover:text-black dark:group-hover:text-white transition-colors mt-auto">
        Conheça o módulo <i data-lucide="arrow-right" class="w-4 h-4 group-hover:translate-x-1 transition-transform"></i>
    </a>
</div>
```

---
*Created automatically to ensure UI consistency across the Let's Work Frontend Application stack.*
## 9. Internal Page Layout (Grid 8/4)

For internal pages (Solutions, Features, Conheça, Blog), the structure must strictly adhere to the following semantic layout to ensure uniformity:

### 9.1 Title Bar (Header)
*   **Background:** `bg-surface-light dark:bg-surface-dark` with `border-b`.
*   **Pattern:** Absolute subtle linear-gradient grid.
*   **Grid:** `lg:grid-cols-12`.
    *   **Col 6 (Left):** Breadcrumbs, Title (`h1.font-display`), Subtitle, Investor Logos (BOSSA, BRDE, ORGÂNICA).
    *   **Col 6 (Right):** Lead Generation Form (Agende uma Demonstração) embedded in a `.solid-card` with `shadow-glow border-primary/20`.

### 9.2 Main Body
*   **Grid:** `max-w-[1400px] grid lg:grid-cols-12 gap-12`.
*   **Col 8 (Left - Content):** 
    *   `prose prose-lg` for dense reading.
    *   Feature cards in `sm:grid-cols-2`.
    *   Highlight section (`.solid-card` with watermark icon).
    *   FAQ accordion using `<details>` and `<summary>`.
*   **Col 4 (Right - Sidebar):** 
    *   Must be `sticky top-32`.
    *   Contains CTA Widget (`shadow-glow`) and Pricing Widget (`.solid-card`).
