<?php

namespace App\TryOn\Data;

final readonly class ReferenceImage
{
    /**
     * @param  string  $bytes  image contents
     * @param  string  $label  how the prompt refers to it: "trousers", "fabric swatch", ...
     */
    public function __construct(
        public string $bytes,
        public string $label,
        public string $filename = 'reference.jpg',
    ) {}
}
