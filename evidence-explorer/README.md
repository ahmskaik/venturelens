# VentureLens Evidence Explorer

Judge-facing static SPA: **live production KPIs** + **agent execution timeline** with autonomy badges (L0–L3).

## Quick start

```bash
cd evidence-explorer
npm install
npm run dev
```

Open http://localhost:5173/evidence-explorer/

## Build

```bash
npm run build
```

Output: `dist/` (static assets, typically &lt; 200 KB gzip).

### Deploy to main app

```powershell
# From repo root (Windows)
Remove-Item -Recurse -Force public\evidence-explorer -ErrorAction SilentlyContinue
Copy-Item -Recurse evidence-explorer\dist public\evidence-explorer
```

```bash
# Linux / macOS
rm -rf public/evidence-explorer
cp -r evidence-explorer/dist public/evidence-explorer
```

Then redeploy Cloud Run or serve locally via Laravel `public/`.

## Configuration

Copy `.env.example` to `.env` to override API base URL (default: `https://venturelens.app`).

## Verify

```bash
curl -s https://venturelens.app/api/v1/impact.json | head -c 200
```

After deploy:

- https://venturelens.app/evidence-explorer/
- Toggle **Live KPIs** / **Agent explorer** tabs
- Dark/light mode persists in localStorage

## Stack

Vue 3 · Vite · Tailwind CSS · no backend · no auth

See `INTEGRATION.md` for Laravel merge steps.
