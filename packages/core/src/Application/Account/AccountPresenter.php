<?php

declare(strict_types=1);

namespace Prospecter\Core\Application\Account;

interface AccountPresenter
{
    public function present(AccountResponse $response): mixed;
}
