<?php

declare(strict_types=1);

namespace Prospecter\Core\Accounts\Application\Account\ConsultMyAccount;

use Prospecter\Core\Accounts\Application\Account\MyAccount;

/**
 * Keeps the answer unchanged for a client that reads it instead of displaying it (ADR 0085).
 */
final class KeptAccount implements ConsultMyAccountOutput
{
    public private(set) MyAccount $account;

    public function present(MyAccount $account): void
    {
        $this->account = $account;
    }
}
