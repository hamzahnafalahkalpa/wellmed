import { api } from '@/composables/useApi';

export async function menu(payload?: {
}) {
  return api('/api/menu', 'GET', payload);
}
