import { useCsrf } from './useCsrf';
import { ref } from 'vue';
import { usePage } from '@inertiajs/vue3';

export async function api<T = any>(
  url: string,
  method: 'GET' | 'POST' | 'PUT' | 'DELETE' = 'GET',
  data?: Record<string, any>,
  customHeaders: Record<string, string> = {}
): Promise<T> {
  const loading = ref(false);
  const csrfToken = useCsrf();
  const page = usePage();

  // Ambil token dari Inertia props dulu, fallback ke localStorage
  const token: string | null = page?.props?.auth?.session?.token || localStorage.getItem('auth_token');
  console.log(token);

  
  const headers: HeadersInit = {
    Accept: 'application/json',
    'Content-Type': 'application/json',
    'X-CSRF-TOKEN': csrfToken.value,
    appcode: '2',
    ...customHeaders,
    ...(token ? { Authorization: `Bearer ${token}` } : {}),
  };

  const response = await fetch(url, {
    method,
    headers,
    body: method !== 'GET' ? JSON.stringify(data) : undefined,
  });

  const result = await response.json();

  const { meta, data: responseData } = result;

  if (!meta.success) {
    if (meta.code == 201) {
      window.location.href = '/login';
      return Promise.reject({
        code: 201,
        messages: ['Session expired. Redirecting to login...'],
      });
    }

    console.error('API Error:', meta.messages);
    throw {
      code: meta.code,
      messages: meta.messages,
    };
  }

  return responseData;
}
