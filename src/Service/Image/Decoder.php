<?php

namespace App\Service\Image;

interface Decoder
{
    public function decode(string $input): string|false;
}
