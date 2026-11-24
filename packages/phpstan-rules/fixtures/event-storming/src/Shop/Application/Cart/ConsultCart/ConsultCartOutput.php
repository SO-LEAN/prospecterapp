<?php

declare(strict_types=1);

namespace Prospecter\Core\Shop\Application\Cart\ConsultCart;

interface ConsultCartOutput
{
    public function present(CartView $cart): void;
}
