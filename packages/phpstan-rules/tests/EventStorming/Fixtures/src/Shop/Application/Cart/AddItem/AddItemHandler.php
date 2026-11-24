<?php

declare(strict_types=1);

namespace Prospecter\Core\Shop\Application\Cart\AddItem;

use Prospecter\Core\SharedKernel\Application\Handler\CommandHandler;
use Prospecter\Core\Shop\Domain\Model\Cart;
use Prospecter\Core\Shop\Domain\Port\Stock;

final readonly class AddItemHandler implements CommandHandler
{
    public function __construct(private Stock $stock, private Cart $cart)
    {
    }

    public function __invoke(AddItem $command): void
    {
        $this->cart->add($command->item);
        $this->cart->empty($this->stock->has($command->item));
    }
}
