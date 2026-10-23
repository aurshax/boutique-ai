<?php

namespace App\TryOn\Data;

final readonly class ReferenceImage
{
    /**
     * @param  string  $bytes  rasm tarkibi
     * @param  string  $label  promptda shu rasmga havola: "trousers", "fabric swatch" ...
     */
    public function __construct(
        public string $bytes,
        public string $label,
        public string $filename = 'reference.jpg',
    ) {}
}
