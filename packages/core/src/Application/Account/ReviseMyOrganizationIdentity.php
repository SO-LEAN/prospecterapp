<?php

declare(strict_types=1);

namespace Prospecter\Core\Application\Account;

use Prospecter\Core\Application\UseCase;
use Prospecter\Core\Domain\Organization\OrganizationRepository;
use Prospecter\Core\Domain\User\UserRepository;

final class ReviseMyOrganizationIdentity extends UseCase
{
    private readonly AccountResponses $responses;

    public function __construct(private readonly UserRepository $users, private readonly OrganizationRepository $organizations)
    {
        $this->responses = new AccountResponses($organizations);
    }

    public function execute(ReviseMyOrganizationIdentityRequest $request, AccountPresenter $presenter): mixed
    {
        $me = $this->users->get($request->userId);
        $myOrganization = $this->organizations->get($me->organizationId());
        $myOrganization->reviseLegalIdentity($request->legalIdentity, by: $me);
        $this->organizations->save($myOrganization);

        return $presenter->present($this->responses->of($me));
    }
}
