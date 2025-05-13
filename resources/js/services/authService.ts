import { api } from '@/composables/useApi';

export function login(payload: {
  email: string;
  password: string;
  remember: boolean;
}) {
  return api('/api/token', 'POST', payload);
}
