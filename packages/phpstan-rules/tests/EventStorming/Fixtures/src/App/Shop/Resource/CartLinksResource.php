<?php

declare(strict_types=1);

namespace App\Shop\Resource;

use App\Shop\Offers;
use OpenApi\Attributes as OA;

final readonly class CartLinksResource
{
    public function __construct(
        #[Offers('api_cart_add_item')]
        public string $addItem,
        #[OA\Property(description: 'Check the cart out. Offered only while it holds an item', nullable: false)]
        #[Offers('api_cart_check_out', 'api_cart_pay')]
        public ?string $checkOut = null,
        public ?string $share = null,
    ) {
    }
}
