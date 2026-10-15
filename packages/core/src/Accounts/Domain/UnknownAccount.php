<?php

declare(strict_types=1);

namespace Prospecter\Core\Accounts\Domain;

use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\NotFound;

final class UnknownAccount extends NotFound
{
    public function __construct(AccountId $id)
    {
        parent::__construct(sprintf('Account %s not found', $id));
    }
}
