# Architecture

## Scope

A virtual fitting room for menswear boutiques.

- **What the customer does:** uploads a full-length photo, enters height and usual size, then picks a style, a ready-made look and a fabric.
- **What the system returns:** a photo of the customer wearing the look, a suggested size from the shop's size charts, and the price of every item and of the whole look.
- **Who pays for the AI:** the visitor, with their own OpenAI key (BYOK). Without a key, the public demo returns a mock result.

## Request flow

```
Vue wizard ──POST /try-on/requests (multipart, key in X-OpenAI-Key)──▶ TryOnController
   ▲                                                                        │ validate (CreateTryOnRequest)
   │                                                                        │ ImageService: sniff MIME, re-encode, strip EXIF/GPS, resize to 1536 px
   │                                                                        │ insert try_on_requests (queued) + dispatch TryOnJob
   │  202 {id, status, suggested_size}                                       ▼
   │                                                          queue:work (database queue)
   │                                                                        │ atomic claim: queued → running
   │                                                                        │ ProviderFactory → MockProvider | OpenAiProvider
   │                                                                        │ PromptBuilder (versioned) + person photo + product images + fabric swatch
   │                                                                        │ POST /v1/images/edits → base64 image, usage → cost
   │                                                                        ▼
   └──GET /try-on/requests/{uuid} every 2 s ◀── done: result_url (signed, 10 min) | failed: message
```

- **Cancel:** "Stop" only stops waiting. An OpenAI call that has already been sent cannot be cancelled and is still billed, and the UI says so. A cancelled request discards the result but records the cost.
- **Fabric swap:** creates a new request with `source_request_id`. The stored photo is reused, nothing is uploaded again.

## Provider modes (`TRYON_PROVIDER`)

| Mode     | Behaviour                                        | `keyMode` prop |
| -------- | ------------------------------------------------ | -------------- |
| `mock`   | Free placeholder result, no key field            | `none`         |
| `openai` | Real generation, the visitor's key is required   | `required`     |
| `auto`   | Public demo: mock without a key, OpenAI with one | `optional`     |

The request stores the provider that actually ran it, and the result screen labels mock output as a demo.

## Suits vs separates

- `looks.is_suit = true`: the chosen fabric applies to the jacket **and** the trousers. That covers the surcharge, the availability check and the prompt ("matching suit").
- Otherwise ("separates") the fabric applies to the trousers only.
- One rule, two implementations, both tested: `Look::followsFabric()` (PHP) and `followsFabric()` in `entities/catalog` (TypeScript).

## Endpoints

| Method | URL                       | Response                                                                |
| ------ | ------------------------- | ----------------------------------------------------------------------- |
| GET    | `/`                       | Home: styles (look count, cheapest look), fabric showcase               |
| GET    | `/try-on`                 | Wizard: catalogue, sizes, upload limits, `keyMode`, cost estimate       |
| POST   | `/try-on/requests`        | 202 + request JSON. Rate limit: 5/min and 100/day per IP                |
| GET    | `/try-on/requests/{uuid}` | Status JSON; `result_url` when done. Rate limit: 120/min                |
| DELETE | `/try-on/requests/{uuid}` | 204. Images deleted immediately; unfinished requests become `cancelled` |

Request IDs are UUIDs, and a session only sees its own requests: anyone else's ID returns 404.

## Data model (PostgreSQL)

| Table                        | Key columns                                                                                                                                       |
| ---------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------- |
| `styles`                     | slug, name, description, cover_image, sort                                                                                                        |
| `products`                   | type (`CHECK` trousers / shirt / blazer / belt / tie / shoes), name, description, price_uzs, image, default_fabric_id                             |
| `fabrics` + `product_fabric` | name, composition, pattern, swatch_image; per-product `extra_price_uzs`                                                                           |
| `looks` + `look_items`       | style_id, name, is_suit; ordered products per look                                                                                                |
| `size_charts`                | product_id, size, height and waist ranges (`int4range`)                                                                                           |
| `try_on_requests`            | uuid, status, height_cm (`CHECK` 120–230), size, params (`jsonb`), provider, model, prompt_version, cost_usd, error; partial index on queued rows |

Never stored: the OpenAI key, the customer's name or phone number.

## Reliability and cost

```
queue:work --timeout=180 --tries=1
database.retry_after = 200                    # must exceed the worker timeout
OpenAiProvider: Http::timeout(170)->connectTimeout(10)
```

- **No retries:** a retry would charge the visitor twice.
- **Price shown up front:** the estimate comes from `config/tryon.php`, the actual cost from the API's `usage`. Prices are per model and per 1M tokens.
- **Error messages:** `OpenAiErrors` maps API errors (invalid key, no credit, rate limit, moderation...) to user-facing messages. Fatal errors stop batch commands early.

## Security

- **The key:**
    - kept in the browser (`sessionStorage`, or `localStorage` when the visitor opts in) and sent only in the `X-OpenAI-Key` header;
    - the header is stripped by middleware;
    - the job payload is encrypted (`ShouldBeEncrypted`);
    - only status/code/type of OpenAI errors are logged.
- **CSP:** a per-request nonce, no inline or third-party scripts, no `v-html`.
- **Uploads:**
    - the MIME type is read from the bytes (JPEG / PNG / WebP, ≤ 10 MB, pixel limit);
    - images are re-encoded, which drops EXIF/GPS, and stored under UUID names;
    - they live on a private disk behind signed URLs;
    - TTL: 24 h for uploads, 7 days for results (`try-on:prune`, hourly).
- **Prompt injection:** no free text reaches the prompt. Height is numeric and bounded, and size comes from a fixed list.

## Frontend — Feature-Sliced Design

```
resources/js/
├── app/        wiring: main.ts, layouts, providers (i18n, directives, toasts), styles, architecture.test.ts
├── pages/      Inertia pages, composition only (home/HomePage, try-on/TryOnPage, auth, settings)
├── widgets/    page blocks: site-header/footer, home-hero, home-sections, try-on-wizard (flow state machine)
├── features/   one user action per slice: byok-api-key, upload-photo, enter-measurements, select-look,
│               select-fabric, generate-try-on (submit 202 → poll → result)
├── entities/   business objects: catalog (Style → Look → Product → Fabric, pricing), try-on-request (types, API)
└── shared/     no business knowledge: api (fetch + XSRF), i18n, lib (format, storage, composables), ui
```

**Rules** (enforced by `app/architecture.test.ts`, which runs with `npx vp test`):

- Imports only go down the layers.
- Slices on the same layer never import each other.
- Another slice is reached only through its `index.ts`.

**Conventions:**

- **Logic stays out of `.vue` files:** calculations are pure TypeScript, reactivity lives in composables, components render and emit.
- **Copy (vue-i18n):** UI copy lives in `shared/i18n/locales/en.ts`. Pure functions return message keys and the UI translates them. Backend messages live in `lang/en/*.php`.
- **State:** wizard state is provided once (`useTryOnFlow`) and injected by the step components. No global store, because the state belongs to one page.
- **Backend limits** (MIME, size) arrive as props and are never duplicated on the client.
- **Routes** come from Wayfinder (typed); no hand-written URLs.
- **Motion:** `motion-v` inside `<MotionConfig reduced-motion="user">`. With reduced motion, the hero shows a poster instead of the video.

## Design tokens

Brand tokens live in `resources/js/app/styles/tokens.css`:

- colours: `ink`, `chalk`, `paper`, `slate`, `fog`, `mist`, `thread`;
- fonts: `font-display` (Bodoni Moda), `font-body` (Figtree), `font-mono` (JetBrains Mono), loaded from Bunny Fonts in `vite.config.ts`.

Brand button variants are in `shared/ui/button`.
