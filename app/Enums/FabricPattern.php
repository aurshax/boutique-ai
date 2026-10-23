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
        return match ($this) {
            self::Plain => 'Silliq',
            self::Pinstripe => "Ingichka chiziq (pinstripe)",
            self::Check => 'Katak',
            self::Herringbone => 'Archa (herringbone)',
            self::Birdseye => "Qush ko'zi (birdseye)",
            self::Tweed => 'Tvid',
        };
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
