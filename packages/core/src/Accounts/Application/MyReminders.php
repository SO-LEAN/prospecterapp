<?php

declare(strict_types=1);

namespace Prospecter\Core\Accounts\Application;

/**
 * Whether I am reminded of the exchanges I planned, and how many minutes before.
 */
final readonly class MyReminders
{
    public function __construct(
        public bool $enabled,
        public int $minutesBefore,
    ) {
    }
}
