import jwtEncode from 'jwt-encode';
import { useCsrf } from './useCsrf';
import { ref } from 'vue';

const SECRET = 'YXYlGIbJ65VGjQnETWXoOiCvqpXg7PJu';

export async function api<T = any>(
  url: string,
  method: 'GET' | 'POST' | 'PUT' | 'DELETE' = 'GET',
  data?: Record<string, any>
): Promise<T> {
  const loading = ref(false);
  const csrfToken = useCsrf();


  const token = jwtEncode(payload, SECRET, 'HS256');

  const headers: HeadersInit = {
    Accept: 'application/json',
    'Content-Type': 'application/json',
    'X-CSRF-TOKEN': csrfToken.value,
    appcode: '2',
    Authorization: `Bearer ${token}`,

  };

  const handleError = async (response: Response) => {
      const errorData = await response.json().catch(() => null);
      const message = errorData?.meta?.message || response.statusText || 'An unexpected error occurred';
      console.error('API Error:', message);
  };


  const response = await fetch(url, {
    method,
    headers,
    body: method !== 'GET' ? JSON.stringify(data) : undefined,
  });

  const result = await response.json();

  const { meta, data: responseData } = result;

if (!meta.success) {
    if (meta.code === 201) {
      // Langsung redirect ke login
      window.location.href = '/login';
      return Promise.reject({
        code: 201,
        messages: ['Session expired. Redirecting to login...'],
      });
    }

    // Lempar error lainnya
    throw {
      code: meta.code,
      messages: meta.messages,
    };
  }

  return responseData;
}
