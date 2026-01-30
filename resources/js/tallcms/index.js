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

// Expose plugins on window for registration before Livewire/Alpine starts
window.__tallcmsPlugins = window.__tallcmsPlugins || [];
window.__tallcmsPlugins.push(intersect);

// Native block components
import './components/contact-form';

// Future components will be added here:
// import './components/image-gallery';
// import './components/pricing-calculator';
