# Deployment

The public deployment is a demo: visitors get a free mock result, or bring their own OpenAI key for a real one. The server never stores a key and never pays for generation.

## Environment

Use `.env.production.example` as the template. Most hosts (Laravel Cloud, Forge) take these values as environment variables in their dashboard. Never commit the real file.

| Setting | Development (`.env.example`) | Production |
|---|---|---|
| `APP_ENV` / `APP_DEBUG` | `local` / `true` | `production` / `false` |
| `LOG_LEVEL` | `debug` | `warning` (`LOG_STACK=stderr` on Laravel Cloud) |
| `DB_*` | Sail PostgreSQL | Managed PostgreSQL, `DB_SSLMODE=require` |
| `SESSION_ENCRYPT`, `SESSION_SECURE_COOKIE` | `false` | `true` |
| `TRUSTED_PROXIES` | — | `*` behind the platform's load balancer (correct HTTPS URLs and client IPs for rate limits) |
| `AUTH_REGISTRATION` | `true` | `false` — no sign-ups on the demo |
| Seeded admin | created | not created (`app()->isProduction()`) |
| `TRYON_PROVIDER` | `mock` / `openai` | `auto` |
| `TRYON_DISK` | `local` | `s3` (private bucket) |

## Storage

Container platforms lose the local disk on every deploy, so customer photos go to a **private** S3-compatible bucket: Laravel Cloud object storage, Cloudflare R2 or AWS S3. Images are served through 10-minute signed URLs (`temporaryUrl()`).

```
composer require league/flysystem-aws-s3-v3
```

Set `TRYON_DISK=s3` and the `AWS_*` variables; `AWS_ENDPOINT` is needed for R2. On a VPS with a persistent disk, `TRYON_DISK=local` also works.

## Every deploy

```
composer install --no-dev --optimize-autoloader
npm ci
npm run build
php artisan migrate --force
php artisan optimize
php artisan queue:restart
```

On an empty database, seed the catalogue once:

```
php artisan db:seed --class=CatalogSeeder --force
```

## Processes

- **Queue worker:** `php artisan queue:work --timeout=180 --tries=1`
- **Scheduler:** `php artisan schedule:run` every minute. It runs `try-on:prune` hourly. On Laravel Cloud, enable the Scheduler; on a VPS, use cron.

## Pre-launch checklist

- [ ] PHP is patched: **8.5.12+ / 8.4.27+ / 8.3.36+** (PHP2Shell, CVE-2026-109618 / CVE-2026-109619). Check with `php -v`
- [ ] `APP_DEBUG=false` and `APP_KEY` is set
- [ ] `AUTH_REGISTRATION=false`, and there is no test admin in the database
- [ ] `TRYON_PROVIDER=auto`, and a visit without a key returns a labelled mock result
- [ ] Uploads land in the private bucket, and their URLs are signed and expire
- [ ] The queue worker and the scheduler are running
- [ ] `php artisan test` and `npx vp test` are green
- [ ] HTTPS is on, and there are no CSP errors in the browser console
