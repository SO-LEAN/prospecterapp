<?php

declare(strict_types=1);

namespace Prospecter\Core\Shop\Domain\Port;

interface Stock
{
    public function has(string $item): bool;
}
