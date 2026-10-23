// Tkan namunasining ko'rinishi: rasm bo'lsa — tekstura, bo'lmasa — asosiy rang.
import type { Fabric } from '../model/types';

export function swatchStyle(
    fabric: Pick<Fabric, 'swatch_image' | 'color_hex'>,
): Record<string, string> {
    return fabric.swatch_image
        ? {
              backgroundImage: `url(/${fabric.swatch_image.replace(/^\//, '')})`,
              backgroundSize: 'cover',
              backgroundPosition: 'center',
          }
        : { backgroundColor: fabric.color_hex };
}
