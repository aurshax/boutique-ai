import type { Style } from '@/entities/catalog';
import type { Quality } from '@/entities/try-on-request';
import type { KeyMode } from '@/features/byok-api-key';
import type { UploadLimits } from '@/features/upload-photo';

/** pages/try-on/TryOnPage.vue props (TryOnController::create). The shape is checked by a Feature test. */
export type TryOnPageProps = {
    styles: Style[];
    sizes: string[];
    keyMode: KeyMode;
    estimateUsd: Record<Quality, number>;
    upload: UploadLimits;
};
