<?php

declare(strict_types=1);

namespace Prospecter\Core\Accounts\Domain\Event;

/**
 * A member moved the account's organization to another address, or erased its address.
 */
final readonly class AccountRelocated extends AccountEvent
{
}
