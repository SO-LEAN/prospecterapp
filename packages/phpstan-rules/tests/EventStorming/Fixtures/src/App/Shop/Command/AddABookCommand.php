<?php

declare(strict_types=1);

namespace App\Shop\Command;

use Prospecter\Core\Shop\Application\Cart\AddItem\AddItem;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'app:cart:add-a-book')]
final readonly class AddABookCommand
{
    public function __invoke(): int
    {
        new AddItem('a book');

        return 0;
    }
}
