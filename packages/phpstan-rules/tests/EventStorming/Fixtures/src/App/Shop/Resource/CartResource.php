<?php

declare(strict_types=1);

namespace App\Shop\Resource;

final readonly class CartResource
{
    /**
     * @param list<CartLineResource> $lines
     */
    public function __construct(public CartLinksResource $links, public array $lines = [], private ?CartSecretResource $secret = null)
    {
    }
}
