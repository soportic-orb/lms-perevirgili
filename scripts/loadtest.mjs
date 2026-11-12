#!/usr/bin/env node
// Prova de càrrega del Campus PSPV (docs/RENDIMENT.md). Només necessita Node 18 o superior.
//
//   node scripts/loadtest.mjs https://campus.example.cat --users 25 --seconds 60
//
// Simula visitants que naveguen per les pàgines públiques (portada, catàleg, inici de
// sessió). Amb LOADTEST_USER i LOADTEST_PASS, cada visitant inicia sessió i també obre el
// tauler i els seus cursos. Fes-la servir contra staging, mai contra producció en hores de feina.
//
// Opcions: --users N (visitants alhora, 10), --seconds N (durada, 30), --think N (pausa
// mitjana entre pàgines en ms, 500), --paths a,b,c (pàgines públiques, separades per comes).

const args = process.argv.slice(2);
const base = (args.find((a) => !a.startsWith('--')) || '').replace(/\/+$/, '');
const option = (name, fallback) => {
    const index = args.indexOf('--' + name);
    return index >= 0 && args[index + 1] !== undefined ? args[index + 1] : fallback;
};
if (!/^https?:\/\//.test(base)) {
    console.error('Ús: node scripts/loadtest.mjs <URL del campus> [--users N] [--seconds N] [--think ms] [--paths a,b]');
    process.exit(1);
}
const users = Number(option('users', 10));
const seconds = Number(option('seconds', 30));
const think = Number(option('think', 500));
const publicPaths = String(option('paths', '/,/local/pspvshop/index.php,/login/index.php')).split(',');
const privatePaths = ['/my/', '/my/courses.php'];
const username = process.env.LOADTEST_USER || '';
const password = process.env.LOADTEST_PASS || '';

const results = new Map();
const record = (path, ms, ok) => {
    const entry = results.get(path) || { times: [], errors: 0 };
    entry.times.push(ms);
    if (!ok) {
        entry.errors++;
    }
    results.set(path, entry);
};
const sleep = (ms) => new Promise((resolve) => setTimeout(resolve, ms));

// Una sessió: recorda les galetes entre peticions, com un navegador.
class Session {
    constructor() {
        this.cookies = new Map();
    }

    async get(path, label = path) {
        return this.request(path, { method: 'GET' }, label);
    }

    async request(path, init, label) {
        const started = performance.now();
        let response = null;
        let body = '';
        try {
            let url = base + path;
            // Les redireccions se segueixen a mà per no perdre les galetes pel camí.
            for (let hop = 0; hop < 6; hop++) {
                response = await fetch(url, {
                    ...init,
                    redirect: 'manual',
                    headers: { ...(init.headers || {}), cookie: [...this.cookies].map(([k, v]) => `${k}=${v}`).join('; ') },
                });
                for (const cookie of response.headers.getSetCookie()) {
                    const [pair] = cookie.split(';');
                    const at = pair.indexOf('=');
                    this.cookies.set(pair.slice(0, at), pair.slice(at + 1));
                }
                const location = response.headers.get('location');
                if (response.status < 300 || response.status >= 400 || !location) {
                    break;
                }
                url = new URL(location, url).toString();
                init = { method: 'GET' };
            }
            body = await response.text();
        } catch (error) {
            record(label, performance.now() - started, false);
            return '';
        }
        record(label, performance.now() - started, response.status < 400);
        return body;
    }

    async login() {
        const page = await this.get('/login/index.php');
        const token = (page.match(/name="logintoken" value="([^"]+)"/) || [])[1] || '';
        const form = new URLSearchParams({ username, password, logintoken: token });
        const landed = await this.request('/login/index.php', {
            method: 'POST',
            headers: { 'content-type': 'application/x-www-form-urlencoded' },
            body: form.toString(),
        }, 'POST /login/index.php');
        return !/id="loginerrormessage"|name="logintoken"/.test(landed);
    }
}

const deadline = Date.now() + seconds * 1000;
let loginFailures = 0;
const visitor = async () => {
    const session = new Session();
    let paths = publicPaths;
    if (username) {
        if (await session.login()) {
            paths = publicPaths.concat(privatePaths);
        } else {
            loginFailures++;
        }
    }
    while (Date.now() < deadline) {
        await session.get(paths[Math.floor(Math.random() * paths.length)]);
        await sleep(think * (0.5 + Math.random()));
    }
};

const percentile = (sorted, p) => sorted[Math.min(sorted.length - 1, Math.floor(sorted.length * p))];
console.log(`Campus: ${base} · ${users} visitants alhora · ${seconds} s · pausa mitjana ${think} ms`);
const started = Date.now();
await Promise.all(Array.from({ length: users }, visitor));
const elapsed = (Date.now() - started) / 1000;

let total = 0;
let errors = 0;
const all = [];
console.log('\nPàgina'.padEnd(34) + 'Peticions'.padStart(10) + 'Errors'.padStart(8) + 'p50 ms'.padStart(9) + 'p95 ms'.padStart(9) + 'màx ms'.padStart(9));
for (const [path, entry] of [...results].sort()) {
    const sorted = entry.times.slice().sort((a, b) => a - b);
    total += sorted.length;
    errors += entry.errors;
    all.push(...sorted);
    console.log(path.padEnd(33) + String(sorted.length).padStart(10) + String(entry.errors).padStart(8)
        + percentile(sorted, 0.5).toFixed(0).padStart(9) + percentile(sorted, 0.95).toFixed(0).padStart(9)
        + sorted[sorted.length - 1].toFixed(0).padStart(9));
}
all.sort((a, b) => a - b);
console.log(`\nTotal: ${total} peticions en ${elapsed.toFixed(0)} s (${(total / elapsed).toFixed(1)} per segon), `
    + `${errors} errors, p50 ${percentile(all, 0.5).toFixed(0)} ms, p95 ${percentile(all, 0.95).toFixed(0)} ms.`);
if (loginFailures) {
    console.log(`Atenció: ${loginFailures} visitants no han pogut iniciar sessió (revisa LOADTEST_USER i LOADTEST_PASS).`);
}
process.exit(errors > total * 0.01 ? 1 : 0);
