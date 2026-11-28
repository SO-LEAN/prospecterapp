<?php

declare(strict_types=1);

namespace App\Shop\Controller;

use App\Shop\Security\CartVoter;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final readonly class ShareCartController
{
    #[Route('/api/cart/share', name: 'api_cart_share', methods: ['POST'])]
    #[IsGranted(CartVoter::ACCESS, subject: 'cart')]
    public function __invoke(): void
    {
    }
}
