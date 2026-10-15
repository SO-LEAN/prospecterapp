<?php

declare(strict_types=1);

namespace Prospecter\Core\Accounts\Application\ConsultMyAccount;

use Prospecter\Core\SharedKernel\Domain\MemberId;

final readonly class ConsultMyAccount
{
    public function __construct(public MemberId $userId)
    {
    }
}
