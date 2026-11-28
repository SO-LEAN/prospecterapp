<?php

declare(strict_types=1);

namespace App\Shop\Security;

final readonly class Buyer implements UserInterface
{
    public const string ROLE = 'ROLE_BUYER';

    private const string LOGIN = 'buyer';
}
