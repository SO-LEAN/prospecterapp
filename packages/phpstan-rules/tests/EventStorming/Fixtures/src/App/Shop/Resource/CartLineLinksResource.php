<?php

declare(strict_types=1);

namespace App\Shop\Resource;

use App\Shop\Offers;

final readonly class CartLineLinksResource
{
    public function __construct(
        #[Offers('api_cart_remove_line')]
        public ?string $remove = null,
    ) {
    }
}
