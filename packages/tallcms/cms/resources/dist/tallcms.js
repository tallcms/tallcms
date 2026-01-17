/**
 * TallCMS Frontend JavaScript
 *
 * Alpine.js components for CMS blocks.
 * These components are registered globally for use in Blade templates.
 *
 * @version 2.0.0
 */

document.addEventListener('alpine:init', () => {
    /**
     * Contact Form Component
     *
     * Handles form submission with AJAX, validation feedback, and honeypot protection.
     */
    Alpine.data('contactForm', function() {
        // Get config from data attribute
        const configEl = this.$el;
        const configData = configEl.dataset.contactFormConfig
            ? JSON.parse(configEl.dataset.contactFormConfig)
            : {};

        return {
            formData: {},
            submitting: false,
            submitted: false,
            formError: null,
            successMessage: configData.successMessage || 'Thank you for your message!',

            init() {
                // Initialize form data for all fields
                if (configData.fieldNames) {
                    configData.fieldNames.forEach(name => {
                        this.formData[name] = '';
                    });
                }
                // Honeypot field
                this.formData._honeypot = '';
            },

            async submit() {
                // Check honeypot
                if (this.formData._honeypot) {
                    // Silently fail for bots
                    this.submitted = true;
                    return;
                }

                this.submitting = true;
                this.formError = null;

                try {
                    const response = await fetch(configData.submitUrl, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                        },
                        body: JSON.stringify({
                            ...this.formData,
                            _config: configData.config,
                            _signature: configData.signature,
                            _page_url: configData.pageUrl,
                        }),
                    });

                    const data = await response.json();

                    if (!response.ok) {
                        if (data.errors) {
                            // Validation errors
                            const errorMessages = Object.values(data.errors).flat();
                            this.formError = errorMessages.join(' ');
                        } else {
                            this.formError = data.message || 'An error occurred. Please try again.';
                        }
                        return;
                    }

                    this.submitted = true;
                } catch (error) {
                    console.error('Contact form error:', error);
                    this.formError = 'An unexpected error occurred. Please try again later.';
                } finally {
                    this.submitting = false;
                }
            }
        };
    });

    /**
     * FAQ Accordion Component
     *
     * Handles expand/collapse of FAQ items.
     */
    Alpine.data('faqAccordion', () => ({
        openItem: null,

        toggle(index) {
            this.openItem = this.openItem === index ? null : index;
        },

        isOpen(index) {
            return this.openItem === index;
        }
    }));

    /**
     * Image Gallery Lightbox Component
     *
     * Handles image lightbox functionality for galleries.
     */
    Alpine.data('imageGallery', () => ({
        lightboxOpen: false,
        currentImage: null,
        currentIndex: 0,
        images: [],

        init() {
            // Collect all images from gallery items
            this.$nextTick(() => {
                const items = this.$el.querySelectorAll('[data-gallery-image]');
                this.images = Array.from(items).map(item => ({
                    src: item.dataset.galleryImage,
                    alt: item.dataset.galleryAlt || '',
                    caption: item.dataset.galleryCaption || ''
                }));
            });
        },

        open(index) {
            this.currentIndex = index;
            this.currentImage = this.images[index];
            this.lightboxOpen = true;
            document.body.style.overflow = 'hidden';
        },

        close() {
            this.lightboxOpen = false;
            this.currentImage = null;
            document.body.style.overflow = '';
        },

        next() {
            this.currentIndex = (this.currentIndex + 1) % this.images.length;
            this.currentImage = this.images[this.currentIndex];
        },

        prev() {
            this.currentIndex = (this.currentIndex - 1 + this.images.length) % this.images.length;
            this.currentImage = this.images[this.currentIndex];
        }
    }));

    /**
     * Tabs Component
     *
     * Simple tab switching for content blocks.
     */
    Alpine.data('tabs', (defaultTab = 0) => ({
        activeTab: defaultTab,

        setTab(index) {
            this.activeTab = index;
        },

        isActive(index) {
            return this.activeTab === index;
        }
    }));

    /**
     * Mobile Menu Component
     *
     * Handles mobile navigation toggle.
     */
    Alpine.data('mobileMenu', () => ({
        open: false,

        toggle() {
            this.open = !this.open;
        },

        close() {
            this.open = false;
        }
    }));

    /**
     * Scroll Animation Component
     *
     * Triggers animations when elements enter viewport.
     */
    Alpine.data('scrollAnimate', (animationClass = 'animate-fadeIn') => ({
        visible: false,

        init() {
            const observer = new IntersectionObserver(
                (entries) => {
                    entries.forEach(entry => {
                        if (entry.isIntersecting) {
                            this.visible = true;
                            observer.unobserve(entry.target);
                        }
                    });
                },
                { threshold: 0.1 }
            );

            observer.observe(this.$el);
        }
    }));
});

/**
 * Utility: Format numbers with separators
 */
window.tallcmsFormatNumber = (num) => {
    return new Intl.NumberFormat().format(num);
};

/**
 * Utility: Smooth scroll to element
 */
window.tallcmsScrollTo = (selector, offset = 0) => {
    const element = document.querySelector(selector);
    if (element) {
        const top = element.getBoundingClientRect().top + window.pageYOffset - offset;
        window.scrollTo({ top, behavior: 'smooth' });
    }
};
