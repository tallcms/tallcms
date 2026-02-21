/**
 * Comment Form Alpine.js Component
 *
 * Handles comment and reply form submission with CSRF protection,
 * validation errors, and rate limiting.
 */
function registerCommentForm() {
    if (typeof Alpine === 'undefined') return false;

    Alpine.data('commentForm', () => ({
        // State
        formData: {
            author_name: '',
            author_email: '',
            content: '',
            _honeypot: '',
        },
        errors: {},
        formError: '',
        submitted: false,
        submitting: false,

        // Config (populated from data attribute)
        submitUrl: '',
        postId: null,
        parentId: null,

        init() {
            const configAttr = this.$el.dataset.commentConfig;
            if (!configAttr) {
                console.error('Comment form missing data-comment-config attribute');
                return;
            }

            try {
                const config = JSON.parse(configAttr);
                this.submitUrl = config.submitUrl || '';
                this.postId = config.postId || null;
                this.parentId = config.parentId || null;
            } catch (e) {
                console.error('Failed to parse comment form config:', e);
            }
        },

        async submit() {
            this.submitting = true;
            this.errors = {};
            this.formError = '';

            const csrfMeta = document.querySelector('meta[name=csrf-token]');
            if (!csrfMeta) {
                this.formError = 'Security token missing. Please refresh the page.';
                this.submitting = false;
                return;
            }

            try {
                const payload = {
                    post_id: this.postId,
                    content: this.formData.content,
                    _honeypot: this.formData._honeypot,
                };

                if (this.parentId) {
                    payload.parent_id = this.parentId;
                }

                if (this.formData.author_name) {
                    payload.author_name = this.formData.author_name;
                }
                if (this.formData.author_email) {
                    payload.author_email = this.formData.author_email;
                }

                const response = await fetch(this.submitUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfMeta.content,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify(payload),
                });

                const contentType = response.headers.get('content-type');
                if (!contentType || !contentType.includes('application/json')) {
                    this.formError = 'Server returned an unexpected response. Please try again.';
                    return;
                }

                let data;
                try {
                    data = await response.json();
                } catch (parseError) {
                    this.formError = 'Server returned an invalid response. Please try again.';
                    return;
                }

                if (response.ok) {
                    this.submitted = true;
                } else if (response.status === 422) {
                    this.errors = data.errors || {};
                } else if (response.status === 429) {
                    this.formError = data.message || 'Too many comments. Please try again later.';
                } else if (response.status === 403) {
                    this.formError = data.message || 'You must be logged in to comment.';
                } else {
                    this.formError = data.message || 'An error occurred. Please try again.';
                }
            } catch (error) {
                console.error('Comment submission error:', error);
                this.formError = 'A network error occurred. Please check your connection and try again.';
            } finally {
                this.submitting = false;
            }
        },
    }));

    return true;
}

// Try to register immediately if Alpine is already loaded
if (!registerCommentForm()) {
    document.addEventListener('alpine:init', registerCommentForm);
}
