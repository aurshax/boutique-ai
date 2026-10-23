// Public API — slice tashqarisiga faqat shu fayl orqali.
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
    fabricsOf,
    findFabric,
    findLook,
    findStyleOfLook,
    firstLookId,
    lookSummary,
    lookTotal,
    productPrice,
    trousersOf,
} from './model/catalog';
export { swatchStyle } from './lib/swatch';
