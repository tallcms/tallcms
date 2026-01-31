/**
 * TallCMS Core Alpine.js Components
 *
 * This file exports all native Alpine components required by TallCMS blocks.
 * Themes should import this file to ensure all native blocks function correctly.
 *
 * Usage in theme's app.js:
 *   import '../../../../resources/js/tallcms';
 *
 * Note: Alpine.js is provided by Livewire. Plugins are exposed on window
 * and registered via an inline script before @livewireScripts in the layout.
 *
 * @version 1.0.0
 */

import intersect from '@alpinejs/intersect';

// Register plugins with Alpine
// If Alpine exists, register immediately; otherwise wait for alpine:init
if (window.Alpine) {
    window.Alpine.plugin(intersect);
} else {
    // Store for layout-level registration AND set up fallback listener
    window.__tallcmsPlugins = window.__tallcmsPlugins || [];
    window.__tallcmsPlugins.push(intersect);

    document.addEventListener('alpine:init', () => {
        (window.__tallcmsPlugins || []).forEach(plugin => {
            window.Alpine.plugin(plugin);
        });
        window.__tallcmsPlugins = []; // Clear to avoid double registration
    });
}

// Native block components
import './components/contact-form';

// Future components will be added here:
// import './components/image-gallery';
// import './components/pricing-calculator';
