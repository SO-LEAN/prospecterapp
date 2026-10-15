<?php

declare(strict_types=1);

namespace Prospecter\Core\Application\Account\ConsultMyAccount;

use Prospecter\Core\Domain\User\UserId;

final readonly class ConsultMyAccountRequest
{
    public function __construct(public UserId $userId)
    {
    }
}
