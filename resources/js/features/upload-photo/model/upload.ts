// Rasm yuklashdan oldingi brauzer tekshiruvi. Limitlar backend'dan keladi (config/tryon.php → props.upload),
// shuning uchun bu yerda raqam yoki MIME ro'yxati yozilmaydi. Haqiqiy tekshiruv baribir serverda (ImageService).
import type { UploadLimits } from './types';

export type FileLike = Pick<File, 'type' | 'size'>;

export type UploadCheck =
    | { ok: true }
    | { ok: false; reason: 'type' | 'size'; message: string };

const MIME_LABELS: Record<string, string> = {
    'image/jpeg': 'JPEG',
    'image/png': 'PNG',
    'image/webp': 'WebP',
};

export function describeAllowed(limits: UploadLimits): string {
    const names = limits.allowed_mimes.map((m) => MIME_LABELS[m] ?? m);
    return names.length > 1
        ? `${names.slice(0, -1).join(', ')} yoki ${names.at(-1)}`
        : (names[0] ?? '');
}

export function maxMegabytes(limits: UploadLimits): number {
    return Math.round(limits.max_kb / 1024);
}

export function checkUpload(file: FileLike, limits: UploadLimits): UploadCheck {
    if (!limits.allowed_mimes.includes(file.type)) {
        return {
            ok: false,
            reason: 'type',
            message: `Faqat ${describeAllowed(limits)} rasm.`,
        };
    }
    if (file.size > limits.max_kb * 1024) {
        return {
            ok: false,
            reason: 'size',
            message: `Rasm ${maxMegabytes(limits)} MB dan oshmasligi kerak.`,
        };
    }
    return { ok: true };
}

/** <input accept="..."> uchun */
export const acceptAttribute = (limits: UploadLimits): string =>
    limits.allowed_mimes.join(',');
