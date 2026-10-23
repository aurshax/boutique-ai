<?php

namespace App\Enums;

enum FabricPattern: string
{
    case Plain = 'plain';
    case Pinstripe = 'pinstripe';
    case Check = 'check';
    case Herringbone = 'herringbone';
    case Birdseye = 'birdseye';
    case Tweed = 'tweed';

    public function label(): string
    {
        return __("catalog.patterns.{$this->value}");
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
