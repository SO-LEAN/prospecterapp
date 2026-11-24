<?php

declare(strict_types=1);

namespace App\Shop\Resource;

use OpenApi\Attributes as OA;

final readonly class CartLinksResource
{
    public function __construct(
        public string $addItem,
        #[OA\Property(description: 'Check the cart out. Offered only while it holds an item', nullable: false)]
        public ?string $checkOut = null,
        public ?string $share = null,
    ) {
    }
}
