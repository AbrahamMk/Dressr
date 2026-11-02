# Dressr

Personal wardrobe, body-fit feedback, and rule-based outfit suggestions. Built with Laravel 12, Inertia.js, Vue 3, Tailwind, and MySQL. Targets **PHP 8.2**.

## Local setup

1. Clone the repo and install PHP dependencies (platform locked to 8.2):

```bash
composer install
cp .env.example .env
php artisan key:generate
```

2. Configure MySQL in `.env` (`DB_*`). Create an empty database (default name in `.env.example` is `wardrobe`).

3. Install front-end deps and build assets:

```bash
npm install
npm run build
# or for local Vite: npm run dev
```

4. Migrate (and optionally seed a demo wardrobe):

```bash
php artisan migrate
php artisan db:seed
php artisan storage:link
```

5. Serve:

```bash
php artisan serve
```

Demo login after seeding: `demo@dressr.test` / `password`.

## Environment variables

| Key | Purpose |
|-----|---------|
| `APP_NAME` | Shown in titles (Dressr) |
| `DB_*` | MySQL connection |
| `FILESYSTEM_DISK` | Usually `local`; photos use the `public` disk |
| `AI_PROVIDER` | Optional Phase 5 — leave empty for rules only |
| `AI_MODEL` | Optional free-tier model id |
| `AI_DAILY_LIMIT` | Cap AI calls per user per day |
| `AI_TIMEOUT` / `AI_CACHE_TTL` | AI HTTP behaviour |
| `GEMINI_API_KEY` / `GROQ_API_KEY` / `OPENROUTER_API_KEY` | Provider secrets (never expose to the front end) |

Tunable fit, silhouette and scoring numbers live in `config/stylist.php` (not secrets).

## Photo analysis (wardrobe)

On **Add / Edit item**, take or import a photo and tap **Analyse photo**.

- With `AI_PROVIDER` + API key: Gemini or OpenRouter vision suggests name, category, colours, pattern, seasons, etc.
- Without a key: local colour sampling only (you still fill the rest).

```bash
AI_PROVIDER=gemini
AI_MODEL=gemini-3.8-flash
GEMINI_API_KEY=your_key_here
```

Or OpenRouter:

```bash
AI_PROVIDER=openrouter
AI_MODEL=google/gemini-3.8-flash
OPENROUTER_API_KEY=your_key_here
```

If analysis says the model is unavailable, update `AI_MODEL` to whatever Google AI Studio currently lists as free Flash (often `gemini-3.8-flash`). A “busy / high demand” message means retry shortly — still free.

Suggestions never auto-save — always review the form. Daily cap: `AI_DAILY_LIMIT`.

## Switching AI provider

1. Set `AI_PROVIDER` to `gemini` or `openrouter` (leave empty for local-only colour).
2. Set `AI_MODEL` from that provider’s current docs.
3. Put the key in the matching `*_API_KEY` env var.

Never commit real keys. `.env` is gitignored. Outfit AI (Phase 5) is still optional/separate.

## Tests and build

```bash
php artisan test
npm run build
```

Production-ish PHP install check:

```bash
composer install --no-dev --optimize-autoloader
```

## Features (by phase)

- Measurements + wardrobe CRUD with photos
- Fit badges and body-shape silhouette
- Rule-based outfit generator, save / favorite
- Wear log calendar (variety penalty on generation)
- Installable PWA (static asset cache only)

## cPanel deployment checklist

1. Upload the project (or git pull) outside the public web root if possible; point the domain/document root at `/public`.
2. PHP version **8.2**. Enable `gd`, `mbstring`, `pdo_mysql`, `tokenizer`, `xml`, `ctype`, `json`, `fileinfo`, `openssl`.
3. Copy `.env.example` → `.env`, set `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL`, and MySQL credentials. Run `php artisan key:generate` once.
4. On the server (SSH or terminal):

```bash
composer install --no-dev --optimize-autoloader
npm ci && npm run build
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

5. **Storage link**

```bash
php artisan storage:link
```

If the host **blocks symlinks**, use a fallback instead of `storage:link`:

- Create a real directory `public/storage`.
- Either copy uploaded files from `storage/app/public` into `public/storage` on deploy, **or** change the `public` disk root in `config/filesystems.php` to `public_path('storage')` and ensure that folder is writable.
- Keep `storage/app` and `bootstrap/cache` writable by the web user (`755`/`775` as your host requires).

6. Schedule (optional): add a cron entry for `php artisan schedule:run` every minute if you use scheduled jobs later.
7. Confirm `/up` health route responds, register/login works, and photo uploads land under the public storage path.
8. Do **not** deploy `.env` via git; do **not** leave `APP_DEBUG=true` in production.

## PWA notes

- Manifest: `/manifest.webmanifest`
- Service worker: `/sw.js` caches `/build/*` and icons only — never HTML or JSON API responses
- Install prompt appears once and respects dismissal in `localStorage`
