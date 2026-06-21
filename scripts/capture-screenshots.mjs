#!/usr/bin/env node
/**
 * Capture marketing / judge screenshots for VentureLens.
 *
 * Usage:
 *   npx playwright install chromium   # first time only
 *   node scripts/capture-screenshots.mjs
 *   node scripts/capture-screenshots.mjs --base-url=http://127.0.0.1:8000 --application-id=66
 */

import { mkdirSync, copyFileSync } from 'node:fs';
import { dirname, resolve } from 'node:path';
import { fileURLToPath } from 'node:url';
import { chromium } from 'playwright';

const __dirname = dirname(fileURLToPath(import.meta.url));
const root = resolve(__dirname, '..');

const args = process.argv.slice(2);
let baseUrl = process.env.VENTURELENS_BASE_URL || 'http://127.0.0.1:8000';
let applicationId = process.env.SCREENSHOT_APPLICATION_ID || null;

for (const arg of args) {
    if (arg.startsWith('--base-url=')) {
        baseUrl = arg.slice('--base-url='.length).replace(/\/$/, '');
    } else if (arg.startsWith('--application-id=')) {
        applicationId = arg.slice('--application-id='.length);
    } else if (arg === '--help' || arg === '-h') {
        console.log(`Usage: node scripts/capture-screenshots.mjs [--base-url=URL] [--application-id=ID]`);
        process.exit(0);
    }
}

const email = process.env.DEMO_USER_EMAIL || 'demo@venturelens.app';
const password = process.env.DEMO_USER_PASSWORD || 'demo-password-change-me';

const VIEWPORT = { width: 1440, height: 900 };

function buildShots(appId) {
    return [
        {
            name: 'application-screening',
            path: `/applications/${appId}`,
            auth: true,
            waitFor: 'text=Replay screening',
        },
        {
            name: 'ai-operations-dashboard',
            path: '/ai-operations',
            auth: true,
            waitFor: 'text=Agent fleet',
        },
        {
            name: 'impact-page',
            path: '/impact',
            auth: false,
            waitFor: 'text=Category impact',
        },
        {
            name: 'billing-split',
            path: '/billing',
            auth: true,
            waitFor: 'text=Arms-length',
        },
    ];
}

async function login(page) {
    await page.goto(`${baseUrl}/login`, { waitUntil: 'networkidle' });
    await page.locator('input[type="email"]').fill(email);
    await page.locator('input[type="password"]').fill(password);
    await page.locator('button[type="submit"]').click();

    await page.waitForURL((url) => /\/(dashboard|applications|programs)/.test(url.pathname), { timeout: 25000 });
    await page.waitForLoadState('networkidle');
}

async function resolveApplicationId(page) {
    if (applicationId) {
        return applicationId;
    }

    await page.goto(`${baseUrl}/applications`, { waitUntil: 'networkidle' });
    const href = await page.locator('a[href*="/applications/"]').first().getAttribute('href').catch(() => null);
    const match = href?.match(/\/applications\/(\d+)/);
    return match?.[1] ?? '66';
}

async function capture(page, shot, destDir) {
    const fullUrl = `${baseUrl}${shot.path}`;
    console.log(`Capturing ${shot.name} → ${fullUrl}`);

    await page.goto(fullUrl, { waitUntil: 'networkidle' });

    await page.locator(shot.waitFor).first().waitFor({ timeout: 25000 }).catch(() => {
        console.warn(`  Warning: "${shot.waitFor}" not found — capturing viewport anyway`);
    });

    // Let charts / fonts settle
    await page.waitForTimeout(800);

    const filePath = resolve(destDir, `${shot.name}.png`);
    await page.screenshot({ path: filePath, fullPage: false });
    console.log(`  Saved ${filePath}`);
    return filePath;
}

async function main() {
    const publicDir = resolve(root, 'public/images/screenshots');
    const evidenceDir = resolve(root, 'docs/evidence');
    mkdirSync(publicDir, { recursive: true });
    mkdirSync(evidenceDir, { recursive: true });

    const browser = await chromium.launch({ headless: true });
    const context = await browser.newContext({
        viewport: VIEWPORT,
        deviceScaleFactor: 2,
        colorScheme: 'light',
    });
    const page = await context.newPage();

    await login(page);
    applicationId = await resolveApplicationId(page);
    console.log(`Using application ID: ${applicationId}\n`);

    const shots = buildShots(applicationId);

    for (const shot of shots) {
        if (!shot.auth) {
            // Impact is public — use a fresh page context without requiring re-login
        }
        const filePath = await capture(page, shot, publicDir);
        copyFileSync(filePath, resolve(evidenceDir, `${shot.name}.png`));
    }

    await browser.close();
    console.log('\nDone — screenshots saved to:');
    console.log('  public/images/screenshots/');
    console.log('  docs/evidence/');
}

main().catch((err) => {
    console.error(err);
    process.exit(1);
});
