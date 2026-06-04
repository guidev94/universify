document.addEventListener('DOMContentLoaded', () => {
    if (typeof gsap === 'undefined') return;

    gsap.defaults({ ease: 'power2.out', duration: 0.6 });

    gsap.from('.stat-card', {
        y: 30,
        opacity: 0,
        duration: 0.5,
        stagger: 0.08,
        ease: 'power3.out',
    });

    gsap.from('.glass-card:not(.stat-card)', {
        y: 20,
        opacity: 0,
        duration: 0.5,
        stagger: 0.06,
        delay: 0.3,
        ease: 'power2.out',
    });

    gsap.from('.glass-card-static', {
        y: 15,
        opacity: 0,
        duration: 0.4,
        delay: 0.4,
    });

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

    document.querySelectorAll('.sidebar-link').forEach(link => {
        link.addEventListener('mouseenter', () => {
            gsap.to(link, { x: 4, duration: 0.2, ease: 'power2.out' });
        });
        link.addEventListener('mouseleave', () => {
            gsap.to(link, { x: 0, duration: 0.2, ease: 'power2.out' });
        });
    });

    document.querySelectorAll('.icon-box').forEach(box => {
        box.addEventListener('mouseenter', () => {
            gsap.to(box, { scale: 1.1, duration: 0.2, ease: 'power2.out' });
        });
        box.addEventListener('mouseleave', () => {
            gsap.to(box, { scale: 1, duration: 0.3, ease: 'elastic.out(1, 0.6)' });
        });
    });
});
