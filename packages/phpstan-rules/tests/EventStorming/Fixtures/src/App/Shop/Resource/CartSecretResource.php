<?php

declare(strict_types=1);

namespace App\Shop\Resource;

final readonly class CartSecretResource
{
    public function __construct(public string $code)
    {
    }
}
