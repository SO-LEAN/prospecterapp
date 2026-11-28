<?php

declare(strict_types=1);

namespace App\Shop\Security;

final readonly class CartVoter
{
    public const string ACCESS = 'CART_ACCESS';

    public const string BUYER = 'ROLE_BUYER';
}
