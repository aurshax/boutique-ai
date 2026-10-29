# Fitzaro Front — AI virtual try-on web app

Shoppers see clothes on their own photo before they buy. This is the web app and live demo of **Fitzaro**, the AI virtual try-on that also ships as a WooCommerce plugin ([aurshax/fitzaro](https://github.com/aurshax/fitzaro)). New try-on features are built and tested here in a full web app first, then ported to WordPress.

The demo today is the **Tailoring** module for menswear shops: a visitor uploads a full-length photo, enters their height and usual size, picks a look (trousers, shirt or blazer, accessories) and a fabric, and gets a photo of themselves wearing it, plus a suggested size and the total price. The catalogue, prices and fabrics come from the shop's own data; the AI only does the fitting.

![Try-on flow: choose a look, wait for the AI, compare before and after, swap the fabric](docs/screenshots/05-try-on-flow.gif)

| Choose a look and fabric                              | Result with size and price                | Same photo, another fabric                          |
| ----------------------------------------------------- | ----------------------------------------- | --------------------------------------------------- |
| ![Choose a look](docs/screenshots/02-choose-look.jpg) | ![Result](docs/screenshots/03-result.jpg) | ![Fabric swap](docs/screenshots/04-fabric-swap.jpg) |

Screenshots are from a real run with the OpenAI Images API (about $0.07 per image). The model photo is from Unsplash, see [credits](public/media/CREDITS.md). To retake them: `scripts/capture-screenshots.mjs` (see [Development](docs/DEVELOPMENT.md)).

## Highlights

- **AI try-on** via the OpenAI Images API (`/v1/images/edits`): the customer photo plus catalogue product images go in as references. The output keeps the input aspect ratio, and the prompt locks face, pose and framing.
- **Suits vs separates.** In a suit look the chosen fabric applies to both jacket and trousers (and so does the surcharge); in separates it applies to the trousers only. The same rule is implemented and tested in PHP and TypeScript.
- **Fabric swap without re-uploading.** The result screen re-tailors the same photo in another fabric.
- **Bring your own key, public demo mode.** `TRYON_PROVIDER=auto`: without a key visitors get a free mock result labelled "demo"; with their own OpenAI key they get the real thing, charged to them. The server never stores a key.
- **Cost transparency.** An estimate is shown before every generation, the real cost is computed from the API's `usage`, and there are no automatic retries.
- **Privacy.** EXIF/GPS is stripped by re-encoding, images sit on a private disk behind short-lived signed URLs, and uploads are pruned after 24 h (results after 7 days).
- **Accessible before/after slider.** A native range input over the image: drag, tap, arrow keys and screen readers work, and the result is shown only once it has loaded.

## Roadmap

Fitzaro Front is moving to the same try-on engine as the WordPress plugin 0.1.0:

- **Catalogue:** tops, dresses and eyewear with colour/style variants, one prompt per product type. Tailoring (suits and fabrics) stays as a module behind a config flag.
- **Who pays:** a store-owner key with a monthly budget, next to today's bring-your-own-key mode.
- **Limits:** per visitor, IP address and site, checked before a job is queued.
- **Owner controls and privacy:** an admin screen to turn try-on on or off per product and category, personal data export and erasure.

## Stack

| Layer    | Tech                                                                                                             |
| -------- | ---------------------------------------------------------------------------------------------------------------- |
| Backend  | Laravel 13, PHP 8.5, PostgreSQL 18, database queue, Fortify                                                      |
| Frontend | Inertia 3, Vue 3 (Composition API, `<script setup>`), TypeScript, Tailwind CSS 4, shadcn-vue, motion-v, vue-i18n |
| Tooling  | Laravel Sail (Docker), Vite+ (`vp test` / `vp check`), Wayfinder (typed routes), PHPUnit, Vitest                 |

## Architecture

**Backend** (`app/TryOn`):

- `TryOnProvider` contract with `MockProvider` and `OpenAiProvider`, selected by `ProviderFactory` (`mock` | `openai` | `auto`).
- `TryOnJob` runs on the queue with an encrypted payload, `tries=1` and an atomic status transition.
- Helpers: `PromptBuilder`, `ImageService` (validation, re-encoding, storage), `SizeSuggester`, and `OpenAiErrors` (maps API errors to user-facing messages).

**Frontend** (`resources/js`) follows [Feature-Sliced Design](https://feature-sliced.design/):

```
app → pages → widgets → features → entities → shared
```

- Imports only go down the layers, and other slices are reached only through their `index.ts` public API. `app/architecture.test.ts` enforces both rules in CI.
- Business logic lives in pure TypeScript (`entities/*/model`, `features/*/model`), reactivity in composables, and components only render.
- The try-on wizard is a small state machine (`widgets/try-on-wizard/model/flow.ts`).

**Security:**

- CSP with a per-request nonce, and the API key is accepted only in the `X-OpenAI-Key` header (stripped by middleware before logging).
- Requests are owned by the session and use UUIDs.
- Rate limits, and only status codes of OpenAI errors are logged.

## Run locally

Requirements: Docker Desktop and Node 24.

```
git clone https://github.com/aurshax/boutique-ai.git fitzaro-front
cd fitzaro-front
cp .env.example .env
docker run --rm -u "$(id -u):$(id -g)" -v "$(pwd):/var/www/html" -w /var/www/html laravelsail/php85-composer:latest composer install
./vendor/bin/sail up -d
./vendor/bin/sail artisan key:generate
./vendor/bin/sail artisan migrate --seed
npm install
npm run dev
```

Then:

- start the queue worker in another terminal: `./vendor/bin/sail artisan queue:work --timeout=180 --tries=1`;
- open http://localhost — the try-on page is at `/try-on`.

By default `TRYON_PROVIDER=mock` (free, no key). Set it to `auto` or `openai` to generate real images with your own OpenAI key.

**Tests:**

```
./vendor/bin/sail artisan test
npx vp test
npx vp check
```

## Deployment

See [`docs/DEPLOY.md`](docs/DEPLOY.md) and `.env.production.example`: an S3-compatible private bucket for photos, a queue worker and the scheduler (`try-on:prune`), `AUTH_REGISTRATION=false` and `TRUSTED_PROXIES`.

## Docs

- [Architecture](docs/ARCHITECTURE.md) — request flow, data model, provider modes, frontend layers, security
- [Development](docs/DEVELOPMENT.md) — local setup, commands, troubleshooting
- [Deployment](docs/DEPLOY.md) — production settings and checklist

## Credits and licence

- Code: [MIT](LICENSE).
- Photos and videos in `public/media/hero` and `public/media/fabrics/fabric-*`: [Unsplash License](https://unsplash.com/license) and [Pexels License](https://www.pexels.com/license/). Authors and sources are listed in [`public/media/CREDITS.md`](public/media/CREDITS.md). They are **not** covered by the MIT licence.
- Catalogue product images and fabric swatches (`public/media/catalog`, `public/media/fabrics/swatch-*`) are AI-generated for this demo.
- Built on the [Laravel Vue starter kit](https://github.com/laravel/vue-starter-kit) (MIT).
- The WordPress plugin [aurshax/fitzaro](https://github.com/aurshax/fitzaro) is a separate project under GPL-2.0-or-later.
