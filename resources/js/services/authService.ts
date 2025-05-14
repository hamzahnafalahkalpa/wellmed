import { api } from '@/composables/useApi';

export function login(payload: {
  username: string;
  password: string;
  remember: boolean;
}) {
  const jw_payload = {
    iat: Math.floor(Date.now() / 1000),
    data: {
      username: payload.username,
      password: payload.password
    },
  };
  return api('/api/token', 'POST', payload);
}
