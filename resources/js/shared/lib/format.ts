// Ko'rsatish uchun formatlash — loyihadagi yagona manba. Pure funksiyalar, Vue'ga bog'liq emas.

/** 4357000 → "4 357 000" (minglik ajratuvchi — oddiy bo'shliq, so'm uchun odatiy yozuv). */
export function formatUzs(amount: number): string {
    return Math.round(amount)
        .toString()
        .replace(/\B(?=(\d{3})+(?!\d))/g, ' ');
}

/** 130000 → "+130k", 0 → "0" — tkan ustamasi kabi qisqa belgilar uchun. */
export function formatShortSurcharge(amount: number): string {
    if (amount <= 0) return '0';
    return amount >= 1000 ? `+${Math.round(amount / 1000)}k` : `+${amount}`;
}

/** 0.05 → "$0.05"; digits — kasr xonalari. */
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
