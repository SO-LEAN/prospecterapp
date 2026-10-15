<?php

declare(strict_types=1);

namespace Prospecter\Core\Accounts\Domain\Event;

use Prospecter\Core\SharedKernel\Domain\Event\AccountEvent;

/**
 * A member revised the legal identity of the organization of the account.
 */
final readonly class AccountIdentityRevised extends AccountEvent
{
}
