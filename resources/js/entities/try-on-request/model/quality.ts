import type { Quality } from './types';

/** Generatsiya sifati — narx va vaqtga ta'sir qiladi (config/tryon.php → estimate_usd). */
export const QUALITY_OPTIONS: { value: Quality; label: string }[] = [
    { value: 'medium', label: 'Medium' },
    { value: 'high', label: 'High' },
];
