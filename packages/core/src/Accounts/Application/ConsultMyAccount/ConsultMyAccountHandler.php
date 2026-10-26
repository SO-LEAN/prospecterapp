<?php

declare(strict_types=1);

namespace Prospecter\Core\Accounts\Application\ConsultMyAccount;

use Prospecter\Core\Accounts\Application\AccountResponse;
use Prospecter\Core\Accounts\Domain\AccountRepository;
use Prospecter\Core\Accounts\Domain\MemberRepository;
use Prospecter\Core\SharedKernel\Application\QueryHandler;
use Prospecter\Core\SharedKernel\Domain\MemberId;

final readonly class ConsultMyAccountHandler implements QueryHandler
{
    public function __construct(private MemberRepository $members, private AccountRepository $accounts)
    {
    }

    public function __invoke(ConsultMyAccount $query, ConsultMyAccountOutput $output): void
    {
        $me = $this->members->get(new MemberId($query->memberId));

        $output->present(AccountResponse::of($this->accounts->get($me->accountId()), $me));
    }
}
