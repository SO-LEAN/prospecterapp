<?php

declare(strict_types=1);

namespace Prospecter\Core\Accounts\Contract\Service\ListAccounts;

/**
 * Asks for the accounts' ids, newest first, one slice after a cursor (ADR 0049). Another context uses them to know
 * which accounts to count again.
 */
final readonly class ListAccounts
{
    public function __construct(
        public ?string $after,
        public int $max,
    ) {
    }
}
