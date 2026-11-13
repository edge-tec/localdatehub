import http from 'k6/http';
import { check, sleep } from 'k6';

/**
 * LocalDateHub Staged Load Test Suite (Staging ONLY)
 *
 * Profiles:
 *   STAGE=smoke  -> 10 VUs (1m)
 *   STAGE=100    -> Stage 1: 100 VUs
 *   STAGE=500    -> Stage 2: 500 VUs
 *   STAGE=1000   -> Stage 3: 1,000 VUs
 *   STAGE=5000   -> Stage 4: 5,000 VUs
 *   STAGE=10000  -> Stage 5: 10,000 VUs
 *   STAGE=full   -> Progressive ramp 100 -> 500 -> 1,000 -> 5,000 -> 10,000 VUs
 *
 * Example:
 *   BASE_URL=https://staging.localdatehub.com STAGE=100 k6 run tests/load/k6-million-traffic.js
 */
const stageProfiles = {
  smoke: [
    { duration: '30s', target: 10 },
    { duration: '30s', target: 0 },
  ],
  '100': [
    { duration: '1m', target: 100 },
    { duration: '3m', target: 100 },
    { duration: '30s', target: 0 },
  ],
  '500': [
    { duration: '1m', target: 500 },
    { duration: '3m', target: 500 },
    { duration: '1m', target: 0 },
  ],
  '1000': [
    { duration: '2m', target: 1000 },
    { duration: '5m', target: 1000 },
    { duration: '1m', target: 0 },
  ],
  '5000': [
    { duration: '2m', target: 5000 },
    { duration: '5m', target: 5000 },
    { duration: '2m', target: 0 },
  ],
  '10000': [
    { duration: '3m', target: 10000 },
    { duration: '5m', target: 10000 },
    { duration: '2m', target: 0 },
  ],
  full: [
    { duration: '2m', target: 100 },
    { duration: '3m', target: 500 },
    { duration: '5m', target: 1000 },
    { duration: '5m', target: 5000 },
    { duration: '5m', target: 10000 },
    { duration: '2m', target: 0 },
  ],
};

const selectedStage = __ENV.STAGE || '100';

export const options = {
  stages: stageProfiles[selectedStage] || stageProfiles['100'],
  thresholds: {
    http_req_failed: ['rate<0.01'],
    http_req_duration: ['p(50)<200', 'p(90)<400', 'p(95)<500', 'p(99)<1200'],
  },
};

const BASE_URL = __ENV.BASE_URL || 'http://127.0.0.1:8000';

// Weighted realistic traffic distribution (read-heavy public discovery + contact + API)
const weightedRoutes = [
  '/',
  '/',
  '/',
  '/categories',
  '/categories',
  '/locations',
  '/locations',
  '/search',
  '/search',
  '/about-us',
  '/safety',
  '/contact-us',
  '/pricing',
];

export default function () {
  const path = weightedRoutes[Math.floor(Math.random() * weightedRoutes.length)];
  const res = http.get(`${BASE_URL}${path}`, {
    headers: {
      'Accept': 'text/html,application/xhtml+xml',
    },
    tags: { name: path },
  });

  check(res, {
    'status is 200 or 302': (r) => r.status === 200 || r.status === 302,
    'latency < 1000ms': (r) => r.timings.duration < 1000,
  });

  sleep(Math.random() * 2 + 1);
}
