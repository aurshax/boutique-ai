import type { Style } from '@/entities/catalog';
import type { Quality } from '@/entities/try-on-request';
import type { UploadLimits } from '@/features/upload-photo';

/** pages/try-on/TryOnPage.vue props (TryOnController::create). Shakl Feature testda tekshiriladi. */
export type TryOnPageProps = {
    styles: Style[];
    sizes: string[];
    provider: string;
    requiresApiKey: boolean;
    estimateUsd: Record<Quality, number>;
    upload: UploadLimits;
};
