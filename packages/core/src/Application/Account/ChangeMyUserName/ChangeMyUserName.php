<?php

declare(strict_types=1);

namespace Prospecter\Core\Application\Account\ChangeMyUserName;

use Prospecter\Core\Application\Account\AccountPresenter;
use Prospecter\Core\Application\Account\AccountResponses;
use Prospecter\Core\Application\UseCase;
use Prospecter\Core\Domain\Organization\OrganizationRepository;
use Prospecter\Core\Domain\User\UserRepository;

final class ChangeMyUserName extends UseCase
{
    private readonly AccountResponses $responses;

    public function __construct(private readonly UserRepository $users, OrganizationRepository $organizations)
    {
        $this->responses = new AccountResponses($organizations);
    }

    public function execute(ChangeMyUserNameRequest $request, AccountPresenter $presenter): mixed
    {
        $me = $this->users->get($request->userId);
        $me->changeUserName($request->userName);
        $this->users->save($me);

        return $presenter->present($this->responses->of($me));
    }
}
