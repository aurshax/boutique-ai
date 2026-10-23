import type { Quality } from './types';

/** Generation quality — affects cost and time (config/tryon.php → estimate_usd). */
export const QUALITY_OPTIONS: { value: Quality; labelKey: string }[] = [
    { value: 'medium', labelKey: 'tryOn.quality.medium' },
    { value: 'high', labelKey: 'tryOn.quality.high' },
];
