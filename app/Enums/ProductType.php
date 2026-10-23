<?php

namespace App\Enums;

enum ProductType: string
{
    case Trousers = 'trousers';
    case Shirt = 'shirt';
    case Blazer = 'blazer';
    case Belt = 'belt';
    case Tie = 'tie';
    case Shoes = 'shoes';

    public function label(): string
    {
        return __("catalog.product_types.{$this->value}");
    }

    /** Only these types can be made in a different fabric. */
    public function supportsFabricSwap(): bool
    {
        return in_array($this, [self::Trousers, self::Blazer], true);
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
