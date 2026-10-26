<?php

declare(strict_types=1);

namespace Prospecter\Core\Accounts\Application\ConsultMyAccount;

use Prospecter\Core\Accounts\Application\AccountResponse;
use Prospecter\Core\SharedKernel\Application\OutputPort;

interface ConsultMyAccountOutput extends OutputPort
{
    public function present(AccountResponse $account): void;
}
