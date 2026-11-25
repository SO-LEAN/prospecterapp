<?php

declare(strict_types=1);

namespace App\Shop;

#[\Attribute(\Attribute::TARGET_PROPERTY)]
final readonly class Offers
{
    public function __construct(string ...$routes)
    {
    }
}
