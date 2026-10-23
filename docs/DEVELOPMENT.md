# Development

## Requirements

- Docker Desktop. Laravel Sail runs PHP 8.5 and PostgreSQL 18 in containers.
- Node 24 on the host. Vite runs outside Docker.

## First run

```
cp .env.example .env
docker run --rm -u "$(id -u):$(id -g)" -v "$(pwd):/var/www/html" -w /var/www/html laravelsail/php84-composer:latest composer install --ignore-platform-reqs
./vendor/bin/sail up -d
./vendor/bin/sail artisan key:generate
./vendor/bin/sail artisan migrate --seed
npm install
```

The seeder creates the demo catalogue and, outside production, an `admin@boutique.test` / `password` user. It is idempotent (`updateOrCreate` by slug).

## Daily

```
./vendor/bin/sail up -d
npm run dev
./vendor/bin/sail artisan queue:work --timeout=180 --tries=1
./vendor/bin/sail artisan schedule:work
```

- `npm run dev` and `queue:work` each need their own terminal.
- Restart the worker after changing `.env` or PHP code: it reads config once at start-up.
- `schedule:work` is optional; it runs `try-on:prune` hourly.
- The app is at http://localhost, the wizard at `/try-on`.

## Try-on settings (`.env`)

| Variable                      | Default                  | Notes                                                                                              |
| ----------------------------- | ------------------------ | -------------------------------------------------------------------------------------------------- |
| `TRYON_PROVIDER`              | `mock`                   | `mock` \| `openai` \| `auto`. See [ARCHITECTURE.md](ARCHITECTURE.md#provider-modes-tryon_provider) |
| `TRYON_OPENAI_MODEL`          | `gpt-image-2.5-sunburst` | Prices per model in `config/tryon.php` → `openai.pricing`                                          |
| `TRYON_OPENAI_INPUT_FIDELITY` | empty                    | `high` keeps faces closer; gpt-image-1 / 1.5 only                                                  |
| `TRYON_MOCK_DELAY`            | `3`                      | Seconds, to see the waiting animation                                                              |
| `TRYON_DISK`                  | `local`                  | `s3` in production                                                                                 |
| `DB_QUEUE_RETRY_AFTER`        | `200`                    | Must be greater than the worker `--timeout`                                                        |

## Commands

```
./vendor/bin/sail artisan catalog:generate-images
./vendor/bin/sail artisan try-on:compare latest --model=gpt-image-2.5-sunburst --model=gpt-image-1.5:high --quality=medium
./vendor/bin/sail artisan try-on:prune
```

- `catalog:generate-images` creates the AI catalogue and fabric swatches. It is paid (≈ $1 one-off) and asks for the key as a hidden prompt.
- `try-on:compare` A/B-tests models on one request. It is paid too, and writes its output to `storage/app/private/compare/<id>/`.
- `try-on:prune` deletes expired images and closes stuck requests.

## Tests and checks

```
./vendor/bin/sail artisan test
npx vp test
npx vp check
npm run types:check
```

- `sail artisan test` runs PHPUnit against a separate `testing` database.
- `vp test` runs Vitest: pure TS, composables, the FSD boundary test and the i18n key check.
- `vp check` runs lint and format; `npx vp fmt` fixes formatting.
- `types:check` runs `vue-tsc --noEmit`.

## Troubleshooting

| Symptom                                                  | Cause                                                                 | Fix                                                                                                                                              |
| -------------------------------------------------------- | --------------------------------------------------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------ |
| Result is still a mock after switching to `openai`       | The queue worker is long-lived and kept the old config                | `sail artisan queue:restart`, then start the worker again                                                                                        |
| Styles and scripts blocked by CSP on localhost           | Node resolved `localhost` to `[::1]`, which the CSP cannot allow-list | `vite.config.ts` pins `server.host: '127.0.0.1'`; check that `public/hot` says `http://127.0.0.1:5173`                                           |
| `php: command not found` from Wayfinder in `npm run dev` | The Wayfinder Vite plugin calls `php artisan` on the host             | It is configured to run through Sail, so Sail must be up. Tests skip it (`process.env.VITEST`)                                                   |
| Port clash on 5173                                       | Sail also forwards the Vite port                                      | `VITE_PORT=5199` in `.env`, then `sail up -d`                                                                                                    |
| `zsh: no such service: #`                                | zsh passes `# comment` as an argument                                 | Don't paste inline comments, or `setopt interactivecomments`                                                                                     |
| `sail php -v` shows PHP < 8.5.12                         | PHP2Shell (CVE-2026-109618/109619) fix not yet built for arm64        | `docker/php/99z-php2shell.ini` applies the interim mitigation; rebuild with `sail build --no-cache` and remove the ini once 8.5.12+ is installed |
