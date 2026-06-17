# VentureLens — System Requirements & Specifications

**Version:** 2.0 (Competition-optimized)  
**Date:** June 2026  
**Status:** Implementation-ready  
**Audience:** Engineering team / Cursor AI agent implementing the product  
**Product URL (target):** `https://venturelens.app`  
**Devpost:** Build with Gemini XPRIZE — Category: Entrepreneurship & Job Creation

> **Positioning intent:** This document is engineered to place VentureLens in a *top-5, prize-contending* position in the Build with Gemini XPRIZE. Every requirement is traceable to one of the three judging criteria — **Business Viability**, **AI-Native Operations**, **Category Impact**. Sections marked **🏆 WINS** are the highest-leverage, score-moving features; prioritize them even under time pressure. The single biggest differentiator is **§6: AI is not a feature inside the product — AI *runs the business itself*** (sales, marketing, support, finance, and operations), which is exactly what the "AI-Native Operations" criterion rewards and what most competing teams will under-build.

---

## Table of Contents

1. [Executive Summary](#1-executive-summary)
2. [Business Context & Constraints](#2-business-context--constraints)
3. [Goals & Success Criteria](#3-goals--success-criteria)
4. [User Personas & Roles](#4-user-personas--roles)
5. [Functional Requirements](#5-functional-requirements)
6. [AI-Native Operations Requirements](#6-ai-native-operations-requirements)
7. [Gemini Integration Specification](#7-gemini-integration-specification)
8. [Non-Functional Requirements](#8-non-functional-requirements)
9. [Technology Stack](#9-technology-stack)
10. [System Architecture](#10-system-architecture)
11. [Directory Structure (Recommended)](#11-directory-structure-recommended)
12. [Data Model](#12-data-model)
13. [API Specification](#13-api-specification)
14. [Core User Flows](#14-core-user-flows)
15. [Billing & Subscription](#15-billing--subscription)
16. [Google Cloud Deployment](#16-google-cloud-deployment)
17. [Observability & Evidence Package](#17-observability--evidence-package)
18. [Security & Privacy](#18-security--privacy)
19. [Internationalization](#19-internationalization)
20. [Environment Variables](#20-environment-variables)
21. [Implementation Phases](#21-implementation-phases)
22. [Testing Requirements](#22-testing-requirements)
23. [Devpost Submission Checklist](#23-devpost-submission-checklist)
24. [Out of Scope (v1)](#24-out-of-scope-v1)
25. [Glossary](#25-glossary)

**Strategic appendices (competition-critical 🏆):**
- [Appendix A: Default Rubric Template](#appendix-a-default-rubric-template)
- [Appendix B: Landing Page Copy](#appendix-b-landing-page-copy)
- [Appendix C: README Sections for GitHub](#appendix-c-readme-sections-for-github)
- [Appendix D: Go-To-Market & Revenue Acceleration Playbook 🏆](#appendix-d-go-to-market--revenue-acceleration-playbook-)
- [Appendix E: Category Impact — Theory of Change 🏆](#appendix-e-category-impact--theory-of-change-)
- [Appendix F: Demo Video Script & Live Pitch Narrative 🏆](#appendix-f-demo-video-script--live-pitch-narrative-)
- [Appendix G: Differentiation & Moat](#appendix-g-differentiation--moat)
- [Appendix H: Risk Register (Competition-Critical)](#appendix-h-risk-register-competition-critical)
- [Appendix I: Phase 0 — Competition Build Priorities](#appendix-i-phase-0--competition-build-priorities-read-first)

---

## 1. Executive Summary

### 1.1 What is VentureLens?

**VentureLens** is an AI-native B2B SaaS product that helps **incubators, accelerators, and university innovation programs** screen startup applications using **Google Gemini** in production.

Programs upload or collect founder applications; Gemini analyzes each submission against configurable evaluation rubrics and returns structured scores, risk flags, and committee-ready summaries. Program managers review AI output, override when needed, and export decisions.

### 1.2 Problem Statement

Innovation programs receive hundreds of applications per cohort but review them manually (spreadsheets, email, weeks of committee meetings). Smaller programs lack access to AI-powered screening. Founders wait too long; reviewers burn out; selection is inconsistent.

### 1.3 Solution Summary

| Capability | Description |
|------------|-------------|
| Application intake | Web forms + PDF/pitch deck upload |
| AI screening | 100% of inbound applications processed by Gemini before human review |
| Evaluation reports | Committee-ready summaries and side-by-side comparisons |
| Founder communication | AI-drafted feedback emails (human-approved before send) |
| Operations dashboard | Screening throughput, API usage, agent execution logs |
| Billing | Stripe subscriptions and one-time cohort packages |

### 1.4 Relationship to Gohorto

VentureLens is a **new, separate product** (new brand, new codebase, new customers) built by team members with incubator domain expertise from **Gohorto** (E-Incubation platform). It is **not** a rebrand of Gohorto. Standard open-source frameworks may be used as boilerplate; all Gemini integration and screening logic must be original work created during the hackathon window (May–August 2026).

Partner context: **BINA Business Incubator** (Turkey) — programs in 9+ countries, ecosystem of 20,000+ startups — may serve as pilot environment. Revenue from related parties must be tracked separately from arms-length revenue for competition reporting.

### 1.5 Why We Win (Judges' View) 🏆

VentureLens is structurally advantaged on all three judging criteria. Most teams will build a "thin AI wrapper" with a demo and zero revenue. We win because:

| Judging Criterion | Why most teams are weak | Why VentureLens is strong |
|-------------------|-------------------------|---------------------------|
| **Business Viability** | No real customers in 90 days; toy demos | We have a sharp B2B wedge, real buyers (program directors), warm distribution into 9+ countries, and a pricing model that converts in a single sales call. Arms-length revenue is achievable inside the window. |
| **AI-Native Operations** | AI used for one feature; humans still run the company | **AI runs the entire VentureLens business** — an AI growth agent does outreach, an AI support agent answers customers, an AI onboarding agent configures programs, an AI finance agent reconciles revenue. We don't just *sell* AI screening; we *operate* on AI. |
| **Category Impact** | Vague "we help founders" claims | A quantified theory of change: applications screened, founder hours saved, programs enabled, jobs influenced — with a credible path to 1,000+ programs. |

**One-line thesis for judges:** *"VentureLens is an AI-operated company that makes AI-powered startup selection accessible to every incubator on earth — and it earns real money doing it."*

### 1.6 Strategic Wedge & Expansion ("Land → Expand")

```
LAND:   AI application screening (acute pain, fast ROI, single-call sale)
   ↓
ATTACH: AI committee reports + founder comms (stickiness, daily use)
   ↓
EXPAND: AI cohort operations (mentor matching, progress, impact reports)
   ↓
PLATFORM: Full program OS (upgrade path to Gohorto for graduates)
```

We win the hackathon on **LAND + ATTACH** (shippable + revenue-generating in 90 days) while demonstrating a credible expansion narrative that signals durable category impact.

---

## 2. Business Context & Constraints

### 2.1 Hackathon Compliance (Build with Gemini XPRIZE)

The implementation **must** satisfy these mandatory rules:

| Rule | Requirement |
|------|-------------|
| **New project** | Created after May 19, 2026. Pre-existing boilerplate allowed with disclosure. |
| **Google Cloud** | At least one Google Cloud product in production. |
| **Gemini API** | At least **one Gemini API call per application** in the deployed app. |
| **AI in operations** | AI must transform workflows — depicted in video and documentation. |
| **Real business** | Real users, real revenue (arms-length customers weighted heavily by judges). |
| **Category** | Entrepreneurship & Job Creation |
| **Code repository** | Public with license, or private shared with `testing@devpost.com` and `judging@hacker.fund`. |
| **Evidence** | Production logs, API usage, revenue proof in repo (`docs/evidence/`). |

### 2.2 Business Model

| Tier | Price (USD) | Limits |
|------|-------------|--------|
| **Free trial** | $0 | 5 screenings |
| **Cohort package** | $199 one-time | 1 cohort, up to 50 applications |
| **Starter** | $299/month | 2 active cohorts, 200 screenings/month |
| **Pro** | $799/month | Unlimited cohorts, 1,000 screenings/month |

Revenue types for reporting:
- **Arms-length revenue:** New programs with no pre-existing Gohorto/BINA contract.
- **Related-party revenue:** BINA, Gohorto, team/family, existing clients — report separately.

### 2.3 Target Market

- **Primary:** Program managers at incubators, accelerators, university entrepreneurship centers.
- **Geography:** Turkey, MENA, UK, emerging markets (English, Arabic, Turkish).
- **Secondary:** Startup founders (applicants — free users).

---

## 3. Goals & Success Criteria

### 3.1 MVP Goals (Hackathon Window)

- [ ] Production deployment on Google Cloud Run
- [ ] Gemini screens 100% of inbound applications
- [ ] Admin dashboard with evaluation reports and API usage metrics
- [ ] Stripe billing for cohort packages and subscriptions
- [ ] 3–5 arms-length paying pilot programs
- [ ] 50+ real users (program staff + founders)
- [ ] Evidence package in `docs/evidence/` for Devpost judges
- [ ] Demo video < 3 minutes

### 3.2 Judging Criteria Alignment (Traceability Matrix) 🏆

Every criterion maps to concrete, demonstrable features and evidence. Build these first.

| Criterion | What judges look for | VentureLens features (FR IDs) | Evidence we produce |
|-----------|----------------------|-------------------------------|---------------------|
| **Business Viability** | Real users, real arms-length revenue, sustainable model | Stripe billing (§15), quota tiers, GTM engine (§6.4), self-serve onboarding | Stripe export, signed pilots, MRR chart, CAC/LTV, related-party split |
| **AI-Native Operations** | AI live in production executing *key business decisions* across the operation | Screening (FR-AI-*), **Autonomous Business Agents (§6.4)**, autonomy ladder (§6.5), agent execution logs | Agent execution dashboard, % decisions automated, Gemini call volume, "company run by AI" narrative |
| **Category Impact** | Redefines how it works OR credible scale | Theory of change (Appendix E), impact dashboard, multi-country reach via partners | Applications screened, founder hours saved, programs enabled, jobs influenced |

### 3.3 Target Scorecard (Quantified KPIs for Submission)

Set and track these so the submission shows hard numbers, not adjectives.

| KPI | Floor (pass) | Target (competitive) | Stretch (winning) |
|-----|--------------|----------------------|-------------------|
| Arms-length paying customers | 3 | 8 | 15+ |
| Total arms-length revenue (USD) | $600 | $4,000 | $12,000+ |
| Applications screened (real) | 100 | 1,000 | 5,000+ |
| Gemini API calls in production | 500 | 5,000 | 25,000+ |
| % of operational decisions executed by AI | 50% | 75% | 90% |
| Registered organizations | 5 | 25 | 75+ |
| Founder hours saved (computed) | 200 | 2,000 | 10,000+ |
| Countries reached | 1 | 5 | 9+ |
| Public verifiable testimonials | 1 | 3 | 6+ |
| Net revenue retention signal (renewals/expansions) | n/a | 1 | 3+ |

> Implement an **internal `CompetitionMetrics` service** (see §17.4) that computes every KPI above automatically from production data and renders a single judge-facing page (`/impact`) — so evidence is live, not hand-assembled.

### 3.4 Anti-Patterns to Avoid (Disqualifiers & Score-Killers)

| Anti-pattern | Why it hurts | Mitigation |
|--------------|--------------|------------|
| AI only in the screening feature | Fails "AI-native *operations*" | Build §6.4 autonomous business agents |
| Revenue only from BINA/Gohorto | Fails arms-length test | Pursue net-new programs; report related-party separately |
| Demo-only, no production | Fails Stage 1 viability gate | Real deploy, real users, real Stripe charges |
| Hand-made screenshots | Weak evidence | Auto-generated `/impact` + live logs |
| "We help founders" with no numbers | Weak category impact | Quantified theory of change (Appendix E) |
| Gemini not actually called | Rule violation | ≥1 Gemini call per application; log token usage |

---

## 4. User Personas & Roles

### 4.1 Roles

| Role | Description | Permissions |
|------|-------------|-------------|
| **Super Admin** | VentureLens platform operator | Manage all organizations, billing overrides, system config |
| **Organization Owner** | Program director (customer) | Manage program, rubrics, team, billing, export data |
| **Program Manager** | Staff reviewer | Review AI scores, approve/reject, send emails, run reports |
| **Reviewer** | Committee member (read-only+) | View assigned applications, add notes, vote |
| **Applicant** | Startup founder | Submit application, upload documents, view status |
| **Demo User** | Judge/test account | Read-only or sandbox access for judging |

### 4.2 Personas

**Sarah — Program Director (Buyer)**  
Runs a university incubator, 200 applications/cohort, 2 staff. Needs fast, fair screening without enterprise budget.

**Ahmed — Founder (Applicant)**  
Applied to 5 programs, waits weeks for answers. Wants transparent, timely feedback.

**Mustafa — Ecosystem Partner (BINA)**  
Operates programs across 9+ countries. Needs consistent evaluation at scale.

---

## 5. Functional Requirements

### 5.1 Organization & Program Management

| ID | Requirement | Priority |
|----|-------------|----------|
| FR-ORG-01 | User can register organization (name, country, website) | P0 |
| FR-ORG-02 | Organization Owner can invite Program Managers and Reviewers | P0 |
| FR-ORG-03 | Organization can create one or more **Programs** (cohorts) | P0 |
| FR-ORG-04 | Each Program has: name, description, application open/close dates, max applications | P0 |
| FR-ORG-05 | Organization can configure evaluation **rubric** per Program | P0 |
| FR-ORG-06 | Organization dashboard shows usage: screenings used, plan limits, billing status | P0 |

### 5.2 Evaluation Rubric Configuration

| ID | Requirement | Priority |
|----|-------------|----------|
| FR-RUB-01 | Rubric consists of weighted **criteria** (e.g., Team 25%, Market 25%, Traction 25%, Innovation 25%) | P0 |
| FR-RUB-02 | Each criterion has: name, description, weight (%), scoring guide (1–10) | P0 |
| FR-RUB-03 | Organization can use default rubric template or customize | P0 |
| FR-RUB-04 | Rubric is passed to Gemini as structured JSON in screening prompts | P0 |
| FR-RUB-05 | Support rubric templates: General Startup, Social Enterprise, Deep Tech | P1 |

### 5.3 Application Intake (Applicant Portal)

| ID | Requirement | Priority |
|----|-------------|----------|
| FR-APP-01 | Public application URL per Program (e.g., `/apply/{program_slug}`) | P0 |
| FR-APP-02 | Application form fields: startup name, founder name(s), email, country, stage, sector, one-liner, problem, solution, market, traction, team, funding needs | P0 |
| FR-APP-03 | Upload pitch deck (PDF, max 20 MB) and optional supplementary files | P0 |
| FR-APP-04 | Save draft and resume later (email magic link or account) | P1 |
| FR-APP-05 | On submit, trigger async Gemini screening job | P0 |
| FR-APP-06 | Applicant sees status: Submitted → Under Review → Decision | P0 |
| FR-APP-07 | Applicant receives email notification on status change | P1 |

### 5.4 AI Screening Engine

| ID | Requirement | Priority |
|----|-------------|----------|
| FR-AI-01 | **Every submitted application** triggers at least one Gemini API call | P0 |
| FR-AI-02 | Screening returns structured JSON: overall_score, criterion_scores[], strengths[], weaknesses[], risk_flags[], summary, recommendation | P0 |
| FR-AI-03 | PDF pitch decks parsed and sent to Gemini (multimodal or text extraction + Gemini) | P0 |
| FR-AI-04 | Incomplete applications flagged by AI; status set to `needs_info` | P0 |
| FR-AI-05 | Screening runs asynchronously via queue; applicant sees "Processing" state | P0 |
| FR-AI-06 | Failed API calls retry with exponential backoff (max 3); dead-letter logged | P0 |
| FR-AI-07 | All Gemini requests/responses logged for audit (redact PII in public exports) | P0 |
| FR-AI-08 | Program Manager can **re-run screening** after rubric change | P1 |
| FR-AI-09 | Shortlisted applications get deeper analysis via Gemini 2.5 Pro (committee report) | P1 |

### 5.5 Review & Decision Workflow

| ID | Requirement | Priority |
|----|-------------|----------|
| FR-REV-01 | Program Manager sees list of applications with AI score, status, flags | P0 |
| FR-REV-02 | Filter/sort by score, status, date, risk flags | P0 |
| FR-REV-03 | Application detail page: form data, uploaded files, AI evaluation, raw Gemini reasoning | P0 |
| FR-REV-04 | Manager can override AI score with manual score and note (audit trail) | P0 |
| FR-REV-05 | Decision actions: Shortlist, Accept, Reject, Waitlist, Needs Info | P0 |
| FR-REV-06 | Side-by-side comparison of 2–10 applicants (AI-generated) | P1 |
| FR-REV-07 | Export committee report as PDF | P1 |
| FR-REV-08 | Bulk export applications + scores as CSV/Excel | P1 |

### 5.6 AI-Drafted Founder Communications

| ID | Requirement | Priority |
|----|-------------|----------|
| FR-EMAIL-01 | On decision, Gemini drafts email to founder (personalized, professional) | P0 |
| FR-EMAIL-02 | Manager reviews/edits draft before send | P0 |
| FR-EMAIL-03 | Email templates by decision type: accepted, rejected, waitlisted, needs_info | P0 |
| FR-EMAIL-04 | Sent emails logged with timestamp and content hash | P0 |
| FR-EMAIL-05 | Transactional email via SendGrid, Mailgun, or Google-compatible SMTP | P0 |

### 5.7 Operations & Admin Dashboard

| ID | Requirement | Priority |
|----|-------------|----------|
| FR-OPS-01 | Dashboard: applications today/week, screenings completed, avg score | P0 |
| FR-OPS-02 | Gemini API usage: calls, tokens, latency, errors (last 7/30 days) | P0 |
| FR-OPS-03 | Agent execution log: intake → screen → score → report → email pipeline | P0 |
| FR-OPS-04 | Exportable screenshots/data for `docs/evidence/` (judges) | P0 |
| FR-OPS-05 | Screening quota vs plan limits with upgrade CTA | P0 |

### 5.8 Authentication & Authorization

| ID | Requirement | Priority |
|----|-------------|----------|
| FR-AUTH-01 | Email/password registration and login | P0 |
| FR-AUTH-02 | Google OAuth login (optional, recommended) | P1 |
| FR-AUTH-03 | Role-based access control (RBAC) per organization | P0 |
| FR-AUTH-04 | Password reset, email verification | P0 |
| FR-AUTH-05 | Demo account for judges: `demo@venturelens.app` / documented password | P0 |

### 5.9 Landing & Marketing Pages

| ID | Requirement | Priority |
|----|-------------|----------|
| FR-WEB-01 | Public landing page: value prop, pricing, CTA | P0 |
| FR-WEB-02 | Sign up → create organization → create first program wizard | P0 |
| FR-WEB-03 | SEO meta tags, Open Graph | P1 |

---

## 6. AI-Native Operations Requirements

Judges assess **AI-native operations**. The system must demonstrate AI executing key business decisions in production.

### 6.1 Automated Decisions (No Human Input)

| Operation | AI Responsibility |
|-----------|-------------------|
| Application completeness check | Gemini determines if submission is complete or needs more info |
| Initial screening score | Gemini scores every application against rubric |
| Risk classification | Gemini assigns risk flags (e.g., incomplete team, no traction) |
| Prioritization | Applications ranked by AI score for committee review queue |
| Report drafting | Gemini generates committee briefs and comparison documents |
| Email drafting | Gemini writes founder feedback emails from evaluation results |

### 6.2 Human-in-the-Loop

| Operation | Human Responsibility |
|-----------|---------------------|
| Final accept/reject | Program Manager confirms decision |
| Email send | Manager approves AI draft before delivery |
| Score override | Manager can override with documented reason |

### 6.3 Production Evidence Requirements

Every AI action must be logged to `agent_executions` table and Google Cloud Logging:

```
intake_received → document_parsed → gemini_screen_called → score_stored → report_generated → email_drafted → [email_sent]
```

Implement `AgentExecutionLogger` service called at each step.

---

### 6.4 Autonomous Business Agents — "The Company Runs on AI" 🏆 (DECISIVE)

> This is the **highest-scoring section** of the entire project. The "AI-Native Operations" criterion rewards teams where *AI executes key business decisions and broadly governs the operation* — not teams who merely ship an AI feature. VentureLens must demonstrate that **the VentureLens company itself is operated by a team of Gemini agents.** Build at least 4 of the 6 agents below in production.

Each agent is a scheduled/event-driven worker that calls Gemini to make and execute a real business decision, then logs it to `agent_executions` with `agent_name`, `decision`, `action_taken`, and `autonomy_level`.

| # | Agent | Business function | Key autonomous decisions | Gemini role | Priority |
|---|-------|-------------------|--------------------------|-------------|----------|
| A1 | **Growth Agent** | Sales & marketing | Identify target incubators, draft + queue personalized outreach, follow-up cadence, write landing/blog/social copy | Generates ICP-matched messaging, sequences, content | P0 🏆 |
| A2 | **Onboarding Agent** | Customer onboarding | Configure a new program, auto-generate a tailored rubric from the org's website/description, set up first cohort | Reads org context → proposes rubric + program config | P0 🏆 |
| A3 | **Support Agent** | Customer support | Answer customer questions in-app/email, resolve common issues, escalate edge cases | RAG over docs + Gemini answers; decides resolve vs escalate | P0 🏆 |
| A4 | **Screening Agent** | Core product ops | Score, flag, prioritize, request-more-info on every application (the engine in §6.1) | Evaluates applications (already specified) | P0 🏆 |
| A5 | **Revenue/Finance Agent** | Finance ops | Reconcile Stripe events, classify arms-length vs related-party, flag dunning, compute KPIs | Categorizes transactions, drafts revenue narrative | P1 |
| A6 | **Success/Retention Agent** | Customer success | Detect at-risk/low-usage orgs, draft re-engagement, suggest plan upgrades, request testimonials post-success | Predicts churn signal + drafts intervention | P1 |

#### 6.4.1 Agent Requirements

| ID | Requirement | Priority |
|----|-------------|----------|
| FR-AGT-01 | Each agent runs on a schedule (Cloud Scheduler) and/or domain events | P0 |
| FR-AGT-02 | Each agent decision calls Gemini and records input, output, decision, action | P0 |
| FR-AGT-03 | Each agent has an **autonomy level** (see §6.5): observe / suggest / act-with-approval / fully-autonomous | P0 |
| FR-AGT-04 | All agent actions logged to `agent_executions` with `agent_name`, `autonomy_level`, `decision`, `confidence` | P0 |
| FR-AGT-05 | Agent activity is visible on the public `/impact` and admin "AI Operations" dashboards | P0 |
| FR-AGT-06 | Guardrails: spend caps, send-rate limits, human override, and "kill switch" per agent | P0 |
| FR-AGT-07 | Growth Agent must operate within anti-spam law (opt-out, throttling); prefer warm/owned channels | P0 |
| FR-AGT-08 | Agents degrade gracefully: on Gemini failure, fall back to safe default + alert | P1 |

#### 6.4.2 Why this wins

The submission can credibly state: *"VentureLens employs zero full-time operators for sales outreach, onboarding, tier-1 support, and finance reconciliation — these are run by Gemini agents in production. Humans set strategy and approve high-stakes actions."* This is the literal definition of **AI-Native Operations** and is rare among hackathon entries.

### 6.5 Autonomy Ladder (Governance Model)

Each agent action declares how autonomously it acted. Track the **distribution** — judges want to see meaningful fully-autonomous activity, safely governed.

| Level | Name | Description | Example |
|-------|------|-------------|---------|
| L0 | Observe | AI analyzes, no action | Flags a low-usage org |
| L1 | Suggest | AI proposes, human acts | Drafts outreach for review |
| L2 | Act-with-approval | AI prepares + executes on one click | Founder email draft → manager sends |
| L3 | Fully autonomous | AI decides + executes, logged | Auto-reply to a known support FAQ; auto-classify a Stripe charge |

**Target mix for submission:** ≥ 30% of all logged agent actions at **L2–L3**, with a clear governance/guardrail story. Render this as a chart on the AI Operations dashboard.

### 6.6 "AI-Native" Proof Metrics (auto-computed)

Surface these on `/impact` and in the evidence pack (see §17.4):

- **% of operational decisions executed by AI** = AI-decided actions ÷ total operational actions
- **Agent action volume** by agent (A1–A6), last 7/30 days
- **Autonomy distribution** (L0–L3)
- **Human-hours displaced** (estimated): Σ(agent actions × per-task human-minute baseline)
- **Gemini calls per business function** (not just screening)

### 6.7 Agent Service Interface (example)

```php
interface BusinessAgentInterface
{
    public function name(): string;            // 'growth' | 'onboarding' | ...
    public function autonomyLevel(): int;      // 0..3
    public function run(AgentContext $ctx): AgentResult; // calls Gemini, decides, acts, logs
}
```

---

## 7. Gemini Integration Specification

### 7.1 Models

| Use Case | Model | Rationale |
|----------|-------|-----------|
| First-pass screening (high volume) | `gemini-2.0-flash` | Fast, cost-effective |
| Committee reports, complex PDFs | `gemini-2.5-pro` | Deeper analysis |
| Email drafting | `gemini-2.0-flash` | Fast text generation |

**Rule:** No non-Google LLMs in production. Gemini only in deployed app.

### 7.2 Screening Prompt Structure

```json
{
  "system": "You are an expert startup evaluator for innovation programs. Score applications fairly, explainably, and consistently. Return valid JSON only.",
  "user": {
    "rubric": { "criteria": [...] },
    "application": { "startup_name": "...", "fields": {...} },
    "documents_summary": "Extracted text from pitch deck...",
    "output_schema": {
      "overall_score": "number 0-100",
      "criterion_scores": [{ "name": "", "score": 0, "reasoning": "" }],
      "strengths": ["string"],
      "weaknesses": ["string"],
      "risk_flags": [{ "code": "", "severity": "low|medium|high", "message": "" }],
      "summary": "string",
      "recommendation": "shortlist|reject|needs_review",
      "completeness": "complete|incomplete",
      "missing_fields": ["string"]
    }
  }
}
```

### 7.3 API Call Requirements

| Requirement | Detail |
|-------------|--------|
| Minimum calls | ≥ 1 Gemini call per submitted application |
| Response format | `responseMimeType: application/json` where supported |
| Timeout | 60s per call; queue job timeout 120s |
| Token limits | Truncate deck text to ~30k chars; summarize if larger |
| Error handling | Retry 3x; store error in `screening_results.error` |
| Cost tracking | Log `prompt_token_count`, `candidates_token_count` per call |

### 7.4 PDF / Document Processing

1. Upload to Google Cloud Storage.
2. Extract text via `pdftotext` (Cloud Run sidecar) or Gemini multimodal file API.
3. Pass extracted content to screening prompt.
4. Store extraction metadata for audit.

### 7.5 Gemini Service Interface (PHP/Laravel Example)

```php
interface GeminiScreeningServiceInterface
{
    public function screenApplication(Application $application, Rubric $rubric): ScreeningResult;
    public function generateCommitteeReport(Collection $applications): CommitteeReport;
    public function draftFounderEmail(Application $application, string $decision): EmailDraft;
    public function compareApplicants(Collection $applications): ComparisonReport;
}
```

---

## 8. Non-Functional Requirements

| ID | Category | Requirement |
|----|----------|-------------|
| NFR-01 | Performance | Screening job completes within 90s for 95% of applications |
| NFR-02 | Availability | 99% uptime during hackathon judging period |
| NFR-03 | Scalability | Cloud Run auto-scales 0–10 instances |
| NFR-04 | Security | HTTPS only; encrypt data at rest (Cloud SQL); OWASP top 10 |
| NFR-05 | Privacy | GDPR-aware; data processing agreement for EU programs |
| NFR-06 | Audit | All AI decisions and human overrides logged with user ID + timestamp |
| NFR-07 | Localization | UI in English; Gemini prompts accept EN/AR/TR content |
| NFR-08 | Accessibility | WCAG 2.1 AA for public application forms |
| NFR-09 | Browser support | Chrome, Firefox, Safari, Edge (last 2 versions) |
| NFR-10 | Mobile | Responsive applicant form (mobile-first) |

---

## 9. Technology Stack

### 9.1 Required (Hackathon Compliance)

| Layer | Technology |
|-------|------------|
| LLM | **Google Gemini API** |
| Compute | **Google Cloud Run** |
| Database | **Google Cloud SQL** (MySQL 8 or PostgreSQL 15) |
| Object storage | **Google Cloud Storage** |
| Logging | **Google Cloud Logging** |

### 9.2 Recommended Application Stack

Align with implementing team's expertise (Gohorto stack):

| Layer | Technology |
|-------|------------|
| Backend | PHP 8.2+, **Laravel 11+** |
| Frontend | **Vue 3** + **Inertia.js** or Livewire |
| CSS | **Tailwind CSS 4** |
| Build | Vite |
| Queue | Laravel Queue + **Redis** (Memorystore or Cloud Run worker) |
| Cache | Redis |
| Billing | **Stripe** (Laravel Cashier) |
| Email | SendGrid or Mailgun |
| PDF export | DomPDF or Snappy |
| Excel export | Laravel Excel |

### 9.3 Development Tools

| Tool | Purpose |
|------|---------|
| Docker | Local dev matching Cloud Run |
| GitHub Actions | CI/CD to Cloud Run |
| Google AI Studio | Prompt prototyping |
| Cursor | AI-assisted development |

---

## 10. System Architecture

```
┌─────────────────────────────────────────────────────────────────────────┐
│                         Public Internet                                  │
└─────────────────────────────────┬───────────────────────────────────────┘
                                  │ HTTPS
                    ┌─────────────▼─────────────┐
                    │   Cloud Load Balancer      │
                    │   (optional, or Cloud Run  │
                    │    default URL)            │
                    └─────────────┬─────────────┘
                                  │
          ┌───────────────────────┼───────────────────────┐
          │                       │                       │
   ┌──────▼──────┐        ┌───────▼───────┐      ┌───────▼───────┐
   │  Vue/Inertia │        │  Laravel API  │      │  Queue Worker │
   │  (SSR/SPA)   │◀──────▶│  Cloud Run    │─────▶│  Cloud Run    │
   └──────────────┘        └───────┬───────┘      └───────┬───────┘
                                    │                      │
              ┌─────────────────────┼──────────────────────┤
              │                     │                      │
       ┌──────▼──────┐      ┌───────▼───────┐     ┌───────▼───────┐
       │  Cloud SQL  │      │ Cloud Storage │     │  Gemini API   │
       │  (MySQL)    │      │  (PDFs)       │     │  (Google AI)  │
       └─────────────┘      └───────────────┘     └───────────────┘
              │
       ┌──────▼──────┐      ┌───────────────┐
       │    Redis    │      │ Cloud Logging │
       │  (queue)    │      │ + Monitoring  │
       └─────────────┘      └───────────────┘
              │
       ┌──────▼──────┐
       │   Stripe    │
       └─────────────┘
```

### 10.1 Request Flow: Application Submit → Screen

```
1. Applicant submits form + PDF
2. API stores Application (status: submitted)
3. API dispatches ScreenApplicationJob to queue
4. API returns 202 + "Processing"
5. Worker: upload PDF → GCS, extract text
6. Worker: AgentExecutionLogger → intake_received, document_parsed
7. Worker: GeminiScreeningService.screenApplication()
8. Worker: AgentExecutionLogger → gemini_screen_called
9. Worker: store ScreeningResult, update Application (status: screened)
10. Worker: if incomplete → status needs_info + draft email
11. Notify Program Manager (email/in-app)
```

---

## 11. Directory Structure (Recommended)

```
venturelens/
├── app/
│   ├── Http/Controllers/
│   │   ├── Api/
│   │   ├── Applicant/
│   │   ├── Admin/
│   │   └── Auth/
│   ├── Jobs/
│   │   ├── ScreenApplicationJob.php
│   │   ├── GenerateCommitteeReportJob.php
│   │   └── SendFounderEmailJob.php
│   ├── Models/
│   ├── Services/
│   │   ├── Gemini/
│   │   │   ├── GeminiClient.php
│   │   │   ├── GeminiScreeningService.php
│   │   │   └── PromptBuilder.php
│   │   ├── AgentExecutionLogger.php
│   │   ├── DocumentExtractor.php
│   │   └── BillingService.php
│   └── Policies/
├── database/migrations/
├── resources/js/Pages/
│   ├── Dashboard/
│   ├── Applications/
│   ├── Programs/
│   └── Apply/
├── docs/
│   └── evidence/
│       ├── production-dashboard.png
│       ├── gemini-api-logs.png
│       ├── agent-execution-trace.png
│       └── revenue-evidence.pdf
├── docker/
│   └── Dockerfile
├── .github/workflows/deploy.yml
├── README.md
└── LICENSE (MIT recommended for public repo)
```

---

## 12. Data Model

### 12.1 Entity Relationship Overview

```
organizations ──┬── programs ──┬── applications ──┬── screening_results
                │              │                  ├── application_files
                │              │                  └── agent_executions
                │              └── rubrics
                ├── users (via organization_user)
                ├── subscriptions (Stripe)
                └── usage_records
```

### 12.2 Core Tables

#### `organizations`
| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| name | string | |
| slug | string unique | |
| country_code | char(2) | |
| website | string nullable | |
| stripe_customer_id | string nullable | |
| plan | enum | free, cohort, starter, pro |
| screenings_quota | int | |
| screenings_used | int | |
| created_at | timestamp | |

#### `users`
| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| name | string | |
| email | string unique | |
| password | string | |
| email_verified_at | timestamp nullable | |

#### `organization_user`
| Column | Type | Notes |
|--------|------|-------|
| organization_id | FK | |
| user_id | FK | |
| role | enum | owner, manager, reviewer |

#### `programs`
| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| organization_id | FK | |
| name | string | |
| slug | string | |
| description | text nullable | |
| opens_at | datetime | |
| closes_at | datetime | |
| max_applications | int nullable | |
| status | enum | draft, open, closed, archived |
| rubric_id | FK nullable | |

#### `rubrics`
| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| organization_id | FK | |
| name | string | |
| criteria | json | `[{name, weight, description, scoring_guide}]` |
| is_default | boolean | |

#### `applications`
| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| program_id | FK | |
| startup_name | string | |
| founder_name | string | |
| founder_email | string | |
| country_code | char(2) | |
| stage | string | idea, mvp, growth |
| sector | string | |
| form_data | json | all form fields |
| status | enum | draft, submitted, processing, screened, needs_info, shortlisted, accepted, rejected, waitlisted |
| ai_overall_score | decimal nullable | |
| manual_overall_score | decimal nullable | |
| decision_by | FK user nullable | |
| decision_at | timestamp nullable | |
| submitted_at | timestamp nullable | |

#### `application_files`
| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| application_id | FK | |
| type | enum | pitch_deck, supplementary |
| gcs_path | string | |
| original_filename | string | |
| mime_type | string | |
| size_bytes | int | |
| extracted_text | longtext nullable | |

#### `screening_results`
| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| application_id | FK | |
| model | string | e.g. gemini-2.0-flash |
| overall_score | decimal | |
| criterion_scores | json | |
| strengths | json | |
| weaknesses | json | |
| risk_flags | json | |
| summary | text | |
| recommendation | string | |
| raw_response | json | full Gemini response |
| prompt_tokens | int | |
| completion_tokens | int | |
| latency_ms | int | |
| created_at | timestamp | |

#### `agent_executions`
| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| application_id | FK nullable | |
| organization_id | FK | |
| agent_name | string nullable | screening, growth, onboarding, support, finance, success |
| step | string | intake_received, document_parsed, gemini_screen_called, ... |
| decision | string nullable | the business decision the AI made |
| action_taken | string nullable | what the system did as a result |
| autonomy_level | tinyint | 0=observe, 1=suggest, 2=act-with-approval, 3=autonomous |
| confidence | decimal nullable | Gemini-reported/derived confidence 0–1 |
| human_minutes_saved | int nullable | baseline used for "human-hours displaced" |
| status | enum | started, completed, failed |
| metadata | json | |
| created_at | timestamp | |

#### `business_agents` (agent registry & guardrails)
| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| name | string unique | growth, onboarding, support, screening, finance, success |
| enabled | boolean | kill switch |
| autonomy_level | tinyint | max allowed level for this agent |
| daily_action_cap | int | rate/spend guardrail |
| config | json | per-agent settings |

#### `email_drafts`
| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| application_id | FK | |
| decision | string | |
| subject | string | |
| body | text | |
| ai_generated | boolean | |
| approved_by | FK user nullable | |
| sent_at | timestamp nullable | |

#### `usage_records`
| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| organization_id | FK | |
| type | enum | screening, report, email |
| gemini_calls | int | |
| tokens | int | |
| recorded_at | date | |

---

## 13. API Specification

Base URL: `https://api.venturelens.app/v1` or `/api/v1`

### 13.1 Authentication

- `POST /auth/register` — create user + organization
- `POST /auth/login` — returns Sanctum token
- `POST /auth/logout`
- `GET /auth/user`

### 13.2 Programs

- `GET /organizations/{org}/programs`
- `POST /organizations/{org}/programs`
- `GET /programs/{id}`
- `PATCH /programs/{id}`
- `GET /programs/{slug}/apply` — public form config

### 13.3 Applications (Applicant — public)

- `POST /programs/{slug}/applications` — create/submit
- `POST /applications/{id}/files` — upload PDF
- `GET /applications/{id}/status` — applicant status (token-based)

### 13.4 Applications (Admin)

- `GET /programs/{id}/applications` — list with filters
- `GET /applications/{id}` — detail + screening result
- `POST /applications/{id}/decision` — accept/reject/etc.
- `POST /applications/{id}/override-score`
- `POST /applications/{id}/rescreen`
- `POST /applications/{id}/compare` — body: `{application_ids: []}`

### 13.5 Reports & Export

- `GET /programs/{id}/committee-report`
- `GET /programs/{id}/export.csv`
- `GET /applications/{id}/export.pdf`

### 13.6 Operations

- `GET /organizations/{org}/dashboard`
- `GET /organizations/{org}/agent-executions`
- `GET /organizations/{org}/gemini-usage`

### 13.7 Billing

- `POST /billing/checkout` — Stripe session
- `GET /billing/portal` — Stripe customer portal
- `POST /webhooks/stripe`

---

## 14. Core User Flows

### 14.1 Onboarding (Organization Owner)

```
Landing → Sign up → Create organization → Choose plan (or free trial)
→ Create first program → Configure rubric → Share apply link
```

### 14.2 Application + Screening

```
Founder opens /apply/{slug} → Fills form → Uploads deck → Submits
→ Job queued → Gemini screens → Manager notified
→ Manager reviews → Decides → Approves AI email → Founder notified
```

### 14.3 Judge Demo Flow

```
Login demo@venturelens.app → View pre-seeded program with 5+ applications
→ Open application → See AI scores + agent execution log
→ View operations dashboard → Gemini API usage chart
```

**Seed demo data** in `DatabaseSeeder` for judging.

---

## 15. Billing & Subscription

### 15.1 Stripe Products

Create in Stripe Dashboard:

| Product | Price ID env var | Type |
|---------|------------------|------|
| Cohort Package | `STRIPE_PRICE_COHORT` | one-time $199 |
| Starter Monthly | `STRIPE_PRICE_STARTER` | recurring $299/mo |
| Pro Monthly | `STRIPE_PRICE_PRO` | recurring $799/mo |

### 15.2 Quota Enforcement

Before `ScreenApplicationJob` runs:
1. Check `organization.screenings_used < organization.screenings_quota`.
2. If exceeded → block job, notify owner, show upgrade CTA.
3. On successful screen → increment `screenings_used`.

### 15.3 Revenue Tracking (for Devpost)

Maintain `docs/evidence/revenue-tracker.xlsx`:

| Month | Arms-length (USD) | Related-party (USD) | Notes |
|-------|-------------------|---------------------|-------|
| May 2026 | | | |
| June 2026 | | | |
| July 2026 | | | |
| August 2026 | | | |

Export Stripe dashboard monthly → `docs/evidence/revenue-evidence.pdf`.

---

## 16. Google Cloud Deployment

### 16.1 Services to Provision

| Resource | Config |
|----------|--------|
| Cloud Run (web) | 1 vCPU, 512Mi–1Gi, min 0, max 10 |
| Cloud Run (worker) | Same image, command: `php artisan queue:work` |
| Cloud SQL | MySQL 8, db-f1-micro (dev) / db-g1-small (prod) |
| Cloud Storage | Bucket `venturelens-uploads-{env}` |
| Secret Manager | API keys, DB password, Stripe secrets |
| Cloud Logging | Default + structured JSON logs |

### 16.2 Dockerfile (outline)

```dockerfile
FROM php:8.2-fpm-alpine
# Install extensions: pdo_mysql, redis, intl, zip
# Install composer, npm, build assets
# Copy app, run migrations on deploy (or init container)
CMD php artisan serve --host=0.0.0.0 --port=8080
```

### 16.3 CI/CD (GitHub Actions)

```
push main → test → build image → push to Artifact Registry
→ deploy Cloud Run (web + worker) → run migrations
```

### 16.4 Environment Separation

| Env | Purpose |
|-----|---------|
| `local` | Docker Compose |
| `staging` | Pre-prod on Cloud Run |
| `production` | Live demo + judges |

---

## 17. Observability & Evidence Package

### 17.1 Required Evidence Files (for judges)

Place in `docs/evidence/`:

| File | Description |
|------|-------------|
| `production-dashboard.png` | Ops dashboard screenshot |
| `gemini-api-logs.png` | Cloud Logging or in-app usage |
| `agent-execution-trace.png` | Agent execution timeline (all 6 agents, autonomy levels) |
| `ai-operations-dashboard.png` | % decisions by AI + autonomy distribution (🏆) |
| `application-screening-demo.png` | Application detail with AI scores |
| `impact-page.png` | Public `/impact` KPIs screenshot |
| `impact-YYYYMMDD.json` | Machine-readable KPI snapshot (nightly) |
| `revenue-evidence.pdf` | Stripe export or P&L |
| `revenue-tracker.xlsx` | Monthly breakdown May–Aug 2026, arms-length vs related-party |

### 17.2 Structured Logging

Log every Gemini call:

```json
{
  "severity": "INFO",
  "message": "gemini_screen_completed",
  "labels": {
    "application_id": "123",
    "organization_id": "45",
    "model": "gemini-2.0-flash",
    "latency_ms": 2340,
    "prompt_tokens": 1200,
    "completion_tokens": 450
  }
}
```

### 17.3 Admin Route for Evidence Export

`GET /admin/evidence-export` — generates ZIP of screenshots + CSV of agent executions (Super Admin only).

### 17.4 CompetitionMetrics Service & Public `/impact` Page 🏆

To make evidence **live and irrefutable** (not hand-built screenshots), implement a `CompetitionMetrics` service that computes the §3.3 scorecard directly from production data, plus a **public, judge-facing** page at `/impact`.

| ID | Requirement | Priority |
|----|-------------|----------|
| FR-IMP-01 | `CompetitionMetrics` computes all §3.3 KPIs from DB (cached 5 min) | P0 |
| FR-IMP-02 | Public `/impact` page renders KPIs, charts, and live agent feed (read-only) | P0 |
| FR-IMP-03 | `% decisions executed by AI` and autonomy distribution (L0–L3) charts | P0 |
| FR-IMP-04 | `human-hours displaced` and `founder hours saved` counters | P0 |
| FR-IMP-05 | `GET /api/v1/impact.json` returns the same metrics for the evidence pack | P0 |
| FR-IMP-06 | Nightly job snapshots metrics to `docs/evidence/impact-YYYYMMDD.json` (committed) | P1 |

**Impact metric formulas (document in code):**

```
founder_hours_saved        = applications_screened × manual_review_minutes_per_app / 60
human_hours_displaced      = Σ(agent_actions × per_task_human_minutes) / 60
pct_decisions_ai           = ai_decided_actions / total_operational_actions
jobs_influenced (modeled)  = accepted_startups × avg_jobs_per_startup (cite source/assumption)
```

> The `/impact` page doubles as marketing (Growth Agent links to it) **and** as the judges' evidence dashboard. One build, two wins.

### 17.5 Demo & Judge-Readiness Requirements

| ID | Requirement | Priority |
|----|-------------|----------|
| FR-DEMO-01 | Seeder creates a realistic org with 1 program + 8–12 screened applications | P0 |
| FR-DEMO-02 | Seeder creates agent_executions across all 6 agents at varied autonomy levels | P0 |
| FR-DEMO-03 | README "Judge Quickstart": URL, demo creds, 5 things to click in 3 min | P0 |
| FR-DEMO-04 | "Replay" button that triggers a live screening so judges see Gemini run in real time | P1 |

---

## 18. Security & Privacy

| Area | Requirement |
|------|-------------|
| Transport | TLS 1.2+ only |
| Auth | Laravel Sanctum; bcrypt passwords |
| Authorization | Policies per organization; no cross-tenant data leak |
| Files | GCS signed URLs for upload/download; virus scan (ClamAV optional P2) |
| PII | Encrypt founder email at rest (optional P1); redact in logs |
| Rate limiting | 60 req/min per IP on public apply endpoints |
| CSRF | Enabled on web routes |
| Stripe webhooks | Verify signature |
| Secrets | Google Secret Manager; never commit `.env` |

### 18.1 Data Retention

- Applications: retained per organization settings (default 2 years).
- Agent logs: 1 year.
- Right to deletion: organization owner can delete application data (GDPR).

---

## 19. Internationalization

| Language | UI | Gemini input | Gemini output |
|----------|-----|--------------|---------------|
| English | P0 | P0 | P0 |
| Arabic | P1 | P0 | P0 |
| Turkish | P1 | P0 | P0 |

Gemini prompts must instruct: *"Evaluate content in its original language. Return summary in English unless program locale is ar/tr."*

---

## 20. Environment Variables

```env
# App
APP_NAME=VentureLens
APP_URL=https://venturelens.app
APP_ENV=production

# Database (Cloud SQL)
DB_CONNECTION=mysql
DB_HOST=
DB_DATABASE=venturelens
DB_USERNAME=
DB_PASSWORD=

# Redis
REDIS_HOST=

# Google Cloud
GOOGLE_CLOUD_PROJECT_ID=
GOOGLE_CLOUD_STORAGE_BUCKET=
GOOGLE_APPLICATION_CREDENTIALS=/secrets/gcp-key.json

# Gemini
GEMINI_API_KEY=
GEMINI_MODEL_FLASH=gemini-2.0-flash
GEMINI_MODEL_PRO=gemini-2.5-pro

# Stripe
STRIPE_KEY=
STRIPE_SECRET=
STRIPE_WEBHOOK_SECRET=
STRIPE_PRICE_COHORT=
STRIPE_PRICE_STARTER=
STRIPE_PRICE_PRO=

# Email
MAIL_MAILER=smtp
MAIL_HOST=
MAIL_FROM_ADDRESS=noreply@venturelens.app

# Demo (judges)
DEMO_USER_EMAIL=demo@venturelens.app
DEMO_USER_PASSWORD=
```

---

## 21. Implementation Phases

### Phase 1 — Foundation (Week 1)
- [ ] Laravel + Vue + Inertia scaffold
- [ ] Auth, organizations, users, RBAC
- [ ] Programs + rubrics CRUD
- [ ] Cloud Run + Cloud SQL deploy (hello world)

### Phase 2 — Core Screening (Week 2)
- [ ] Public apply form + file upload (GCS)
- [ ] GeminiScreeningService + ScreenApplicationJob
- [ ] AgentExecutionLogger
- [ ] Application list + detail (admin)

### Phase 3 — Workflow + Revenue (Week 3)
- [ ] Decision workflow + score override
- [ ] AI email drafting + approval send
- [ ] Operations dashboard + Gemini usage
- [ ] Stripe cohort package **live** + first paying customer 🏆

### Phase 4 — AI-Native Operations (Week 4–5) 🏆 DECISIVE
- [ ] `business_agents` registry + guardrails (§6.4.1)
- [ ] Growth Agent (A1) — automated outreach + content
- [ ] Onboarding Agent (A2) — auto rubric/program setup
- [ ] Support Agent (A3) — in-app/email answers
- [ ] Autonomy ladder logging (L0–L3) on agent_executions
- [ ] AI Operations dashboard (agents, autonomy mix, % decisions by AI)

### Phase 5 — Evidence & Scale (Week 6–7)
- [ ] `CompetitionMetrics` service + public `/impact` page (§17.4) 🏆
- [ ] Finance Agent (A5) + Success Agent (A6)
- [ ] Subscriptions (Starter/Pro)
- [ ] Committee report + PDF export
- [ ] Demo seeder across all agents + "replay" live screening
- [ ] Landing page + pricing

### Phase 6 — Submission (Week 8–10)
- [ ] Pilot customers + revenue tracking (arms-length focus)
- [ ] 3+ public testimonials
- [ ] Demo video < 3 min (Appendix F script)
- [ ] Evidence pack complete (§23)
- [ ] Final Devpost submission **by Aug 15** (48h buffer before Aug 17)

---

## 22. Testing Requirements

| Type | Coverage |
|------|----------|
| Unit | GeminiScreeningService (mocked), PromptBuilder, quota logic |
| Feature | Application submit → job → screening result |
| API | Auth, applications CRUD, decision endpoints |
| E2E | Playwright: apply flow + admin review flow |
| Manual | Judge demo script documented in README |

### 22.1 Test Accounts

```
demo@venturelens.app — Program Manager, pre-seeded data
applicant@test.com — Sample founder (optional)
```

---

## 23. Devpost Submission Checklist

**Paste-ready field copy:** [`docs/commercialization/DEVPOST_SUBMISSION.md`](commercialization/DEVPOST_SUBMISSION.md) (orientation session aligned).

**Product & compliance**
- [ ] Category: Entrepreneurship & Job Creation
- [ ] GitHub repo public (MIT) or shared with `testing@devpost.com` + `judging@hacker.fund`
- [ ] ≥1 Gemini API call per application (logged)
- [ ] At least one Google Cloud product in production
- [ ] Pre-existing Gohorto boilerplate disclosed

**AI-Native Operations evidence (🏆 decisive)**
- [ ] ≥4 of 6 business agents live in production (§6.4)
- [ ] AI Operations dashboard screenshot (`docs/evidence/agent-execution-trace.png`)
- [ ] Autonomy distribution (L0–L3) shown; ≥30% at L2–L3
- [ ] `% of operational decisions executed by AI` reported

**Business viability evidence**
- [ ] `docs/evidence/revenue-evidence.pdf` (Stripe export)
- [ ] Revenue fields: total, by month (May–Aug), related-party separate
- [ ] No single customer > 40% of revenue (confirm checkbox)
- [ ] User counts (registered orgs, applicants)
- [ ] 3+ public verifiable testimonials (with links)

**Category impact evidence**
- [ ] Public `/impact` page live with KPIs (§17.4)
- [ ] `docs/evidence/impact-YYYYMMDD.json` snapshot committed
- [ ] Founder hours saved + jobs-influenced model documented

**Demo & judging**
- [ ] `docs/evidence/production-dashboard.png` + `gemini-api-logs.png`
- [ ] Demo URL + credentials + "Judge Quickstart" in README
- [ ] Video < 3 min on YouTube (Appendix F script)
- [ ] Submit by **Aug 15** (48h buffer)

---

## 24. Out of Scope (v1)

- Full multi-tenant subdomain isolation (single DB with org_id is sufficient)
- Mentor matching post-selection (Phase 2 product)
- Mobile native apps
- Integration with full Gohorto platform
- Custom SSO/SAML (enterprise)
- White-label branding per organization
- Non-Google LLM providers in production
- Real-time collaborative review (Google Docs style)

---

## 25. Glossary

| Term | Definition |
|------|------------|
| **Program** | A cohort or application cycle (e.g., "Spring 2026 Accelerator") |
| **Rubric** | Weighted evaluation criteria passed to Gemini |
| **Screening** | Automated AI evaluation of an application |
| **Arms-length revenue** | Payment from unrelated third-party customers |
| **Related-party revenue** | Payment from BINA, Gohorto, team, or existing relationships |
| **Agent execution** | Logged step in the AI operations pipeline |

---

## Appendix A: Default Rubric Template

```json
{
  "name": "General Startup Evaluation",
  "criteria": [
    {
      "name": "Team",
      "weight": 25,
      "description": "Founder experience, complementary skills, commitment",
      "scoring_guide": "1=no relevant experience, 10=exceptional proven team"
    },
    {
      "name": "Market Opportunity",
      "weight": 25,
      "description": "Problem significance, market size, timing",
      "scoring_guide": "1=unclear problem, 10=large validated opportunity"
    },
    {
      "name": "Traction",
      "weight": 25,
      "description": "Revenue, users, pilots, partnerships",
      "scoring_guide": "1=no validation, 10=strong measurable traction"
    },
    {
      "name": "Innovation",
      "weight": 25,
      "description": "Differentiation, defensibility, scalability",
      "scoring_guide": "1=commodity idea, 10=highly differentiated"
    }
  ]
}
```

---

## Appendix B: Landing Page Copy

**Headline:** Screen startup applications with Gemini — in minutes, not weeks.

**Subhead:** VentureLens helps incubators and accelerators evaluate founders faster with AI-powered screening, committee reports, and automated feedback.

**CTA:** Start free — 5 screenings included.

---

## Appendix C: README Sections for GitHub

The implementing project README must include:

1. Project description
2. Hackathon compliance statement (Gemini + Google Cloud)
3. Pre-existing work disclosure (Gohorto boilerplate)
4. Local setup instructions
5. Environment variables
6. Deploy to Cloud Run guide
7. **Judge access:** demo URL, credentials, what to click
8. License (MIT)

---

## Appendix D: Go-To-Market & Revenue Acceleration Playbook 🏆

The fastest path to **arms-length revenue** inside the 90-day window. Optimized for a single-call close.

### D.1 Ideal Customer Profile (ICP)

| Attribute | Sweet spot |
|-----------|-----------|
| Org type | University incubators, regional accelerators, NGO/donor entrepreneurship programs |
| Size | 1–3 staff running selection; 50–500 applications/cohort |
| Pain | Open call happening in the next 60 days |
| Budget authority | Program director can approve < $300 without procurement |
| Geography | Turkey, MENA, UK, Sub-Saharan Africa, South/SE Asia |

### D.2 Distribution Channels (ranked by speed)

1. **Warm partner referrals (arms-length!)** — BINA/ecosystem *introduces* us to *independent* programs in its 9-country network. The introduced program is a new, unrelated customer → **arms-length revenue**. (BINA's own payment = related-party.)
2. **Direct outreach** via Growth Agent (A1) to program directors on LinkedIn/email.
3. **"Open call" timing** — target programs with application deadlines in June–August.
4. **Content + `/impact` page** — public proof drives inbound.
5. **Incubator associations / networks** (e.g., regional accelerator alliances).

### D.3 Offer Design (single-call close)

- **Hook:** "Screen your next cohort with AI — free for your first 5 applications, $199 for the whole cohort."
- **Risk reversal:** money-back if not faster than manual.
- **Urgency:** "Set up before your deadline; live in 15 minutes."
- **Expansion:** convert cohort buyers → Starter/Pro after first successful cohort (Success Agent A6).

### D.4 Pricing Psychology

| Plan | Price | Anchored against |
|------|-------|------------------|
| Cohort | $199 | One afternoon of staff time |
| Starter | $299/mo | Cost of a single bad cohort selection |
| Pro | $799/mo | Far below enterprise incubation suites ($1k–$5k/mo) |

### D.5 30-60-90 Revenue Plan

| Window | Focus | Target |
|--------|-------|--------|
| Days 1–30 | Ship MVP, 2–3 design-partner pilots (arms-length), first $ | $300–$600 |
| Days 31–60 | Referral engine + Growth Agent at scale; convert pilots to paid | $2k–$4k cumulative |
| Days 61–90 | Expansion + testimonials + `/impact` polish for judges | $4k–$12k cumulative |

### D.6 Revenue Integrity (for judges)

- Every Stripe charge auto-classified by Finance Agent (A5) as **arms-length** or **related-party**.
- No single customer > 40% of revenue (matches Devpost confirmation checkbox).
- Keep `docs/evidence/revenue-evidence.pdf` (Stripe export) + monthly `revenue-tracker.xlsx`.

---

## Appendix E: Category Impact — Theory of Change 🏆

Category: **Entrepreneurship & Job Creation**. Judges reward *redefinition* or *credible scale*. We argue both.

### E.1 Logic Model

```
INPUTS            ACTIVITIES              OUTPUTS                 OUTCOMES                 IMPACT
Gemini API   →  AI screens every    →  Applications      →  Faster, fairer       →  More & better
Cloud infra     application            screened; hours      selection; founders     founders funded
Domain rubrics  AI drafts reports/     saved; programs      get timely feedback;    → startups survive
Partner reach   comms; AI runs ops     enabled              programs scale w/o      → JOBS CREATED
                                                            adding staff
```

### E.2 Quantified Impact Model (compute on `/impact`)

| Metric | Formula / source | Why it matters |
|--------|------------------|----------------|
| Applications screened | count(screening_results) | Direct activity |
| Founder hours saved | apps × manual_minutes/60 | Efficiency for ecosystem |
| Program staff hours saved | agent actions × baseline | Operational leverage |
| Programs enabled | count(orgs with ≥1 cohort) | Reach |
| Countries reached | distinct org country | Geographic breadth |
| Founders given timely feedback | apps with decision < 7 days | Fairness/equity |
| **Jobs influenced (modeled)** | accepted_startups × avg_jobs_per_startup | Category headline (state assumptions + citation) |

### E.3 Equity & Fairness Narrative

- **Access:** brings AI screening to programs that can't afford enterprise tools — especially MENA/Africa/South Asia.
- **Bias mitigation:** consistent rubric scoring + explainable reasoning + human override reduces single-reviewer bias.
- **Transparency:** founders get structured feedback instead of silent rejection.

### E.4 Scale Story (credible path)

20,000+ startup ecosystem reach via partners → 1,000+ programs addressable → each program screens hundreds/year → **hundreds of thousands of founders evaluated fairly**, compounding into measurable job creation.

---

## Appendix F: Demo Video Script & Live Pitch Narrative 🏆

Judges weight the **< 3 min video** heavily. Structure for maximum criteria coverage.

### F.1 Video Script (target 2:45)

| Time | Scene | Message | Criterion hit |
|------|-------|---------|---------------|
| 0:00–0:15 | Founder/program pain | "Incubators drown in applications; founders wait weeks." | Category |
| 0:15–0:45 | Live screening | Upload deck → **Gemini scores live** → report appears | AI ops + product |
| 0:45–1:15 | AI Operations dashboard | Show 6 agents acting; autonomy distribution; % decisions by AI | **AI-Native (decisive)** |
| 1:15–1:45 | Business proof | Stripe revenue, paying customers, `/impact` KPIs | Business viability |
| 1:45–2:15 | Customer testimonial | Real program director (public link) | Viability + impact |
| 2:15–2:45 | Vision + close | "An AI-operated company expanding fair startup selection worldwide." | All three |

### F.2 One-Liner & Elevator Pitch

- **One-liner:** *"VentureLens is an AI-operated company that makes AI-powered startup selection accessible to every incubator on earth."*
- **30-sec:** problem → product (Gemini screening) → **AI runs the whole business** → real revenue → category impact + scale.

### F.3 Live Finals Q&A Prep (anticipated judge questions)

| Question | Crisp answer |
|----------|--------------|
| "Is this new or just Gohorto?" | New product, new repo/brand/customers post-May 19; Gohorto is disclosed prior domain experience. |
| "How is AI more than a feature?" | 6 production agents run sales, onboarding, support, screening, finance, success — show the dashboard. |
| "Is your revenue arms-length?" | Yes — Finance Agent classifies each charge; related-party reported separately; no customer > 40%. |
| "What's defensible?" | Domain rubrics, evaluation data flywheel, partner distribution, AI-ops cost structure (Appendix G). |
| "Can it scale?" | Serverless on Cloud Run; agents scale ops without headcount; partner reach to 1,000+ programs. |

---

## Appendix G: Differentiation & Moat

| Layer | Moat |
|-------|------|
| **Data flywheel** | Every screening + human override improves rubric/prompt quality; proprietary evaluation dataset grows. |
| **Domain depth** | Incubator-specific rubrics, workflows, multi-stakeholder UX (from years of Gohorto experience). |
| **AI-ops cost structure** | Agents run sales/support/finance → near-zero marginal opex → undercut incumbents profitably. |
| **Distribution** | Warm reach into a 9-country, 20,000-startup ecosystem. |
| **Expansion path** | Land on screening → expand to cohort ops → upgrade to Gohorto (portfolio synergy). |
| **Trust/explainability** | Transparent, overridable AI scoring builds buyer trust vs black-box tools. |

**Competitive landscape:** generic form tools (Typeform/Google Forms) have no AI judgment; enterprise incubation suites are expensive and slow to adopt; generic AI chat tools lack domain rubrics and workflow. VentureLens occupies the **AI-native, incubator-specific, affordable** quadrant.

---

## Appendix H: Risk Register (Competition-Critical)

| Risk | Impact | Likelihood | Mitigation |
|------|--------|-----------|------------|
| Not enough arms-length revenue | Low viability score | Med | Start sales Day 1; partner referrals to independent programs; design-partner pilots |
| Eligibility (org > 25 employees) | Disqualification | Med | Register as Team/Individual or <25-employee entity; confirm before submit |
| "AI is just a feature" perception | Low AI-native score | Med | Build ≥4 of 6 business agents; show autonomy distribution on dashboard |
| Gemini cost/latency at volume | Ops/UX | Low | Flash for high volume; caching; batching; spend caps |
| Weak/no testimonial | Lower credibility | Med | Success Agent requests testimonial post-win; secure ≥3 public ones |
| Demo fails for judges | Lost points | Low | Seeded demo data + "replay" live screening + recorded fallback video |
| Related-party revenue too high | Viability discounted | Med | Prioritize net-new programs; Finance Agent split; keep < 40% per customer |
| Submission incompleteness | Hard fail | Low | Use §23 checklist; submit 48h early (Aug 15) |

---

## Appendix I: Phase 0 — Competition Build Priorities (read first)

If time is constrained, build in this exact order to maximize score per hour:

1. **Core screening loop in production** (FR-AI-*, Cloud Run, Gemini) — passes Stage 1 gate.
2. **Stripe live + first paying customer** (§15) — proves viability.
3. **2+ business agents live** (Growth A1 + Support A3 or Onboarding A2) — proves AI-native ops.
4. **`/impact` page + CompetitionMetrics** (§17.4) — turns work into evidence.
5. **2 more agents** (Finance A5, Success A6) — deepens AI-native story.
6. **Demo video + testimonials + evidence pack** (Appendix F, §23).

> Rule of thumb: a feature only matters if it (a) earns revenue, (b) lets AI run the business, or (c) generates judge-visible evidence. If it does none of these, defer it.

---

*End of document. Implement according to priority labels: P0 = must have for MVP/submission, P1 = should have, P2 = nice to have. Sections marked 🏆 are the highest-leverage for winning — do not cut them.*
