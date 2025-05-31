import '../css/app.css';

import { createInertiaApp } from '@inertiajs/vue3';
import { createHead } from '@vueuse/head';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
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
// import '@/assets/css/app.css';

// Extend ImportMeta interface for Vite...
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

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

const pages = {
    ...import.meta.glob<DefineComponent>('./pages/**/*.vue'),
    // ...import.meta.glob<DefineComponent>('@projects/**/src/Resources/js/pages/**/*.vue'),
    // ...import.meta.glob<DefineComponent>('@groups/**/src/Resources/js/pages/**/*.vue'),
    // ...import.meta.glob<DefineComponent>('@tenants/**/src/Resources/js/pages/**/*.vue'),
};

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
        // resolve: (name) => {
        //     const match = Object.entries(pages).find(([key]) =>
        //         key.endsWith(`${name}.vue`)
        //     );
    
        //     if (!match) {
        //         throw new Error(`Page not found: ${name}`);
        //     }
    
        //     return match[1]();
        // },
    resolve: (name) => resolvePageComponent(`./pages/${name}.vue`, import.meta.glob<DefineComponent>('./pages/**/*.vue')),
    setup({ el, App, props, plugin }) {
        const pinia = createPinia();
        const head = createHead();

        createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(ZiggyVue)
            .use(pinia)
            .use(head)
            .use(TippyPlugin)
            .component('Popper', Popper)
            .component('QuillEditor', QuillEditor)
            .component('VueDatePicker', VueDatePicker)
            .mount(el);
    },
    progress: {
        color: '#4B5563',
    },
});

// This will set light / dark mode on page load...
initializeTheme();
