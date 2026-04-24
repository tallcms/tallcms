// Elevate Theme JavaScript
// Core CMS components (contact-form, comments, etc.) are loaded globally
// via @tallcmsCoreJs in the layout — no need to import them here.

// Floating nav scroll behavior
(function() {
    const navBar = document.querySelector('.nav-bar');
    const navShell = document.querySelector('.nav-shell');
    if (!navBar || !navShell) return;

    const onScroll = () => {
        const scrolled = window.scrollY > 10;
        navBar.classList.toggle('nav-bar-scrolled', scrolled);
        navShell.classList.toggle('nav-shell-scrolled', scrolled);
    };

    window.addEventListener('scroll', onScroll, { passive: true });
    onScroll();
})();
