import '../css/app.css';

import { createInertiaApp } from '@inertiajs/vue3';
import { createHead } from '@vueuse/head';
import type { DefineComponent } from 'vue';
import { createApp, h } from 'vue';
import { ZiggyVue } from 'ziggy-js';
import { initializeTheme } from './composables/useAppearance';
import { createPinia } from 'pinia';
import { TippyPlugin } from 'tippy.vue';
import Popper from 'vue3-popper';
import { QuillEditor } from '@vueup/vue-quill';
import '@vueup/vue-quill/dist/vue-quill.snow.css';
import VueDatePicker from '@vuepic/vue-datepicker';
import '@vuepic/vue-datepicker/dist/main.css'
import { routeWithTenant } from '@/utils/routeWithTenant';

import { globMap } from './globMap';
import { loadComponent } from '@/utils/loadComponent';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

declare module 'vite/client' {
    interface ImportMetaEnv {
        readonly VITE_APP_NAME: string;
        [key: string]: string | boolean | undefined;
    }

    interface ImportMeta {
        readonly env: ImportMetaEnv;
        readonly glob: <T>(pattern: string) => Record<string, () => Promise<T>>;
    }
}

function loadPagesByHost(hostAndPath: string, host: string): Record<string, () => Promise<DefineComponent>> {
  return {
    ...(globMap[hostAndPath] ?? {}),
    ...(globMap[host] ?? {}),
    ...import.meta.glob<DefineComponent>('./pages/**/*.vue'),
  };
}

const host = window.location.host;
const path = window.location.pathname.split('/')[1];
const hostAndPath = path ? `${host}/${path}` : host;

const pages = loadPagesByHost(hostAndPath,host);

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) => {
        console.log(pages);
        const match = Object.entries(pages).find(([key]) => key.includes(`${name}.vue`));
        if (!match) {
            throw new Error(`Page not found: ${name}`);
        }
        return loadComponent(match[1]); // <- penting!
    },
    setup({ el, App, props, plugin }) {
        const pinia = createPinia();
        const head  = createHead();

        const app = createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(ZiggyVue)
            .use(pinia)
            .use(head)
            .use(TippyPlugin)
            .component('Popper', Popper)
            .component('QuillEditor', QuillEditor)
            .component('VueDatePicker', VueDatePicker);

        // Daftarkan routeWithTenant sebagai global property di Vue app
        app.config.globalProperties.routeWithTenant = routeWithTenant;

        // Mount app
        app.mount(el);

        window.routeWithTenant = routeWithTenant;
    },
    progress: {
        color: '#4B5563',
    },
});

// This will set light / dark mode on page load...
initializeTheme();
