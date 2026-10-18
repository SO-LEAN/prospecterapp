<?php

declare(strict_types=1);

namespace Prospecter\Core\Accounts\Domain\Event;

use Prospecter\Core\SharedKernel\Domain\Event\AccountEvent;

/**
 * A member moved the organization of the account to another address, or erased it.
 */
final readonly class AccountRelocated extends AccountEvent
{
}
