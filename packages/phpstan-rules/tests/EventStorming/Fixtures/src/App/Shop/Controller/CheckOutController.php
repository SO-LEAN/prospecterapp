<?php

declare(strict_types=1);

namespace App\Shop\Controller;

use App\Shop\Security\Buyer;
use App\Shop\Security\CartVoter;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted(Buyer::ROLE)]
final readonly class CheckOutController
{
    #[Route('/api/cart/check-out', name: 'api_cart_check_out', methods: ['POST'])]
    #[IsGranted(CartVoter::ACCESS, subject: 'cart')]
    public function __invoke(): void
    {
    }
}
