<?php

declare(strict_types=1);

namespace Prospecter\Core\SharedKernel\Domain;

/**
 * The identity of an account (an organization using Prospecter), published to the other contexts.
 */
final readonly class AccountId extends Uuid
{
}
