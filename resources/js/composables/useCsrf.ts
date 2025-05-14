import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

export function useCsrf() {
    return computed(() => usePage().props.csrf_token as string);
}
