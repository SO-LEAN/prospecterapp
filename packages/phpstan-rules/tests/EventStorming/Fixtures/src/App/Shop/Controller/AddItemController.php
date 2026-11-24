<?php

declare(strict_types=1);

namespace App\Shop\Controller;

use Prospecter\Core\Shop\Application\Cart\AddItem\AddItem;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
final readonly class AddItemController
{
    #[Route('/api/cart/items', name: 'api_cart_add_item', methods: ['POST'])]
    #[IsGranted('ROLE_PROSPECTOR')]
    public function __invoke(): void
    {
        new AddItem('a book');
    }
}
