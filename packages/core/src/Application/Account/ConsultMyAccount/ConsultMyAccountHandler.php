<?php

declare(strict_types=1);

namespace Prospecter\Core\Application\Account\ConsultMyAccount;

use Prospecter\Core\Application\Account\AccountResponse;
use Prospecter\Core\Application\Account\AccountResponses;
use Prospecter\Core\Domain\Organization\OrganizationRepository;
use Prospecter\Core\Domain\User\UserRepository;

final readonly class ConsultMyAccountHandler
{
    private AccountResponses $responses;

    public function __construct(private UserRepository $users, OrganizationRepository $organizations)
    {
        $this->responses = new AccountResponses($organizations);
    }

    public function __invoke(ConsultMyAccount $query): AccountResponse
    {
        return $this->responses->of($this->users->get($query->userId));
    }
}
