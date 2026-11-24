<?php

declare(strict_types=1);

namespace App\Shop\Controller;

use Prospecter\Core\Shop\Application\Cart\AddItem\AddItem;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
final readonly class AddItemController
{
    #[IsGranted('ROLE_PROSPECTOR')]
    public function __invoke(): void
    {
        new AddItem('a book');
    }
}
