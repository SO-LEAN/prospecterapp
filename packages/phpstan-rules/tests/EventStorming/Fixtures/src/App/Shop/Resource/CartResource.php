<?php

declare(strict_types=1);

namespace App\Shop\Resource;

final readonly class CartResource
{
    public function __construct(public CartLinksResource $links)
    {
    }
}
