import jwtEncode from 'jwt-encode';

const SECRET = 'YXYlGIbJ65VGjQnETWXoOiCvqpXg7PJu';

export async function api<T = any>(
  url: string,
  method: 'GET' | 'POST' | 'PUT' | 'DELETE' = 'GET',
  data?: Record<string, any>
): Promise<T> {
  const payload = {
    iat: Math.floor(Date.now() / 1000),
    data: {
      username: 'admin',
      password: 'password',
    },
  };

  const token = jwtEncode(payload, SECRET, 'HS256');

  const headers: HeadersInit = {
    Accept: 'application/json',
    'Content-Type': 'application/json',
    appcode: '2',
    Authorization: `Bearer ${token}`,
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
