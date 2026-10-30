# Development Phases (instructions for AI coding agents)

This file is the ordered work plan. `PROJECT_BRIEF.md` is the spec (stack, schema, algorithms, routes). Read **both** fully before doing anything.

> **Note:** `PROJECT_BRIEF.md` is not part of this repository. Until it is written, treat this file plus the existing migrations and enums as the specification, and keep engine numbers in `config/stylist.php`.


---

## Operating rules (apply to every phase)

1. **One phase at a time.** Do only the phase the human names. Do not start the next phase, and do not "get ahead" by creating files for later phases.
2. **Plan before coding.** At the start of a phase, post a short plan (files you will create or change, in order). Wait for a go-ahead only if something in the plan departs from the brief.
3. **Stack is fixed:** Laravel 12, Inertia.js, Vue 3, Tailwind, MySQL, session auth from the starter kit. Code must run on **PHP 8.2**. No PHP 8.3+ syntax or packages. No Sanctum, no Vue Router, no Pinia.
4. **No new packages** beyond those in the brief (`intervention/image`, Pest) without asking first.
5. **Security:** every query is scoped to the authenticated user; use policies or scoped relations. Validate all input with Form Requests. Never expose API keys to the frontend, logs, or Git.
6. **Business logic lives in `app/Services`**, not in controllers or Vue components. Tunable numbers live in `config/stylist.php`.
7. **Tests are part of the work.** A phase is not done until its tests exist and pass. Actually run `php artisan test` and `npm run build` and report the real output. Never claim a test passed without running it.
8. **Small, reviewable commits** (one logical change each, clear messages). Do not rewrite or reformat files you were not asked to touch.
9. **If the brief is ambiguous or contradicts this file, stop and ask** instead of guessing.
10. **End every phase with the report below.** Then stop and wait.

### Phase report template (post this when the phase is done)

```
Phase N report
- Summary: (2 to 4 sentences)
- Files created / changed: (list)
- Migrations: (names, and whether they were run)
- Commands run and results: php artisan test, npm run build (paste the final lines)
- Deviations from the brief: (or "none")
- Open questions / risks: (or "none")
- How to try it manually: (3 to 6 steps)
```

---

## Phase 0: Foundation

**Goal:** a clean, booting Laravel 12 + Inertia + Vue app on MySQL, running on PHP 8.2, with the navigation shell ready.

**Prerequisite:** the human has already created the project from the Vue starter kit (see PROJECT_BRIEF section 2).

**Tasks**
- [ ] Confirm `composer.json` has `"config": {"platform": {"php": "8.2.0"}}` and that `composer install` works under it.
- [ ] Confirm `.env` uses MySQL; `.env` is in `.gitignore`; provide `.env.example` with the keys the project will need (DB, `AI_PROVIDER`, `AI_MODEL`, provider key placeholders, `AI_DAILY_LIMIT`).
- [ ] Remove starter-kit demo content that is not needed; keep auth, settings, layouts.
- [ ] Rename the app (name in `.env.example`, page titles, sidebar/header).
- [ ] Create `config/stylist.php` with empty, commented sections: `fit`, `silhouette`, `scoring`, `ai`.
- [ ] Add a navigation shell with placeholder pages: Dashboard, Wardrobe, Measurements, Outfits, Wear Log.
- [ ] Add the base route structure in `routes/web.php` inside the `auth` group.

**Done when**
- Register, login and logout work; a logged-in user sees the dashboard with navigation to all placeholder pages.
- `php artisan test` and `npm run build` pass.

**Paste-ready prompt:** "Read PROJECT_BRIEF.md and DEVELOPMENT_PHASES.md. Do Phase 0 only. Post your plan first, then implement, then post the phase report and stop."

---

## Phase 1: Measurements

**Goal:** the user can record and edit body measurements and see the current ones.

**Tasks**
- [ ] Migration, `Measurement` model (casts to decimal/date), `User::measurements()` and `User::currentMeasurement()` helpers.
- [ ] `StoreMeasurementRequest` / `UpdateMeasurementRequest` with sane ranges (for example height 50 to 250 cm; chest, waist, hips 30 to 250 cm; waist cannot be absurdly larger than hips). Chest, waist, hips and height are required; others optional.
- [ ] `MeasurementPolicy` (owner only).
- [ ] `MeasurementController` (`index`, `store`, `update`) returning Inertia pages and redirects with flash messages.
- [ ] Page `Measurements/Index.vue`: current measurement summary, history table, add/edit form.
- [ ] Dashboard receives the current measurement as a prop and shows it.
- [ ] Tests: create, update, validation failures, another user's measurement returns 403/404, history ordering.

**Done when:** I can save and edit measurements, the dashboard shows the latest, and all tests pass.

---

## Phase 2: Wardrobe CRUD

**Goal:** the user can add, edit, filter and delete clothing items with photos.

**Tasks**
- [ ] Backed enums for category, pattern, fit type, stretch, season (PHP 8.2 compatible).
- [ ] `items` migration, `Item` model (JSON casts for `seasons` and `cut_tags`), factory, `ItemPolicy`.
- [ ] `StoreItemRequest` / `UpdateItemRequest` (valid enum values, hex colors, formality 1 to 5, garment measurements as positive decimals, image rules: jpg/png/webp, max 5 MB).
- [ ] `PhotoService` using Intervention Image v3 with the GD driver: store a max-1200px image and a 300px thumbnail on the `public` disk; delete files when the item or photo is replaced or deleted.
- [ ] `ItemController` (`index` with filters by category, season, color family and search; `create`, `store`, `edit`, `update`, `destroy`).
- [ ] Pages: `Wardrobe/Index.vue` (photo grid, filters, empty state), `Wardrobe/Form.vue` shared by create and edit (photo preview, color picker, multi-selects for seasons and cut tags, garment measurement inputs that are clearly marked optional).
- [ ] Tests: CRUD, validation, upload and thumbnail creation, file cleanup on delete, filters, cross-user access.

**Done when:** I can add 5 items with photos, filter them, edit and delete them, and a second user cannot see or change my items.

---

## Phase 3: Fit and silhouette

**Goal:** each item shows how well it fits the user, and the dashboard shows the body shape label.

**Tasks**
- [ ] Fill `config/stylist.php` (`fit` and `silhouette` sections) with the values from PROJECT_BRIEF section 4.1 and 4.2.
- [ ] `FitService`: pure class, no database access. Input: a measurement and an item. Output: a result object with `score` (nullable), `label` (`good_fit`, `acceptable`, `tight`, `loose`, `unknown`), and the per-pair eases.
- [ ] `SilhouetteService`: classify shape, expose favored and avoided cut tags, and score an item.
- [ ] Wire both into the Items props (fit label and score per item) and the Dashboard props (shape label and short explanation).
- [ ] UI: fit badge on each item card, a fit breakdown on the edit page, a shape card on the dashboard. Show "add garment measurements for a precise fit" when the label is unknown.
- [ ] Unit tests (table-driven datasets) covering: each ease range boundary, stretch factors, missing measurements, dress and outerwear handling, inseam check and the cropped/shorts skip, every shape label and the evaluation order.

**Done when:** the unit tests cover every rule in the brief, and badges and the shape card render correctly with real data.

---

## Phase 4: Rule-based outfit generator

**Goal:** generate, view and save outfits without any AI.

**Tasks**
- [ ] Fill the `scoring` section of `config/stylist.php` (weights, thresholds).
- [ ] `outfits` and `outfit_items` migrations, models, `OutfitPolicy`, factories.
- [ ] `ColorHarmony` class (hex to HSL, neutrals, analogous, complementary, triadic, over-many-colors penalty, pattern clash penalty).
- [ ] `OutfitScorer` (the weighted factors from the brief; variety factor uses a stub that returns full score until Phase 6).
- [ ] `OutfitGenerator`: hard filters, combination building (top + bottom or dress, shoes, optional outerwear and accessory), scoring, top-N with de-duplication. Cap the combination count so generation stays fast on large wardrobes.
- [ ] `OutfitController`: `generate` (JSON, session auth), `store`, `index`, `update`, `destroy`, `favorite`.
- [ ] Pages: `Outfits/Generate.vue` (occasion, season, temperature, count; result cards with photos, score and per-factor breakdown; save button), `Outfits/Index.vue` (saved outfits, favorites filter).
- [ ] Tests: unit tests for `ColorHarmony`, `OutfitScorer` and each hard filter; feature tests for `generate` and saving; handle the "not enough items" case with a clear message.

**Done when:** with a seeded wardrobe, generating for "casual, summer" returns up to 5 sensible, distinct outfits, and saved outfits persist.

---

## Phase 5: Optional AI mode (free-tier models)

**Goal:** an AI mode that improves on the rule engine and never breaks the app.

**Tasks**
- [ ] Fill `config/ai.php` (provider, model, key, timeout, daily limit, cache TTL), all from `.env`.
- [ ] `AiProvider` interface with `generate(array $payload): array`; implement `GeminiProvider`, `GroqProvider`, `OpenRouterProvider` using Laravel's `Http` client. Confirm each provider's current request format from its official documentation before coding it, because formats and free-tier limits change. Do not guess model names.
- [ ] `AiOutfitService`: build the compact payload (pre-filtered candidates, shape label only, no raw measurements, no photos), call the provider, parse and validate the JSON, check every `item_id` against the candidate list, enforce slot rules, drop invalid outfits.
- [ ] Fallback to the rule generator on timeout, HTTP 429, invalid JSON, or zero valid outfits; the response includes `fallback: true` and a short reason.
- [ ] Per-user daily cap (cache or table), short-term response cache keyed by payload hash, error logging without keys or full prompts.
- [ ] UI: mode toggle (Rules / AI) on the generator page, AI reasoning shown on cards, a visible note when a fallback happened, and a Settings section showing whether an AI key is configured (never display the key).
- [ ] Tests with `Http::fake`: success, malformed JSON, hallucinated item IDs, 429, timeout, daily cap reached, cache hit. Assert that no key ever appears in a response.

**Done when:** all failure paths fall back cleanly, and with a real free-tier key the human can generate AI outfits end to end.

---

## Phase 6: Wear log, variety, PWA

**Goal:** track what was worn, avoid repeats, and make the app installable on a phone.

**Tasks**
- [ ] `wear_log` and `wear_log_items` migrations, models, `WearLogPolicy`, controller (`index`, `store`).
- [ ] Replace the Phase 4 variety stub: penalize items worn in the last N days (config) and exact repeated combinations.
- [ ] Pages: `WearLog/Index.vue` with a month calendar, a "wore this today" action on saved outfits, and recently worn indicators in the wardrobe.
- [ ] PWA: web manifest, icons, a minimal service worker that caches static assets only (never authenticated pages or API/JSON responses), and an install prompt that does not nag.
- [ ] Tests: logging, variety penalty effect on ranking, cross-user access.

**Done when:** logging an outfit changes the next generation, and the app can be installed from a phone browser.

---

## Phase 7: Hardening, seed data and deployment

**Goal:** production-ready on cPanel with PHP 8.2.

**Tasks**
- [ ] Security pass: confirm every controller path is user-scoped and policy-protected, CSRF works on all forms, upload validation is strict, no secrets in the repo, rate limiting on login and generation routes.
- [ ] Performance pass: eager-load relations (no N+1 in item, outfit and wear-log pages), check indexes from the brief, paginate long lists.
- [ ] Seeder/factory that creates a realistic demo wardrobe of about 30 items and one measurement set, for local testing.
- [ ] Friendly error pages and flash/validation messaging review.
- [ ] README: local setup, environment variables, running tests, how to switch AI provider, and the cPanel deployment checklist from PROJECT_BRIEF section 8 (including the fallback for blocked symlinks).
- [ ] Final run: `php artisan test`, `npm run build`, `composer install --no-dev --optimize-autoloader` in a clean copy, and confirm the app boots under PHP 8.2.

**Done when:** a clean checkout can be deployed by following only the README, and the demo seeder produces a wardrobe on which every feature works.

---

## Phase gate checklist (for the human, after each report)

- [ ] I read the migrations and they match the brief.
- [ ] I ran `php artisan test` and `npm run build` myself.
- [ ] I tried the feature manually in the browser, including on a phone.
- [ ] I checked that nothing uses PHP 8.3+ only features.
- [ ] I committed the phase before starting the next one.