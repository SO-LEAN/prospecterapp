<?php

declare(strict_types=1);

namespace Prospecter\Core\SharedKernel\Domain\Identity;

/**
 * An account's id, published to the other contexts. An account is an organization that uses Prospecter.
 */
final readonly class AccountId extends Uuid
{
}
