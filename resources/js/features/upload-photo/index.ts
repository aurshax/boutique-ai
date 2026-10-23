// Public API — the only entry point into this slice from other slices.
export type { UploadLimits } from './model/types';
export {
    acceptAttribute,
    checkUpload,
    describeAllowed,
    maxMegabytes,
} from './model/upload';
export type { UploadCheck } from './model/upload';
export { default as PhotoDrop } from './ui/PhotoDrop.vue';
