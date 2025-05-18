import { api } from '@/composables/useApi';
import { SignJWT } from 'jose';

const SECRET = 'YXYlGIbJ65VGjQnETWXoOiCvqpXg7PJu';

export async function login(payload: {
  username: string;
  password: string;
  remember: boolean;
}) {
  // const iat = Math.floor(Date.now() / 1000);
  // const exp = iat + 60 * 60; // 1 jam

  // const jwt = await new SignJWT({
  //   data: {
  //     username: payload.username,
  //     password: payload.password,
  //   },
  // })
  // .setProtectedHeader({ alg: 'HS256' })
  // .setIssuedAt(iat)
  // .setExpirationTime(exp)
  // .sign(new TextEncoder().encode(SECRET));

  // return api('/api/token', 'POST', {}, {
  //   Authorization: `Bearer ${jwt}`,
  // });

  return api('/api/token', 'POST', payload);
}
