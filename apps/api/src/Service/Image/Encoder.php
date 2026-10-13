<?php

namespace App\Service\Image;

interface Encoder
{
    public function encode(string $input): string;
}
