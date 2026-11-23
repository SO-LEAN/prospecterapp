<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Domain\Model;

/**
 * What a member has to do, and when it is due. It is the argument object for logging a to-do.
 */
final readonly class Task
{
    public function __construct(
        public string $text,
        public Due $due,
    ) {
    }
}
