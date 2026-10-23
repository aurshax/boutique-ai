// Public API — the only entry point into this slice from other slices.
export type {
    Fabric,
    FabricCard,
    Look,
    Product,
    ProductType,
    Style,
    StyleCard,
} from './model/types';
export {
    defaultFabricId,
    fabricScope,
    fabricsOf,
    findFabric,
    findLook,
    findStyleOfLook,
    followsFabric,
    firstLookId,
    lookSummary,
    lookTotal,
    productPrice,
    productsWithImages,
    trousersOf,
} from './model/catalog';
export { swatchStyle } from './lib/swatch';
export { default as ProductThumb } from './ui/ProductThumb.vue';
