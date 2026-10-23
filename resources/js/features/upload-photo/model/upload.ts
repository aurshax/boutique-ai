// Browser-side pre-check before upload. Limits come from the backend (config/tryon.php → props.upload),
// so no numbers or MIME lists are hard-coded here. The real check still happens on the server (ImageService).
import type { UploadLimits } from './types';

export type FileLike = Pick<File, 'type' | 'size'>;

/** On failure returns an i18n key + params; the UI translates it. */
export type UploadCheck =
    | { ok: true }
    | {
          ok: false;
          reason: 'type' | 'size';
          messageKey: string;
          params: Record<string, string | number>;
      };

const MIME_LABELS: Record<string, string> = {
    'image/jpeg': 'JPEG',
    'image/png': 'PNG',
    'image/webp': 'WebP',
};

/** ['image/jpeg','image/png','image/webp'] → "JPEG, PNG or WebP" (the conjunction is translated by the caller). */
export function describeAllowed(limits: UploadLimits, or = 'or'): string {
    const names = limits.allowed_mimes.map((m) => MIME_LABELS[m] ?? m);
    return names.length > 1
        ? `${names.slice(0, -1).join(', ')} ${or} ${names.at(-1)}`
        : (names[0] ?? '');
}

export function maxMegabytes(limits: UploadLimits): number {
    return Math.round(limits.max_kb / 1024);
}

export function checkUpload(
    file: FileLike,
    limits: UploadLimits,
    or = 'or',
): UploadCheck {
    if (!limits.allowed_mimes.includes(file.type)) {
        return {
            ok: false,
            reason: 'type',
            messageKey: 'tryOn.photo.wrongType',
            params: { formats: describeAllowed(limits, or) },
        };
    }
    if (file.size > limits.max_kb * 1024) {
        return {
            ok: false,
            reason: 'size',
            messageKey: 'tryOn.photo.tooLarge',
            params: { mb: maxMegabytes(limits) },
        };
    }
    return { ok: true };
}

/** For <input accept="..."> */
export const acceptAttribute = (limits: UploadLimits): string =>
    limits.allowed_mimes.join(',');
