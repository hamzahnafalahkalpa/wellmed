// utils/route.ts
import { usePage } from '@inertiajs/vue3';

type Tenant = {
  id: number
  name: string
}

type PageProps = {
  tenant?: Tenant
}

export function routeWithTenant(name: string, params: Record<string, any> = {}, absolute = true) {
    const { tenant } = usePage().props as PageProps;
    const routeDef = Ziggy.routes[name];
    if (routeDef?.uri.includes('{tenant}') && tenant?.id) {
        params = { tenant: tenant.id, ...params };
    }

    return route(name, params, absolute);
}
