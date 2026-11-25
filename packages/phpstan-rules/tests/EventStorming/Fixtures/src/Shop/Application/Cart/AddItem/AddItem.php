<?php

declare(strict_types=1);

namespace Prospecter\Core\Shop\Application\Cart\AddItem;

final readonly class AddItem
{
    public function __construct(public string $item)
    {
    }

    public static function ofABook(): self
    {
        return new self('a book');
    }
}
