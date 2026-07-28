/**
 * WV theme — light default, persist preference, sync toggle UI.
 * Storage key: wv-theme → "light" | "dark"
 */
(function () {
    const STORAGE_KEY = "wv-theme";
    const DEFAULT_THEME = "light";

    const normalize = (value) => (value === "dark" || value === "light" ? value : DEFAULT_THEME);

    const readStored = () => {
        try {
            return normalize(localStorage.getItem(STORAGE_KEY));
        } catch {
            return DEFAULT_THEME;
        }
    };

    const writeStored = (theme) => {
        try {
            localStorage.setItem(STORAGE_KEY, theme);
        } catch {
            /* ignore quota / private mode */
        }
    };

    const metaThemeColor = () => document.querySelector('meta[name="theme-color"]');

    const applyTheme = (theme) => {
        const next = normalize(theme);
        document.documentElement.setAttribute("data-theme", next);

        const meta = metaThemeColor();
        if (meta) {
            const color =
                getComputedStyle(document.documentElement).getPropertyValue("--wv-theme-meta").trim() ||
                (next === "dark" ? "#0a0a0a" : "#f5f5f2");
            meta.setAttribute("content", color);
        }

        document.querySelectorAll("[data-theme-toggle]").forEach((btn) => {
            const label =
                next === "light" ? "Ativar tema escuro" : "Ativar tema claro";
            btn.setAttribute("aria-label", label);
            btn.setAttribute("title", label);
            btn.setAttribute("aria-pressed", next === "dark" ? "true" : "false");
        });
    };

    const toggleTheme = () => {
        const current = normalize(document.documentElement.getAttribute("data-theme"));
        const next = current === "light" ? "dark" : "light";
        writeStored(next);
        applyTheme(next);
    };

    // Re-apply after paint (handles SSR / WP without early snippet, syncs buttons)
    applyTheme(readStored());

    const bindToggles = () => {
        document.querySelectorAll("[data-theme-toggle]").forEach((btn) => {
            if (btn.dataset.themeBound === "1") return;
            btn.dataset.themeBound = "1";
            btn.addEventListener("click", (e) => {
                e.preventDefault();
                toggleTheme();
            });
        });
        applyTheme(readStored());
    };

    if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", bindToggles);
    } else {
        bindToggles();
    }

    window.WVTheme = { applyTheme, toggleTheme, readStored, STORAGE_KEY, DEFAULT_THEME };
})();
