/**
 * TallCMS Core Alpine.js Components
 *
 * Native Alpine components and plugins required by CMS blocks.
 * Loaded globally via resources/js/tallcms-core.js and the @tallcmsCoreJs
 * Blade directive. Themes do NOT need to import this — it ships as a
 * shared runtime asset built from the root Vite config.
 *
 * Note: Alpine.js is provided by Livewire. Plugins are exposed on window
 * and registered via an inline script before @livewireScripts in the layout.
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
import './components/comments';
