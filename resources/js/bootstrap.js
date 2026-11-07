/**
 * We'll load the axios HTTP library which allows us to easily issue requests
 * to our Laravel back-end. This library automatically handles sending the
 * CSRF token as a header based on the value of the "XSRF" token cookie.
 */

// Global event bus and client helpers for LocalDateHub
window.LocalDateHub = {
    csrfToken: document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
};
