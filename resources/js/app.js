import '../css/app.css';

// Inertia.js for Vue 3
import { createApp, h } from 'vue';
import { createInertiaApp, Link, Head } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { ZiggyVue } from 'ziggy-js';
import axios from 'axios';
import AppLogo from './Components/AppLogo.vue';

// ─── Axios CSRF Setup ────────────────────────────────────────────────────────

axios.defaults.withCredentials = true;
axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
axios.defaults.headers.common['Accept'] = 'application/json';

/**
 * Read a cookie by name, URL-decoding its value.
 * Laravel URL-encodes the XSRF-TOKEN cookie value; we must decode it before
 * sending it as the X-XSRF-TOKEN header or the server-side comparison fails.
 */
function getCookie(name) {
    const cookies = document.cookie.split('; ');
    const found = cookies.find(c => c.startsWith(name + '='));
    return found ? decodeURIComponent(found.split('=').slice(1).join('=')) : null;
}

// Attach a fresh, decoded XSRF token to every outgoing axios request.
axios.interceptors.request.use(function (config) {
    const token = getCookie('XSRF-TOKEN');
    if (token) {
        config.headers['X-XSRF-TOKEN'] = token;
    }
    return config;
});

// On 419 Page Expired, silently refresh the CSRF cookie and retry once.
axios.interceptors.response.use(
    function (response) { return response; },
    async function (error) {
        if (error.response && error.response.status === 419 && !error.config._csrfRetry) {
            error.config._csrfRetry = true;
            await axios.get('/csrf-cookie').catch(() => {});
            const newToken = getCookie('XSRF-TOKEN');
            if (newToken) {
                error.config.headers['X-XSRF-TOKEN'] = newToken;
            }
            return axios(error.config);
        }
        return Promise.reject(error);
    }
);

window.axios = axios;

// ─── Alpine.js ───────────────────────────────────────────────────────────────

import Alpine from 'alpinejs';
window.Alpine = Alpine;
Alpine.start();

// ─── Global helpers ──────────────────────────────────────────────────────────

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

window.toast = (message, type = 'success') => {
    const event = new CustomEvent('toast', { detail: { message, type } });
    window.dispatchEvent(event);
};

window.confirmAction = (message, callback) => {
    const event = new CustomEvent('confirm', { detail: { message, callback } });
    window.dispatchEvent(event);
};

// ─── Inertia App ─────────────────────────────────────────────────────────────

const appName = import.meta.env.VITE_APP_NAME || 'Outreach CRM';
const pages = import.meta.glob('./Pages/**/*.vue');

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) => {
        const page = `./Pages/${name}.vue`;
        if (pages[page]) {
            return resolvePageComponent(page, pages);
        }
        return pages['./Pages/Placeholder.vue']();
    },
    setup({ el, App, props, plugin }) {
        createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(ZiggyVue)
            .component('Link', Link)
            .component('Head', Head)
            .component('AppLogo', AppLogo)
            .mount(el);
    },
    progress: {
        color: '#2563eb',
    },
});
