import '../css/app.css';

// Inertia.js for Vue 3
import { createApp, h } from 'vue';
import { createInertiaApp, Link, Head } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { ZiggyVue } from 'ziggy-js';
import axios from 'axios';

// Configure axios with CSRF token for all requests
const csrfMeta = document.head.querySelector('meta[name="csrf-token"]');
if (csrfMeta) {
    axios.defaults.headers.common['X-CSRF-TOKEN'] = csrfMeta.getAttribute('content');
}
axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
window.axios = axios;

// Alpine.js for lightweight interactivity
import Alpine from 'alpinejs';
window.Alpine = Alpine;
Alpine.start();

// Global helper functions
window.formatCurrency = (value, currency = 'USD') => {
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: currency,
    }).format(value);
};

window.formatDate = (date, options = {}) => {
    const defaultOptions = {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        ...options,
    };
    return new Date(date).toLocaleDateString('en-US', defaultOptions);
};

window.formatDateTime = (date) => {
    return new Date(date).toLocaleString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};

window.truncate = (str, length = 50) => {
    if (!str) return '';
    return str.length > length ? str.substring(0, length) + '...' : str;
};

window.getInitials = (name) => {
    if (!name) return '?';
    return name.split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2);
};

// Toast notification system
window.toast = (message, type = 'success') => {
    const event = new CustomEvent('toast', {
        detail: { message, type }
    });
    window.dispatchEvent(event);
};

// Confirm dialog
window.confirmAction = (message, callback) => {
    const event = new CustomEvent('confirm', {
        detail: { message, callback }
    });
    window.dispatchEvent(event);
};

// Create Inertia App
const appName = import.meta.env.VITE_APP_NAME || 'Outreach CRM';

const pages = import.meta.glob('./Pages/**/*.vue');

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) => {
        const page = `./Pages/${name}.vue`;

        if (pages[page]) {
            return resolvePageComponent(page, pages);
        }

        // Screens without a Vue component fall back to a friendly placeholder
        // instead of crashing the whole page.
        return pages['./Pages/Placeholder.vue']();
    },
    setup({ el, App, props, plugin }) {
        createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(ZiggyVue)
            .component('Link', Link)
            .component('Head', Head)
            .mount(el);
    },
    progress: {
        color: '#4F46E5',
    },
});