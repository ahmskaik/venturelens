# Demo video script & Devpost copy

**Target length:** 2:50 (hard cap **3:00**).  
**Format:** 1920×1080, browser zoom 100%, hide bookmarks bar, cursor highlights only.

**Pre-flight script:** `.\scripts\preflight-demo-video.ps1` (run before every take).

**Record on production** (not localhost):

| | |
|---|---|
| **App** | https://venturelens.app |
| **Demo login** | `demo@venturelens.app` / `demo123` |
| **Impact** | `/impact` |
| **Impact JSON** | `/api/v1/impact.json` |
| **AI Operations** | `/ai-operations` |
| **Ask (RAG)** | `/ask` |

**Two narration options — pick one:**

1. **AI voiceover (recommended, ElevenLabs)** — record the screen silently, generate narration separately, sync in edit. See [ElevenLabs voiceover script](#elevenlabs-voiceover-script-ai-narration) + [AI-narrated recording flow](#ai-narrated-recording-flow).
2. **Live read** — use the [live-read teleprompter](#live-read-teleprompter-250) further down and talk over screen capture in one take.

---

## Production status (checked 2026-07-07, live `/api/v1/impact.json`)

| Check | Status |
|-------|--------|
| `/up` health | ✅ 200 |
| Applications screened | ✅ 165 |
| Gemini API calls | ✅ 175 (369,071 tokens) |
| Agent actions / % AI | ✅ 34,579 / 99.9% |
| Founder hours saved | ✅ 123.8 |
| **Arms-length revenue** | ✅ **$2,887** from **13** customers — **$0 related-party** |
| Accepted startups | ✅ 2 (6 jobs influenced, modeled) |
| Countries reached / programs enabled | ✅ 14 / 13 |
| Testimonial public URL | 🔴 still `null` — do **not** claim a public testimonial link in the video or Devpost until fixed |

All scorecard floors are cleared with margin — record now. Snapshot saved to [`docs/evidence/impact-20260707.json`](../evidence/impact-20260707.json).

---

## Intro logo card (Scene 1) — build in Canva, free

Scene 1 ("Title / logo or homepage") works best as a designed title card rather than a raw screen recording. Use the logo assets already in the repo — `public/images/venturelens-logo-light.png` (for dark backgrounds) or `public/images/venturelens-logo.png` (for light backgrounds).

1. **canva.com** → sign in (free account, no watermark on video export) → **Create a design → Custom size → 1920 × 1080 px**.
2. Background: solid dark indigo/navy (e.g. `#0F172A` or `#1E1B4B`) — a dark card makes the logo pop on video better than white. Use `venturelens-logo-light.png` on this background.
3. **Uploads** panel → drag in `venturelens-logo-light.png` from your file explorer → place it centered, roughly 40–50% of frame width.
4. Select the logo → **Animate** (toolbar) → pick a subtle entrance: *Fade*, *Rise*, or *Pan* (avoid *Bounce*/spin styles — too playful for a B2B pitch).
5. Add two lines of text below the logo, staggered to animate in ~0.5–1s after the logo (so it doesn't all pop at once):
   - `AI-operated startup screening for incubators`
   - `Built for the Build with Gemini XPRIZE — Entrepreneurship & Job Creation`
6. Set the page duration to **~8 seconds** (timing control at the bottom of the editor) — this covers just the "VentureLens — the AI-operated company..." line from Block A; the "This is live on Google Cloud" line still needs a real screen recording of the URL bar (see below).
7. Preview, adjust, then **Share → Download → File type: MP4 Video** → download.
8. In your editor (DaVinci Resolve), drop this MP4 as the **first ~8s of the Block A video track**, then cut to a live screen recording of the production URL bar for the remaining ~4s so it lines up with "This is live, in production, on Google Cloud..." in the Block A audio.

---

## Before you record (15 min)

1. Run `.\scripts\preflight-demo-video.ps1` — all green (no fixes needed as of 2026-07-07).
2. **Log in** on production with demo credentials.
3. **Applications** → open a well-scored app (`screened` status) → run **Replay screening** once *off-camera* to confirm the worker is healthy and note how long it takes (~15s) → leave that same app ready for the on-camera take.
4. Open **`/impact`** — confirm live numbers match (or exceed) the reference table below; update the script/overlays if they've moved a lot.
5. Pre-open tabs (left to right): **Applications** → **AI Operations** → **Impact** → **Billing** → **Ask**.
6. Close unrelated tabs; disable notifications; Do Not Disturb on; mute mic if recording AI-narrated (audio comes from ElevenLabs, not your voice).

### Reference KPIs (live 2026-07-07 — refresh from `/impact` right before your final take)

| Metric | Production (live, 2026-07-07) | Committed snapshot (backup) |
|--------|-------------------------------|------------------------------|
| Arms-length revenue | **$2,887** ✅ | `impact-20260707.json` |
| Arms-length customers | **13** ✅ | 13 |
| Related-party revenue | **$0** ✅ | 0 |
| Applications screened | **165** ✅ | 165 |
| Gemini API calls | **175** (369,071 tokens) | 175 |
| Founder hours saved | **123.8** | 123.8 |
| Accepted startups | **2** | 2 |
| Jobs influenced (modeled) | **6** | 6 |
| % decisions by AI (L2–L3) | **99.9%** | 99.9% |
| Total agent actions | **34,579** | 34,579 |
| Countries reached | **14** | 14 |
| Agents live | **6** | 6 |

**Do not say** "verified public testimonial" — the seeded Sarah Chen quote still has `url: null`. If asked or shown, call it a "pilot program quote," not a verified public link.

---

## ElevenLabs voiceover script (AI narration)

**Important — pick one lane, don't mix:**

| | Eleven Multilingual v2 (or Turbo v2.5 / Flash v2.5) | Eleven v3 |
|---|---|---|
| Pause control | Real SSML `<break time="1.0s" />` tags — works reliably, up to 3s per tag | **Ignores `<break>` tags silently.** Uses its own bracket tags instead: `[pause]`, `[short pause]`, `[long pause]`, plus emotion tags like `[confidently]`, `[excited]` |
| Consistency | More stable, very lifelike, best pick for a business-pitch narrator | More expressive/emotional, slightly more run-to-run variance |
| Recommended for this video | ✅ **Yes — use this** (matches the `<break>` workflow below) | Only if you want the bracket-tag emotion version (kept below as an alternative) |

**This doc's primary recommendation: Eleven Multilingual v2 + `<break>` tags.** Do not paste `[bracketed]` emotion tags into v2 — it will read them aloud as literal words (e.g. "bracket confidently bracket").

**Suggested voice:** any confident, professional narrator voice from the ElevenLabs Voice Library (a voice tagged "Narration" / "Professional" / "Confident" — preview 2–3 and pick the one that doesn't sound generic-AI-demo). Avoid overly bright/energetic "ad read" voices — this is a B2B SaaS pitch, not a toy.

**Suggested settings:**

| Setting | Value | Why |
|---|---|---|
| Model | **Eleven Multilingual v2** | `<break>` tag support + most lifelike/consistent narration |
| Stability | 45–55% | Natural variance without instability/artifacts |
| Similarity | 80% | Stays close to the source voice |
| Style exaggeration | 10–20% | Subtle emphasis, not overacted |
| Speaker boost | On | Cleaner output for compressed video audio |
| Speed | 0.9–1.0× | Slightly slower reads numbers/stats more clearly |
| Export | WAV or 192kbps MP3, 44.1kHz | Clean source for editing |

**Don't paste the whole markdown file into ElevenLabs.** Copy only the plain narration text below (no headers, no table pipes, no links) into the ElevenLabs Text to Speech text box.

**Workflow — 3 generations, not 10:** group scenes into 3 blocks so you get natural `<break>` pauses between paragraphs without doing 10 separate renders. Scene 3 (live Replay screening) stays its own block because its on-screen duration is unpredictable and you may need to regenerate/re-time it independently.

| Block | Scenes | Time budget | On-screen action |
|---|---|---|---|
| **A** | 1–2 | ~0:00–0:22 | Title/logo → production URL bar |
| **B** | 3 (standalone) | ~0:22–0:55 | Applications → open scored app → Replay screening → result reveal |
| **C** | 4–10 | ~0:55–2:50 | AI Operations → Impact → Billing → Ask → Decision/email → Apply form → end card |

**Block A — paste into ElevenLabs (Multilingual v2):**

```
VentureLens — the AI-operated company that screens startup applications for incubators. Built for the Build with Gemini XPRIZE, Entrepreneurship and Job Creation category.
<break time="1.0s" />
This is live, in production, on Google Cloud — Cloud Run, Cloud SQL, and Cloud Storage. Not a localhost demo, not a slide deck.
```

**Block B — paste into ElevenLabs (Multilingual v2):**

```
Every application is screened by Google Gemini before a human ever opens it. Watch — I'll replay a live screening call right now.
<break time="1.5s" />
Structured scores, strengths, risk flags, and a committee-ready summary — generated in seconds, not weeks. Programs can run fully automated screening, or keep a human in the loop on every single decision.
```

*Note: the real Gemini replay takes ~15s — the `<break>` here is only ~1.5s (spoken pacing), not the real wait. In editing, hold on the spinner for the actual ~15s and cut to the result reveal so it lands under "Structured scores..." — see the recording flow below.*

**Block C — paste into ElevenLabs (Multilingual v2):**

```
VentureLens isn't a thin AI wrapper bolted onto a SaaS app. Six Gemini agents actually run this company — growth, onboarding, support, finance, screening, and success. As of this morning: thirty-four thousand, five hundred seventy-nine logged agent actions. Ninety-nine point nine percent of them decided by AI, at full autonomy — every one logged, in production, for judges to verify.
<break time="1.2s" />
Everything here is computed from production data, not projections. One hundred sixty-five applications screened across fourteen countries. One hundred twenty-three founder-hours saved. Two startups accepted. And two thousand, eight hundred eighty-seven dollars in arms-length revenue from thirteen paying customers — real customers, real Stripe charges, zero related-party.
<break time="1.2s" />
Programs pay through Stripe — a one-time cohort package, or a monthly subscription. Our Finance agent classifies every single charge the moment it lands.
<break time="1.2s" />
Support runs on Gemini too. Watch — I'll ask: how many applications have we screened?
<break time="1.0s" />
Answered instantly, straight from our own indexed data.
<break time="1.2s" />
Committee decisions always stay with a human. Gemini drafts the evidence and the founder email — a person approves before anything is sent.
<break time="1.2s" />
Founders apply right here, in public. Pitch decks land in Cloud Storage and feed straight into Gemini screening.
<break time="1.5s" />
VentureLens makes fair, fast startup selection accessible to every incubator — and it earns real revenue doing it. Every number you just saw is public and verifiable at venturelens dot app, slash impact. Try it yourself — demo credentials are on screen now. Thank you.
```

*Tip: if the model sounds unstable or speeds up anywhere, that's ElevenLabs' documented `<break>`-overuse artifact — reduce to 2–3 breaks per generation by splitting Block C further, or shorten some `<break>` values to 0.8s.*

**Regenerate 2–3× per block and keep the best take.** Export each as `voiceover-block-A.mp3`, `voiceover-block-B.mp3`, `voiceover-block-C.mp3`.

---

### Alternative: Eleven v3 with bracket emotion/pause tags (no `<break>`)

If you'd rather use v3's more expressive delivery instead of `<break>` tags, use bracket tags (`[pause]`, `[short pause]`, `[confidently]`, `[excited]`, `[warmly]`, `[curious]`) in place of every `<break>` above, and generate one clip **per scene** (10 clips) rather than 3 blocks, since v3 has more take-to-take variance and you'll want to regenerate individual scenes.

| # | Time budget | Scene / on-screen action | Voiceover — paste into ElevenLabs |
|---|---|---|---|
| 1 | ~0:00–0:12 | Title / logo or homepage | `[confidently] VentureLens — the AI-operated company that screens startup applications for incubators. Built for the Build with Gemini XPRIZE, Entrepreneurship and Job Creation category.` |
| 2 | ~0:12–0:22 | Show production URL bar (venturelens.app) | `This is live, in production, on Google Cloud — Cloud Run, Cloud SQL, and Cloud Storage. Not a localhost demo, not a slide deck.` |
| 3 | ~0:22–0:55 | Applications → open scored app → Replay screening → result | `Every application is screened by Google Gemini before a human ever opens it. Watch — I'll replay a live screening call right now... [excited] structured scores, strengths, risk flags, and a committee-ready summary — generated in seconds, not weeks. Programs can run fully automated screening, or keep a human in the loop on every single decision.` |
| 4 | ~0:55–1:20 | `/ai-operations` — fleet cards, autonomy %, point at 99.9% and total actions | `[confidently] VentureLens isn't a thin AI wrapper bolted onto a SaaS app. Six Gemini agents actually run this company — growth, onboarding, support, finance, screening, and success. As of this morning: thirty-four thousand, five hundred seventy-nine logged agent actions... ninety-nine point nine percent of them decided by AI, at full autonomy — every one logged, in production, for judges to verify.` |
| 5 | ~1:20–1:45 | `/impact` — slow scroll across KPI cards | `[warmly] Everything here is computed from production data, not projections. One hundred sixty-five applications screened across fourteen countries. One hundred twenty-three founder-hours saved. Two startups accepted. And two thousand, eight hundred eighty-seven dollars in arms-length revenue from thirteen paying customers — real customers, real Stripe charges, zero related-party.` |
| 6 | ~1:45–1:55 | `/billing` — plans + charge history with classification | `Programs pay through Stripe — a one-time cohort package, or a monthly subscription. Our Finance agent classifies every single charge the moment it lands.` |
| 7 | ~1:55–2:05 | `/ask` — type question, show RAG answer | `[curious] Support runs on Gemini too. Watch — I'll ask: how many applications have we screened?... Answered instantly, straight from our own indexed data.` |
| 8 | ~2:05–2:15 | Application detail — decision buttons / founder email draft | `Committee decisions always stay with a human. Gemini drafts the evidence and the founder email — a person approves before anything is sent.` |
| 9 | ~2:15–2:25 | `/apply/summer-2026` — public form + pitch deck field | `Founders apply right here, in public. Pitch decks land in Cloud Storage and feed straight into Gemini screening.` |
| 10 | ~2:25–2:50 | `/impact` end card — URL + demo login on screen | `[warmly] VentureLens makes fair, fast startup selection accessible to every incubator — and it earns real revenue doing it. Every number you just saw is public and verifiable at venturelens dot app, slash impact. Try it yourself — demo credentials are on screen now. Thank you.` |

**Full script, no tags at all (paste as one block if you want a single continuous take, no `<break>` and no bracket tags — punctuation only):**

```
VentureLens — the AI-operated company that screens startup applications for incubators. Built for the Build with Gemini XPRIZE, Entrepreneurship and Job Creation category.

This is live, in production, on Google Cloud — Cloud Run, Cloud SQL, and Cloud Storage. Not a localhost demo, not a slide deck.

Every application is screened by Google Gemini before a human ever opens it. Watch — I'll replay a live screening call right now... structured scores, strengths, risk flags, and a committee-ready summary — generated in seconds, not weeks. Programs can run fully automated screening, or keep a human in the loop on every single decision.

VentureLens isn't a thin AI wrapper bolted onto a SaaS app. Six Gemini agents actually run this company — growth, onboarding, support, finance, screening, and success. As of this morning: thirty-four thousand, five hundred seventy-nine logged agent actions... ninety-nine point nine percent of them decided by AI, at full autonomy — every one logged, in production, for judges to verify.

Everything here is computed from production data, not projections. One hundred sixty-five applications screened across fourteen countries. One hundred twenty-three founder-hours saved. Two startups accepted. And two thousand, eight hundred eighty-seven dollars in arms-length revenue from thirteen paying customers — real customers, real Stripe charges, zero related-party.

Programs pay through Stripe — a one-time cohort package, or a monthly subscription. Our Finance agent classifies every single charge the moment it lands.

Support runs on Gemini too. Watch — I'll ask: how many applications have we screened?... Answered instantly, straight from our own indexed data.

Committee decisions always stay with a human. Gemini drafts the evidence and the founder email — a person approves before anything is sent.

Founders apply right here, in public. Pitch decks land in Cloud Storage and feed straight into Gemini screening.

VentureLens makes fair, fast startup selection accessible to every incubator — and it earns real revenue doing it. Every number you just saw is public and verifiable at venturelens dot app, slash impact. Try it yourself — demo credentials are on screen now. Thank you.
```

**Word count:** ~340 words / ~2:50 at a deliberate, numbers-heavy pace (~120 wpm) — matches the hard 3:00 cap with room for the Replay-screening wait time in scene 3.

---

## AI-narrated recording flow

Because AI narration has a fixed, known duration per clip, record **audio first, screen second**, then sync in editing — don't try to talk live over the app.

**Step 1 — Generate voiceover (ElevenLabs, Multilingual v2 + `<break>` tags)**

1. Go to elevenlabs.io → log in → **Text to Speech**.
2. Set **Model = Eleven Multilingual v2** (not v3 — it ignores `<break>` tags).
3. Pick a voice from the Voice Library and dial in the settings table above.
4. Paste **Block A** (plain text, not the markdown file) into the text box → Generate → listen → regenerate if needed → download as `voiceover-block-A.mp3`.
5. Repeat for **Block B** and **Block C**.
6. Note each block's exact duration (ElevenLabs player shows it, or `ffprobe -i file.mp3` if you have ffmpeg installed).

*(If you went with the v3/bracket-tag alternative instead, generate each of the 10 scenes as its own clip: `scene-01-title.mp3` … `scene-10-endcard.mp3`.)*

**Step 2 — Record the screen (silent, video only)**

1. OBS / Xbox Game Bar / Loom — 1920×1080, 30fps, capture browser window only, **mic muted**.
2. Record screen clips a few seconds **longer** than the matching audio block so you have trim room:
   - **For Block A** (scenes 1–2): use the Canva-exported logo intro MP4 (~8s, see "Intro logo card" above) for scene 1, then cut to a live screen recording of the production URL bar (~4s) for scene 2.
   - **For Block B** (scene 3): click into the pre-selected scored application → click **Replay screening** → hold on the spinner → let the result reveal. Record generously (30–40s) — the real Gemini call (~15s) will very likely run longer than the ~1.5s `<break>` in the audio, so you'll trim/pad in editing (see Step 3).
   - **For Block C** (scenes 4–10), record as one continuous pass or split into sub-clips at each `<break>` boundary — whichever is easier to sync:
     - `/ai-operations` — pan fleet cards, hold cursor on the 99.9% stat and total-actions counter.
     - `/impact` — slow scroll through KPI cards (screened, founder hours, accepted, revenue split).
     - `/billing` — plans, then charge history with the classification tag visible.
     - `/ask` — type "How many applications have we screened?", show the streamed answer.
     - Application detail — decision buttons and/or the founder email draft.
     - `/apply/summer-2026` — public form + pitch deck upload field.
     - Back to `/impact` — hold for the end-card overlay (URL + demo login).
3. Grab 2 takes per section where practical, for a clean backup cut.

**Step 3 — Edit / assemble**

1. Import the 3 silent screen clips + 3 ElevenLabs audio blocks into an editor (DaVinci Resolve — free — CapCut, or Premiere).
2. Lay Block A, B, C audio on the timeline back to back — this is your master timing reference.
3. Drop the matching screen clip under each audio block; trim in/out so key visual beats land under the matching words. For Block B specifically: since the real Replay-screening wait (~15s) is longer than the audio's `<break>` (~1.5s), either (a) speed up the spinner segment in edit to compress it near the break length, or (b) simply extend that one `<break time="1.5s" />` to something closer to your actual measured replay time (e.g. `<break time="3.0s" />`, the per-tag max) and pad the remainder with a freeze-frame/lower-third ("Scoring in progress...") during editing. If a screen clip runs short elsewhere, freeze the last frame briefly; if it runs long, trim dead time or speed it up 1.05–1.15×.
4. Add lower-third text overlays for the callout numbers (works with sound off too):
   - `100% applications screened by Gemini`
   - `6 AI business agents · 99.9% AI-decided · L0–L3 autonomy`
   - `$2,887 arms-length revenue · 13 customers · $0 related-party`
   - `Google Cloud Run + SQL + Storage`
   - `venturelens.app/impact`
5. Add a quiet instrumental bed under the narration (~ −24 dB, ducked slightly under the voice). Sources, ranked by ease:
   - **ElevenLabs Music** (same account you're already using for narration) — go to the **Music** tab → prompt e.g. *"subtle corporate tech background music, minimal, no vocals, ambient pads, ~3 minutes"* → generate → download. Licensed for commercial use, no attribution needed, and keeps the whole audio pipeline in one tool.
   - **YouTube Audio Library** — studio.youtube.com → left sidebar **Audio Library** → **Audio** tab → filter Genre: *Corporate* or *Cinematic*, or search "tech"/"ambient" → tick **"Attribution not required"** in filters to keep it simple → download.
   - **Mixkit** (mixkit.co/free-stock-music) or **Pixabay Music** (pixabay.com/music) — free, no attribution required, browse "Corporate"/"Technology" categories, download MP3 directly.
   - Avoid Incompetech/Bensound unless you're fine adding an attribution credit — their free tracks require it (CC BY license).
6. Add the end-card overlay (production URL + demo login), hold 3–4s after narration ends.
7. Export **1920×1080, H.264 MP4**, total runtime ≤ 3:00.

**Step 4 — Publish**

1. Upload to YouTube **Unlisted**.
2. Paste the URL into `DEVPOST_SUBMISSION.md` → *Video demo* field and the Devpost project's video field.
3. Update `docs/PROJECT_STATUS.md` submission checklist row for the demo video.

---

## Judging criteria map (must hit all three)

| Criterion | What judges need to see | Where in video |
|-----------|-------------------------|----------------|
| **Business Viability** | Real Stripe revenue, arms-length vs related-party split, SaaS pricing | `/impact` + `/billing` |
| **AI-Native Operations** | Gemini in production; agents run the company; autonomy L0–L3 | Replay screening + `/ai-operations` |
| **Category Impact** | Founder hours saved, programs enabled, jobs influenced | `/impact` narrative |
| **Hard rules** | Gemini API + Google Cloud in production | Replay screening + mention Cloud Run / SQL / GCS |

---

## Devpost — paste-ready fields

**Full field-by-field copy** (narrative, revenue, AI ops, category impact, testimonials): [`DEVPOST_SUBMISSION.md`](DEVPOST_SUBMISSION.md)

Quick reference below — prefer `DEVPOST_SUBMISSION.md` for final paste.

**One-liner:**  
VentureLens is an AI-operated company that helps every incubator screen startup applications with Gemini — and earns real revenue doing it.

**Tagline:**  
AI-operated screening for incubators. Gemini on Google Cloud. Real revenue.

**Elevator pitch (30 sec):**  
Incubators receive hundreds of applications and review them by hand for weeks. VentureLens uses Google Gemini to screen every submission against configurable rubrics — fully automated or human-in-the-loop. What makes us different: AI doesn't just power the product; **six Gemini agents run our sales, support, finance, onboarding, and success operations**. We have real Stripe revenue with arms-length customers tracked separately from related-party, a public `/impact` dashboard judges can verify, and the full stack runs on **Google Cloud Run, Cloud SQL, and Cloud Storage**. We're making fair, fast startup selection accessible to programs worldwide.

---

## Devpost — “What does it do?”

**Problem**  
Program managers at incubators and accelerators drown in applications. Review is slow, inconsistent, and founders wait weeks for feedback.

**Solution**  
VentureLens is a B2B SaaS platform where **every inbound application is screened by Gemini** against program-specific rubrics. Managers get structured scores, risk flags, and committee-ready summaries — with full human override (Accept, Shortlist, Reject, Waitlist).

**AI-native operations (not just a feature)**  
Six production agents — **Screening, Growth, Onboarding, Support, Finance, Success** — call Gemini, make decisions, and log every action with an autonomy level (L0–L3). The `/ai-operations` dashboard shows which decisions ran without human approval.

**Business viability**  
Stripe billing (Cohort **$199**, Starter **$299/mo**). Arms-length revenue from paying customers; related-party revenue reported separately per competition rules. Live evidence at `/impact` and `GET /api/v1/impact.json`.

**Category impact**  
Founder hours saved, accepted startups, and jobs-influenced metrics are computed from production data — not slide-deck estimates.

**Built with**  
Laravel, Vue, Inertia, **Google Gemini API**, Stripe, **Google Cloud Run**, **Cloud SQL (MySQL)**, **Cloud Storage** (pitch decks & logos).

**Try it**  
Production: https://venturelens.app  
Demo: `demo@venturelens.app` / `demo123`

---

## Live-read teleprompter (~2:50)

*Use this only if you're narrating live instead of using the ElevenLabs script above. Numbers below are stale (2026-06-17) — refresh from `/impact` before reading.*

Read **live KPIs** from `/impact` where bracketed. Practice once with timer.

| Time | Scene | Action | Voiceover (read aloud) |
|------|-------|--------|------------------------|
| **0:00–0:12** | Title / Welcome | Logo + category text on screen or homepage | "VentureLens — AI-operated startup screening for incubators. Built for the Build with Gemini XPRIZE: Entrepreneurship and Job Creation." |
| **0:12–0:22** | URL bar | Show `venturelens-web-…run.app` | "This is live on Google Cloud — Cloud Run, Cloud SQL, and Cloud Storage. Not a localhost demo." |
| **0:22–0:55** | Dashboard → Applications → scored app | Scores, strengths, risks → **Replay screening** → cut to result | "Every application is processed by Gemini before a human sees it. Structured scores, risk flags, committee-ready summaries — in minutes, not weeks. Programs run fully automated screening or stay human-in-the-loop with committee decisions." |
| **0:55–1:20** | `/ai-operations` | 6 agents, autonomy L0–L3, % AI decisions, execution log (point at `rag_chat_answer` if visible) | "VentureLens is not a thin AI wrapper. Six agents run the company — growth, onboarding, support, finance, screening, and success. [POINT AT %] Eighty-seven percent of operational decisions ran at L2 or L3 autonomy — logged in production." |
| **1:20–1:45** | `/impact` | Revenue split, screened count, Gemini calls, founder hours | "Judges verify everything live. Arms-length and related-party revenue are tracked separately. [READ LIVE NUMBERS] Applications screened, Gemini API calls, founder hours saved — computed from production data." |
| **1:45–1:55** | `/billing` | Plans + charge history with classification | "Programs pay via Stripe — cohort or subscription. The Finance agent classifies every charge at checkout." |
| **1:55–2:05** | `/ask` | Type: "How many applications have we screened?" → show RAG answer | "Support runs on Gemini RAG — answers from indexed applications and screening history, with autonomy logging." |
| **2:05–2:15** | Application detail | Accept / Shortlist buttons or founder email draft | "Committee decisions stay with humans — AI prepares evidence and drafts founder emails." |
| **2:15–2:25** | `/apply/summer-2026` | Public form + pitch deck field | "Founders apply publicly; pitch decks land in Cloud Storage and feed Gemini screening." |
| **2:25–2:50** | `/impact` end card | URL + demo credentials on screen | "VentureLens expands fair startup selection to every incubator — and earns real revenue doing it. Demo login in the README and Devpost." |

### Shorter cut (if over 3:00)

Drop `/apply` scene; keep Replay screening + AI Operations + Impact + Billing.

### On-screen overlays (optional lower-thirds)

- `100% applications screened by Gemini`
- `6 AI business agents · L0–L3 autonomy`
- `[LIVE $] arms-length · [LIVE $] related-party`
- `Google Cloud Run + SQL + Storage`
- `{production URL}/impact`

### If something fails during recording

| Issue | Fallback |
|-------|----------|
| Replay screening slow | Pre-record one successful replay; cut to completed score |
| `/impact` KPI is 0 | Run replay + wait for worker; or use committed `docs/evidence/impact-*.json` and say "snapshot from production" |
| Billing empty | Show `/impact` revenue panel instead |
| Gemini 429 | Use last screened application; mention retry logic in voiceover |

---

## Recording setup

| Tool | Settings |
|------|----------|
| **OBS / Xbox Game Bar / Loom** | 1920×1080, 30fps, capture browser window only |
| **Browser** | Chrome incognito, zoom 100%, bookmarks bar hidden |
| **Mic** | Headset preferred; test levels before take |
| **Export** | H.264 MP4; upload YouTube **Unlisted** |
| **Devpost** | Paste URL into `DEVPOST_SUBMISSION.md` → Video demo field |

**Do not show:** `.env`, Stripe secret keys, Cloud Console credentials.

---

## Recording checklist

- [ ] `.\scripts\preflight-demo-video.ps1` — no failures
- [ ] Arms-length revenue > $0 on `/impact` (or evidence PDF fallback planned)
- [ ] Worker healthy — replay screening completes in ~15s
- [ ] Tabs: Applications → AI Operations → Impact → Billing → Ask
- [ ] Live KPI numbers copied into teleprompter
- [ ] One full dry run with phone timer (target 2:45)
- [ ] Export 1920×1080 H.264 → YouTube unlisted
- [ ] Paste YouTube URL into Devpost + `DEVPOST_SUBMISSION.md`
- [ ] Capture 5 PNG screenshots per [JUDGE_EVIDENCE.md](JUDGE_EVIDENCE.md)

---

## Devpost field cheat sheet

See **[`DEVPOST_SUBMISSION.md`](DEVPOST_SUBMISSION.md)** for complete paste-ready text. Quick links:

| Field | Doc section |
|-------|-------------|
| Tagline, inspiration, what it does | DEVPOST_SUBMISSION → Project details |
| Written narrative (500–1k words) | DEVPOST_SUBMISSION → Written narrative |
| Revenue / P&L / confirmations | DEVPOST_SUBMISSION → Revenue & financial evidence |
| AI ops + GCP + Gemini | DEVPOST_SUBMISSION → AI-native operations evidence |
| Category impact | DEVPOST_SUBMISSION → Category impact evidence |
| Video / GitHub / gallery | DEVPOST_SUBMISSION → Media & repo |

---

## Related docs

| Topic | Guide |
|-------|--------|
| **Devpost paste-ready fields** | [`DEVPOST_SUBMISSION.md`](DEVPOST_SUBMISSION.md) |
| Judge screenshots & API | [JUDGE_EVIDENCE.md](JUDGE_EVIDENCE.md) |
| Stripe & revenue split | [STRIPE_JUDGE_GUIDE.md](STRIPE_JUDGE_GUIDE.md) |
| Advanced stage gate | [ADVANCED_STAGE_GATE.md](../ADVANCED_STAGE_GATE.md) |
| Cloud Run deploy | [DEPLOY_CLOUD_RUN.md](DEPLOY_CLOUD_RUN.md) |
| Full spec (Appendix F) | [VENTURELENS_SYSTEM_REQUIREMENTS.md](../VENTURELENS_SYSTEM_REQUIREMENTS.md) |
