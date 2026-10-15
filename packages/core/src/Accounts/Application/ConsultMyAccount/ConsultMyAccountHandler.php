<?php

declare(strict_types=1);

namespace Prospecter\Core\Accounts\Application\ConsultMyAccount;

use Prospecter\Core\Accounts\Application\AccountResponse;
use Prospecter\Core\Accounts\Application\AccountResponses;
use Prospecter\Core\Accounts\Domain\AccountRepository;
use Prospecter\Core\Accounts\Domain\MemberRepository;

final readonly class ConsultMyAccountHandler
{
    private AccountResponses $responses;

    public function __construct(private MemberRepository $members, AccountRepository $accounts)
    {
        $this->responses = new AccountResponses($accounts);
    }

    public function __invoke(ConsultMyAccount $query): AccountResponse
    {
        return $this->responses->of($this->members->get($query->userId));
    }
}
