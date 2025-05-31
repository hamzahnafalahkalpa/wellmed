import type { route as routeFn } from 'ziggy-js';
import type { routeWithTenant as routeWithTenantFn } from '@/utils/route';
import { ComponentCustomProperties } from 'vue';

declare global {
    const route: typeof routeFn;
    const routeWithTenant: typeof routeWithTenantFn;
    interface Window {
        routeWithTenant: typeof routeWithTenantFn;
    }
}

declare module '@vue/runtime-core' {
    interface ComponentCustomProperties {
        routeWithTenant: typeof routeWithTenantFn;
    }
}