import http from 'k6/http';
import { check, sleep } from 'k6';

// Konfigurasi dasar load test
export const options = {
  vus: 10,         // jumlah virtual users (VU)
  duration: '10s', // durasi test
};

// Fungsi utama
export default function () {
  // Ganti dengan endpoint API lo, misal: /api/health atau /api/status
  const res = http.get('lite.test:9000/api/health');

  // Validasi response
  check(res, {
    'status is 200': (r) => r.status === 200,
    'response time < 500ms': (r) => r.timings.duration < 500,
  });

  sleep(1); // delay antar request (simulasi user nyata)
}
