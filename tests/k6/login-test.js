import http from 'k6/http';
import { check, sleep } from 'k6';

export const options = {
  vus: 5, // jumlah virtual user
  duration: '10s', // lama test
};

export default function () {
  const loginUrl = 'http://wellmed:9000/api/login'; // pakai nama service container
  const payload = JSON.stringify({
    username: 'admin',
    password: 'password', // ganti sesuai credential lo
  });

  const params = {
    headers: { 'Content-Type': 'application/json' },
  };

  // === 1️⃣ Request Login ===
  const loginRes = http.post(loginUrl, payload, params);

  // === 2️⃣ Validasi Response ===
  const success = check(loginRes, {
    'status is 200': (r) => r.status === 200,
    'response has token': (r) => {
      try {
        const json = r.json();
        return json?.data?.token !== undefined;
      } catch (_) {
        return false;
      }
    },
  });

  if (!success) {
    console.error('❌ Login failed or token missing');
    return;
  }

  // === 3️⃣ Ambil token ===
  const token = loginRes.json('data.token');
  if (!token) {
    console.error('❌ Token not found in response');
    return;
  }

  // === 4️⃣ Request ke endpoint protected ===
  const authHeaders = {
    headers: {
      Authorization: `Bearer ${token}`,
      'Content-Type': 'application/json',
    },
  };

  const userRes = http.get('http://wellmed:9000/api/me', authHeaders);

  // === 5️⃣ Validasi endpoint protected ===
  check(userRes, {
    'protected endpoint status 200': (r) => r.status === 200,
    'has user data': (r) => {
      try {
        const json = r.json();
        return json?.data?.username === 'admin';
      } catch (_) {
        return false;
      }
    },
  });

  sleep(1);
}
