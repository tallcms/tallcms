/**
 * TallCMS Core Alpine.js Components
 *
 * This file exports all native Alpine components required by TallCMS blocks.
 * Themes should import this file to ensure all native blocks function correctly.
 *
 * Usage in theme's app.js:
 *   import '../../../../resources/js/tallcms';
 *
 * @version 1.0.0
 */

// Alpine plugins
import intersect from '@alpinejs/intersect';

// Register immediately if Alpine already exists, otherwise wait for alpine:init
if (window.Alpine) {
    window.Alpine.plugin(intersect);
} else {
    document.addEventListener('alpine:init', () => {
        window.Alpine.plugin(intersect);
    });
}

// Native block components
import './components/contact-form';

// Future components will be added here:
// import './components/image-gallery';
// import './components/pricing-calculator';
