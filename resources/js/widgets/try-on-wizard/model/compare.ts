// Before/after comparison: the same rules as the WordPress plugin's slider (assets/view.js → slide, setPosition).
// The divider position lives in the --position CSS variable, so styles alone decide how it is drawn.

/** Where the divider starts: in the middle, like the plugin. */
export const COMPARE_START = 50;

/** Full-length frame (2:3) used until the result's real size is known. */
export const FALLBACK_RATIO = 2 / 3;

/** Range input value → 0–100. Anything that is not a number counts as 0. */
export function clampPercent(value: unknown): number {
    const n = Number(value);
    if (!Number.isFinite(n)) return 0;
    return Math.min(100, Math.max(0, n));
}

/**
 * Frame size and divider position. The frame keeps the result's own aspect ratio (no cropping) and fits
 * the viewport height, so a full-length photo shows the feet too.
 */
export function compareFrameStyle(
    ratio: number | null,
    position: number,
): Record<string, string> {
    const r = ratio ?? FALLBACK_RATIO;
    return {
        aspectRatio: String(r),
        width: `min(100%, calc(82svh * ${r}))`,
        '--position': `${clampPercent(position)}%`,
    };
}
