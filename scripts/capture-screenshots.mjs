// Captures the README / portfolio screenshots and the try-on flow video from a running app.
//
//   BASE_URL=http://localhost PHOTO=path/to/full-length.jpg OPENAI_KEY=sk-... node scripts/capture-screenshots.mjs
//
// - BASE_URL    the running app (default http://localhost — Sail).
// - PHOTO       full-length photo to upload. Use a licensed stock photo or an AI-generated model, never a real customer.
// - OPENAI_KEY  only when TRYON_PROVIDER=openai: put into sessionStorage like the key form does (it stays in the
//               browser and is sent only to your own app). Each run makes two real generations (~$0.15).
// - OUT         output folder (default docs/screenshots).
//
// Needs Playwright (npx playwright install chromium) and, for the MP4/GIF, ffmpeg on PATH.
// The masked key in the header is hidden in every frame.
import { execFileSync } from 'node:child_process';
import {
    existsSync,
    mkdirSync,
    readdirSync,
    renameSync,
    rmSync,
} from 'node:fs';
import { join, resolve } from 'node:path';
import { chromium } from 'playwright';

const BASE_URL = (process.env.BASE_URL ?? 'http://localhost').replace(
    /\/$/,
    '',
);
const PHOTO = process.env.PHOTO;
const KEY = process.env.OPENAI_KEY ?? '';
const OUT = resolve(process.env.OUT ?? 'docs/screenshots');
const KEY_STORAGE = 'boutique.openai_key';

if (!PHOTO || !existsSync(PHOTO)) {
    console.error('Set PHOTO to a full-length photo (JPEG, PNG or WebP).');
    process.exit(1);
}
mkdirSync(OUT, { recursive: true });

const pause = (page, ms) => page.waitForTimeout(ms);

// Pages are laid out at 1344 px (the result and its details side by side) and saved at 3/4 of that,
// so the layout matches a laptop screen while the files stay small.
const WIDE = { width: 1344, height: 1008 };
const SCALE = 0.75;

/** Screenshot from the top of the page (the context renders at SCALE, so the file is 3/4 size). */
async function shot(page, name) {
    await page.evaluate(() => window.scrollTo(0, 0));
    await pause(page, 400);
    await page.screenshot({ path: join(OUT, name), type: 'jpeg', quality: 86 });
}

/** Hides the masked API key badge (sk-…1234) in the header. */
async function hideKey(page) {
    await page.addStyleTag({
        content:
            'header div:has(> .font-mono) { visibility: hidden !important; }',
    });
}

async function openTryOn(context) {
    const page = await context.newPage();
    if (KEY)
        await page.addInitScript(
            ([k, v]) => sessionStorage.setItem(k, v),
            [KEY_STORAGE, KEY],
        );
    await page.goto(`${BASE_URL}/try-on`);
    await hideKey(page);
    return page;
}

/** Waits for the result screen and for the result image to be revealed. */
async function waitForResult(page) {
    await page
        .getByRole('slider', { name: /Compare/ })
        .waitFor({ timeout: 180_000 });
    await page.evaluate(() => window.scrollTo({ top: 0, behavior: 'smooth' }));
    await pause(page, 1200);
}

/** Drags the before/after divider across the frame, for the video. */
async function sweep(page, from, to, steps = 30) {
    const box = await page
        .getByRole('slider', { name: /Compare/ })
        .boundingBox();
    const y = box.y + box.height / 2;
    await page.mouse.move(box.x + box.width * from, y);
    await page.mouse.down();
    await page.mouse.move(box.x + box.width * to, y, { steps });
    await page.mouse.up();
}

async function setDivider(page, percent) {
    await page.getByRole('slider', { name: /Compare/ }).fill(String(percent));
}

async function fillSetup(page) {
    await page.locator('input[type=file]').first().setInputFiles(PHOTO);
    await pause(page, 700);
    await page.getByRole('button', { name: /Navy Knit/ }).click();
    await page.getByRole('checkbox').first().check();
    await pause(page, 500);
}

async function swapTo(page, fabric) {
    await page.getByRole('radio', { name: new RegExp(fabric) }).click();
    await pause(page, 600);
    await page
        .getByRole('complementary')
        .getByRole('button', { name: /\$/ })
        .click();
}

const browser = await chromium.launch();

// 1. Home page hero.
{
    const page = await browser.newPage({
        viewport: { width: 1440, height: 723 },
    });
    await page.goto(BASE_URL + '/');
    await pause(page, 2500);
    await page.screenshot({
        path: join(OUT, '01-home.jpg'),
        type: 'jpeg',
        quality: 86,
    });
    await page.close();
}

// 2–4. Setup, result and fabric swap, recorded as a video at the same time.
const videoDir = join(OUT, '_video');
rmSync(videoDir, { recursive: true, force: true });
const context = await browser.newContext({
    viewport: WIDE,
    deviceScaleFactor: SCALE,
    recordVideo: {
        dir: videoDir,
        size: { width: WIDE.width * SCALE, height: WIDE.height * SCALE },
    },
});
const page = await openTryOn(context);
await pause(page, 1200);
await fillSetup(page);
await shot(page, '02-choose-look.jpg');
await page.evaluate(() => window.scrollTo({ top: 0, behavior: 'smooth' }));

await page.getByRole('button', { name: /Dress me/ }).click();
await waitForResult(page);
await sweep(page, 0.5, 0.15);
await sweep(page, 0.15, 0.85, 45);
await setDivider(page, 50);
await pause(page, 600);
await shot(page, '03-result.jpg');

await swapTo(page, 'Grey check');
await waitForResult(page);
await sweep(page, 0.5, 0.2);
await sweep(page, 0.2, 0.8, 40);
await setDivider(page, 50);
await pause(page, 1500);
await shot(page, '04-fabric-swap.jpg');
await context.close();
await browser.close();

const [webm] = readdirSync(videoDir).filter((f) => f.endsWith('.webm'));
renameSync(join(videoDir, webm), join(OUT, '05-try-on-flow.webm'));
rmSync(videoDir, { recursive: true, force: true });
console.log(`Saved screenshots and 05-try-on-flow.webm in ${OUT}`);

// MP4 (Upwork, LinkedIn) in real time, and a 1.5× faster, smaller GIF for the README. Skipped when ffmpeg is not installed.
try {
    const src = join(OUT, '05-try-on-flow.webm');
    execFileSync('ffmpeg', [
        '-y',
        '-loglevel',
        'error',
        '-i',
        src,
        '-c:v',
        'libx264',
        '-preset',
        'veryfast',
        '-crf',
        '23',
        '-pix_fmt',
        'yuv420p',
        '-movflags',
        '+faststart',
        '-an',
        join(OUT, '05-try-on-flow.mp4'),
    ]);
    execFileSync('ffmpeg', [
        '-y',
        '-loglevel',
        'error',
        '-i',
        src,
        '-vf',
        'setpts=PTS/1.5,fps=8,scale=640:-1:flags=lanczos,split[a][b];[a]palettegen=max_colors=96:stats_mode=diff[p];[b][p]paletteuse=dither=bayer:bayer_scale=5:diff_mode=rectangle',
        join(OUT, '05-try-on-flow.gif'),
    ]);
    rmSync(src);
    console.log('Converted to 05-try-on-flow.mp4 and 05-try-on-flow.gif');
} catch {
    console.log('ffmpeg not found — convert 05-try-on-flow.webm yourself.');
}
