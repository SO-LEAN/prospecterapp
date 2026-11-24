<?php

declare(strict_types=1);

namespace Prospecter\Core\Shop\Application\Cart\ConsultCart;

use Prospecter\Core\SharedKernel\Application\Handler\QueryHandler;

final readonly class ConsultCartHandler implements QueryHandler
{
    public function __invoke(ConsultCart $query, ConsultCartOutput $output): void
    {
        $output->present(new CartView());
    }
}
