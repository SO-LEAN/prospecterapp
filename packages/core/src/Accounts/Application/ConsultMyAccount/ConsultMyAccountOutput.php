<?php

declare(strict_types=1);

namespace Prospecter\Core\Accounts\Application\ConsultMyAccount;

use Prospecter\Core\Accounts\Application\MyAccount;
use Prospecter\Core\SharedKernel\Application\OutputPort;

interface ConsultMyAccountOutput extends OutputPort
{
    public function present(MyAccount $account): void;
}
