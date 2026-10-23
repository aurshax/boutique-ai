// How a fabric swatch looks: the texture image when there is one, otherwise the main colour.
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
