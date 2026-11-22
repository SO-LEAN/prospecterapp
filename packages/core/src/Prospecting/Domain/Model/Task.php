<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Domain\Model;

/**
 * What a member has to do, and when it is due: the argument object of a to-do noted.
 */
final readonly class Task
{
    public function __construct(
        public string $text,
        public Due $due,
    ) {
    }
}
