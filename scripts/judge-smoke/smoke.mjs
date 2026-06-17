#!/usr/bin/env node
/**
 * VentureLens judge / demo smoke test.
 * Usage: node scripts/judge-smoke/smoke.mjs [--base-url=https://venturelens.app] [--out=report.json]
 */

import { writeFileSync } from 'node:fs';
import { resolve } from 'node:path';

const args = process.argv.slice(2);
let baseUrl = process.env.VENTURELENS_BASE_URL || 'https://venturelens.app';
let outFile = null;

for (const arg of args) {
    if (arg.startsWith('--base-url=')) {
        baseUrl = arg.slice('--base-url='.length).replace(/\/$/, '');
    } else if (arg.startsWith('--out=')) {
        outFile = arg.slice('--out='.length);
    } else if (arg === '--help' || arg === '-h') {
        console.log(`Usage: node smoke.mjs [--base-url=URL] [--out=report.json]

Checks production readiness for judges and demo video recording.
Exit 0 = pass (warnings ok), 1 = failures.`);
        process.exit(0);
    }
}

const results = {
    base_url: baseUrl,
    checked_at: new Date().toISOString(),
    checks: [],
    summary: { passed: 0, warnings: 0, failed: 0 },
};

function record(level, name, message, detail = null) {
    results.checks.push({ level, name, message, detail });
    results.summary[level === 'pass' ? 'passed' : level === 'warn' ? 'warnings' : 'failed']++;
    const tag = level === 'pass' ? 'OK  ' : level === 'warn' ? 'WARN' : 'FAIL';
    console.log(`[${tag}] ${name}: ${message}`);
}

async function fetchText(path, timeoutMs = 30000) {
    const controller = new AbortController();
    const timer = setTimeout(() => controller.abort(), timeoutMs);
    try {
        const res = await fetch(`${baseUrl}${path}`, {
            signal: controller.signal,
            headers: { Accept: 'application/json, text/html, */*' },
        });
        return { res, body: await res.text() };
    } finally {
        clearTimeout(timer);
    }
}

async function main() {
    console.log(`\nVentureLens judge smoke test\nBase: ${baseUrl}\n`);

    try {
        const { res } = await fetchText('/up');
        if (res.status === 200) {
            record('pass', 'health', '/up returned 200');
        } else {
            record('fail', 'health', `/up returned ${res.status}`);
        }
    } catch (e) {
        record('fail', 'health', e.message || String(e));
    }

    let impact = null;
    try {
        const { res, body } = await fetchText('/api/v1/impact.json');
        if (res.status !== 200) {
            record('fail', 'impact_json', `HTTP ${res.status}`);
        } else {
            impact = JSON.parse(body);
            record('pass', 'impact_json', 'Valid JSON from /api/v1/impact.json');
        }
    } catch (e) {
        record('fail', 'impact_json', e.message || String(e));
    }

    if (impact) {
        const required = [
            ['business.arms_length_revenue_usd', impact.business?.arms_length_revenue_usd],
            ['business.arms_length_paying_customers', impact.business?.arms_length_paying_customers],
            ['activity.applications_screened', impact.activity?.applications_screened],
            ['activity.gemini_api_calls', impact.activity?.gemini_api_calls],
            ['ai_operations.pct_decisions_by_ai', impact.ai_operations?.pct_decisions_by_ai],
            ['impact.founder_hours_saved', impact.impact?.founder_hours_saved],
            ['generated_at', impact.generated_at],
        ];

        for (const [key, value] of required) {
            if (value === undefined || value === null) {
                record('fail', 'impact_fields', `Missing ${key}`);
            }
        }

        const screened = Number(impact.activity?.applications_screened ?? 0);
        const gemini = Number(impact.activity?.gemini_api_calls ?? 0);
        const revenue = Number(impact.business?.arms_length_revenue_usd ?? 0);
        const customers = Number(impact.business?.arms_length_paying_customers ?? 0);
        const agents = Number(impact.ai_operations?.total_agent_actions ?? 0);
        const pctAi = Number(impact.ai_operations?.pct_decisions_by_ai ?? 0);
        const accepted = Number(impact.impact?.accepted_startups ?? 0);

        if (screened >= 1) {
            record('pass', 'applications_screened', String(screened));
        } else {
            record('fail', 'applications_screened', '0 — run replay screening before demo');
        }

        if (gemini >= 1) {
            record('pass', 'gemini_api_calls', String(gemini));
        } else {
            record('fail', 'gemini_api_calls', '0 — no Gemini usage logged');
        }

        if (revenue >= 600) {
            record('pass', 'arms_length_revenue', `$${revenue} from ${customers} customer(s)`);
        } else if (revenue > 0) {
            record('warn', 'arms_length_revenue', `$${revenue} — target ≥ $600 for Business Viability`);
        } else {
            record('fail', 'arms_length_revenue', '$0 — fix before demo video (Stripe checkout or verify script)');
        }

        if (agents >= 10) {
            record('pass', 'agent_actions', `${agents} actions, ${pctAi}% at L2–L3`);
        } else {
            record('warn', 'agent_actions', `Only ${agents} agent actions`);
        }

        if (accepted >= 1) {
            record('pass', 'accepted_startups', String(accepted));
        } else {
            record('warn', 'accepted_startups', '0 — optional: accept one application');
        }

        results.metrics = {
            generated_at: impact.generated_at,
            arms_length_revenue_usd: revenue,
            arms_length_paying_customers: customers,
            applications_screened: screened,
            gemini_api_calls: gemini,
            pct_decisions_by_ai: pctAi,
            founder_hours_saved: impact.impact?.founder_hours_saved,
        };
    }

    for (const path of ['/login', '/impact', '/widgets/impact/', '/apply/summer-2026']) {
        try {
            const { res } = await fetchText(path);
            if (res.status === 200) {
                record('pass', `page${path}`, 'loads');
            } else {
                record('warn', `page${path}`, `HTTP ${res.status}`);
            }
        } catch (e) {
            record('warn', `page${path}`, e.message || String(e));
        }
    }

    const { failed, warnings, passed } = results.summary;
    console.log(`\n--- ${passed} passed, ${warnings} warnings, ${failed} failures ---\n`);

    if (outFile) {
        const path = resolve(process.cwd(), outFile);
        writeFileSync(path, JSON.stringify(results, null, 2));
        console.log(`Report written to ${path}\n`);
    }

    process.exit(failed > 0 ? 1 : 0);
}

main().catch((e) => {
    console.error(e);
    process.exit(1);
});
