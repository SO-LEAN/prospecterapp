<?php

declare(strict_types=1);

namespace Prospecter\Core\Accounts\Application\ConsultMyAccount;

use Prospecter\Core\Accounts\Application\AccountResponse;

/**
 * Keeps the answer as it is, for a client that reads it rather than shows it (ADR 0084).
 */
final class KeptAccount implements ConsultMyAccountOutput
{
    public private(set) AccountResponse $account;

    public function present(AccountResponse $account): void
    {
        $this->account = $account;
    }
}
