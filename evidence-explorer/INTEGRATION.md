# Integration — Evidence Explorer → VentureLens

## What this is

A static Vue 3 SPA that fetches live `GET /api/v1/impact.json` and displays production KPIs plus a merged timeline of agent executions (bundled sample + live `recent_agent_executions`). Built for **Build with Gemini XPRIZE** judge transparency (AI-Native Operations + Category Impact).

## Files to copy

After `npm run build` in `evidence-explorer/`:

| Source | Target in main repo |
|--------|---------------------|
| `evidence-explorer/dist/*` | `public/evidence-explorer/` |

No Laravel PHP changes required. Optional: link from `/impact` nav (already added in `Impact/Index.vue`).

## Build & copy (one command block)

```powershell
cd evidence-explorer
npm ci
npm run build
cd ..
Remove-Item -Recurse -Force public\evidence-explorer -ErrorAction SilentlyContinue
Copy-Item -Recurse evidence-explorer\dist public\evidence-explorer
```

## Vite base path

`vite.config.js` sets `base: '/evidence-explorer/'` so assets resolve under `https://venturelens.app/evidence-explorer/`.

## Env vars

None required on Cloud Run. Optional at build time:

- `VITE_API_BASE_URL=https://venturelens.app`

## GCP resources

None — static files served from Laravel `public/` on existing Cloud Run service.

## Deploy order

1. Build + copy to `public/evidence-explorer/`
2. Commit `public/evidence-explorer/` (or build in CI before Docker image)
3. `.\scripts\deploy-cloud-run.ps1 deploy` (or `build` + `web` if only static files changed)

## Judge verification

1. Open https://venturelens.app/evidence-explorer/
2. **Live KPIs** tab — numbers match https://venturelens.app/api/v1/impact.json
3. **Agent explorer** — filter by agent / autonomy level; timeline shows L0–L3 badges
4. Toggle dark mode — reload persists preference
