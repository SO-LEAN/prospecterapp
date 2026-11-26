<?php

declare(strict_types=1);

namespace App\Shop\Resource;

final readonly class CartLineResource
{
    public function __construct(public string $book, public CartLineLinksResource|CartSecretResource|null $links = null)
    {
    }
}
