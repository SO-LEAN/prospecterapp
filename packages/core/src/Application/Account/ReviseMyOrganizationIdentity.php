<?php

declare(strict_types=1);

namespace Prospecter\Core\Application\Account;

use Prospecter\Core\Application\UseCaseConsumer;

final class ReviseMyOrganizationIdentity extends AccountUseCase
{
    public function execute(ReviseMyOrganizationIdentityRequest $request, AccountPresenter $presenter, UseCaseConsumer $consumer): mixed
    {
        $myOrganization = $this->myOrganization($consumer);
        $myOrganization->reviseLegalIdentity($request->legalIdentity);
        $this->organizations->save($myOrganization);

        return $this->present($consumer, $presenter);
    }
}
