<?php

declare(strict_types=1);

namespace Prospecter\Core\Accounts\Application\ConsultMyAccount;

use Prospecter\Core\Accounts\Application\MyAccount;

/**
 * Keeps the answer as it is, for a client that reads it rather than shows it (ADR 0085).
 */
final class KeptAccount implements ConsultMyAccountOutput
{
    public private(set) MyAccount $account;

    public function present(MyAccount $account): void
    {
        $this->account = $account;
    }
}
