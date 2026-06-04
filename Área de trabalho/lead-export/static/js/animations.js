document.addEventListener('DOMContentLoaded', () => {
    if (typeof gsap === 'undefined') return;

    gsap.defaults({ ease: 'power2.out', duration: 0.6 });

    // Stat cards entrance
    gsap.from('.stat-card', {
        y: 30,
        opacity: 0,
        duration: 0.5,
        stagger: 0.08,
        ease: 'power3.out',
    });

    // Glass cards entrance
    gsap.from('.glass-card:not(.stat-card)', {
        y: 20,
        opacity: 0,
        duration: 0.5,
        stagger: 0.06,
        delay: 0.3,
        ease: 'power2.out',
    });

    // Metrics strip
    gsap.from('.glass-card-static', {
        y: 15,
        opacity: 0,
        duration: 0.4,
        delay: 0.4,
    });

    // Stat number countUp
    document.querySelectorAll('.stat-number').forEach(el => {
        const observer = new MutationObserver(() => {
            const val = parseInt(el.textContent.replace(/\D/g, ''));
            if (val > 0 && !el.dataset.animated) {
                el.dataset.animated = 'true';
                const obj = { val: 0 };
                gsap.to(obj, {
                    val: val,
                    duration: 1.2,
                    ease: 'power2.out',
                    onUpdate: () => {
                        el.textContent = Math.round(obj.val).toLocaleString('pt-BR');
                    }
                });
            }
        });
        observer.observe(el, { childList: true, characterData: true, subtree: true });
    });

    // Button press feedback
    document.querySelectorAll('.btn-accent, .btn-ghost').forEach(btn => {
        btn.addEventListener('mousedown', () => {
            gsap.to(btn, { scale: 0.95, duration: 0.1 });
        });
        btn.addEventListener('mouseup', () => {
            gsap.to(btn, { scale: 1, duration: 0.3, ease: 'elastic.out(1, 0.5)' });
        });
        btn.addEventListener('mouseleave', () => {
            gsap.to(btn, { scale: 1, duration: 0.2 });
        });
    });

    // Sidebar link hover
    document.querySelectorAll('.sidebar-link').forEach(link => {
        link.addEventListener('mouseenter', () => {
            gsap.to(link, { x: 4, duration: 0.2, ease: 'power2.out' });
        });
        link.addEventListener('mouseleave', () => {
            gsap.to(link, { x: 0, duration: 0.2, ease: 'power2.out' });
        });
    });

    // Table rows entrance (when content loads)
    const tableObserver = new MutationObserver((mutations) => {
        mutations.forEach(mutation => {
            if (mutation.addedNodes.length) {
                const rows = mutation.target.querySelectorAll('tr');
                if (rows.length > 1) {
                    gsap.from(rows, {
                        opacity: 0,
                        x: -10,
                        duration: 0.3,
                        stagger: 0.03,
                        ease: 'power2.out',
                    });
                }
            }
        });
    });

    document.querySelectorAll('tbody').forEach(tbody => {
        tableObserver.observe(tbody, { childList: true });
    });

    // Icon boxes subtle pulse on hover
    document.querySelectorAll('.icon-box').forEach(box => {
        box.addEventListener('mouseenter', () => {
            gsap.to(box, { scale: 1.1, duration: 0.2, ease: 'power2.out' });
        });
        box.addEventListener('mouseleave', () => {
            gsap.to(box, { scale: 1, duration: 0.3, ease: 'elastic.out(1, 0.6)' });
        });
    });
});
