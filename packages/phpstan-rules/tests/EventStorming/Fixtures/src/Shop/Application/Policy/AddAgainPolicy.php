<?php

declare(strict_types=1);

namespace Prospecter\Core\Shop\Application\Policy;

use Prospecter\Core\SharedKernel\Application\Handler\Policy;
use Prospecter\Core\Shop\Application\Cart\AddItem\AddItem;
use Prospecter\Core\Shop\Domain\Event\CartEmptied;

final readonly class AddAgainPolicy implements Policy
{
    public function __invoke(CartEmptied $event): ?AddItem
    {
        return null;
    }
}
