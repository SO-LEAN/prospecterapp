<?php

declare(strict_types=1);

namespace Prospecter\Core\Accounts\Domain\Exception;

use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\Exception\NotFound;

final class UnknownAccount extends NotFound
{
    public static function identifiedBy(AccountId $id): self
    {
        return new self(sprintf('Account %s not found', $id));
    }
}
