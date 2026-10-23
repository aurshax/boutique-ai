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
        return match ($this) {
            self::Trousers => 'Shim',
            self::Shirt => "Ko'ylak",
            self::Blazer => 'Blazer',
            self::Belt => 'Kamar',
            self::Tie => 'Galstuk',
            self::Shoes => 'Poyabzal',
        };
    }

    /** Faqat shu turlar uchun tkan almashtirish mumkin. */
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
