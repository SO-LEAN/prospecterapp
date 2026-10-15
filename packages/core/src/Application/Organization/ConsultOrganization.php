<?php

declare(strict_types=1);

namespace Prospecter\Core\Application\Organization;

use Prospecter\Core\Application\UseCaseConsumer;

final class ConsultOrganization extends OrganizationUseCase
{
    public function execute(ConsultOrganizationRequest $request, OrganizationPresenter $presenter, UseCaseConsumer $consumer): mixed
    {
        return $this->present($this->prospectedOrganizations->visibleTo($consumer->getOrganizationId(), $request->id), $presenter);
    }
}
