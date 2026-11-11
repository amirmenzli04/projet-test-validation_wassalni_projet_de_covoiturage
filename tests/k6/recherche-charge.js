/**
 * Semaine 9 — Test de charge léger (k6) sur les parcours en lecture : recherche filtrée, détail d'un trajet,
 * page de connexion et /health. Aucune écriture en base.
 *
 * Seuils (hypothèses de travail, à valider par le produit) : taux d'échec < 1 %, p95 < 1500 ms.
 * Limite : le serveur intégré « php -S » sous Windows traite UNE requête à la fois ; les utilisateurs
 * virtuels sont donc mis en file d'attente. Les mesures caractérisent cet environnement de test, pas la production.
 */
import http from 'k6/http';
import { check, sleep } from 'k6';

const BASE = __ENV.BASE_URL || 'http://127.0.0.1:8000';
const TRIP = __ENV.TRIP_ID;

export const options = {
    scenarios: {
        recherche: { executor: 'constant-vus', vus: 5, duration: '30s' },
    },
    thresholds: {
        http_req_failed: ['rate<0.01'],
        http_req_duration: ['p(95)<1500'],
        'http_req_duration{page:recherche}': ['p(95)<1500'],
        checks: ['rate>0.99'],
    },
    summaryTrendStats: ['avg', 'min', 'med', 'p(90)', 'p(95)', 'max'],
};

export default function () {
    const search = http.get(`${BASE}/trajet/recherche?villeDepart=Tunis&destination=Sfax&dateDepart=2099-12-01`, { tags: { page: 'recherche' } });
    check(search, {
        'recherche : 200': r => r.status === 200,
        'recherche : trajet de fixture présent': r => r.body.includes(`data-trajet-id="${TRIP}"`),
    });
    const detail = http.get(`${BASE}/trajet/${TRIP}`, { tags: { page: 'detail' } });
    check(detail, { 'détail : 200': r => r.status === 200 });
    const login = http.get(`${BASE}/connexion`, { tags: { page: 'connexion' } });
    check(login, { 'connexion : 200': r => r.status === 200 });
    const health = http.get(`${BASE}/health`, { tags: { page: 'health' } });
    check(health, { 'health : ok': r => r.status === 200 && r.json('status') === 'ok' });
    sleep(1);
}

export function handleSummary(data) {
    const m = data.metrics;
    const pick = (name) => (m[name] ? m[name].values : {});
    const lines = [
        `Scénario : 5 utilisateurs virtuels pendant 30 s (pause 1 s par itération), 4 requêtes par itération`,
        `Requêtes : ${pick('http_reqs').count} ; débit ${Number(pick('http_reqs').rate).toFixed(2)} req/s ; itérations ${pick('iterations').count}`,
        `Échecs HTTP : ${(100 * (pick('http_req_failed').rate || 0)).toFixed(2)} % ; vérifications réussies : ${(100 * (pick('checks').rate || 0)).toFixed(2)} %`,
        `Durée (toutes pages) : moy ${Number(pick('http_req_duration').avg).toFixed(1)} ms ; médiane ${Number(pick('http_req_duration').med).toFixed(1)} ms ; p95 ${Number(pick('http_req_duration')['p(95)']).toFixed(1)} ms ; max ${Number(pick('http_req_duration').max).toFixed(1)} ms`,
        `Durée recherche : p95 ${Number(pick('http_req_duration{page:recherche}')['p(95)']).toFixed(1)} ms`,
        `Seuils : ${Object.entries(m).filter(([, v]) => v.thresholds).map(([k, v]) => `${k} ${Object.values(v.thresholds).every(t => t.ok) ? 'RESPECTÉ' : 'DÉPASSÉ'}`).join(' ; ')}`,
    ];
    return {
        stdout: lines.join('\n') + '\n',
        [__ENV.SUMMARY_JSON || 'k6-resume.json']: JSON.stringify(data, null, 2),
    };
}
