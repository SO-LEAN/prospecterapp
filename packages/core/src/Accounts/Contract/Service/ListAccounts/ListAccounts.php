<?php

declare(strict_types=1);

namespace Prospecter\Core\Accounts\Contract\Service\ListAccounts;

/**
 * The identities of the accounts, newest first, a slice after a cursor (ADR 0049): what another context needs to
 * know which accounts to count again.
 */
final readonly class ListAccounts
{
    public function __construct(
        public ?string $after,
        public int $max,
    ) {
    }
}
