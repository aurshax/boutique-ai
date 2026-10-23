// Display formatting — the single source in the project. Pure functions, no Vue.

const integer = new Intl.NumberFormat('en-US', { maximumFractionDigits: 0 });

/** 4357000 → "4,357,000" (amount only; the UI adds the currency: t('common.uzs', { amount })). */
export function formatUzs(amount: number): string {
    return integer.format(Math.round(amount));
}

/** 130000 → "+130k", 0 → "0" — short labels such as a fabric surcharge. */
export function formatShortSurcharge(amount: number): string {
    if (amount <= 0) return '0';
    return amount >= 1000 ? `+${Math.round(amount / 1000)}k` : `+${amount}`;
}

/** 0.05 → "$0.05"; digits = decimal places. */
export function formatUsd(amount: number, digits = 2): string {
    return `$${amount.toFixed(digits)}`;
}

/** { medium: 0.05, high: 0.09 } → "$0.05–0.09" */
export function formatUsdRange(min: number, max: number): string {
    return `$${min.toFixed(2)}–${max.toFixed(2)}`;
}

/** 75 → "1:15" */
export function formatDuration(totalSeconds: number): string {
    const s = Math.max(0, Math.floor(totalSeconds));
    return `${Math.floor(s / 60)}:${String(s % 60).padStart(2, '0')}`;
}
