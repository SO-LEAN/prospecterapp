<?php

namespace App\Api;

use App\Api\Resource\OrganizationResource;
use App\Presenter\OrganizationResourcePresenter;
use Prospecter\Core\Application\Organization\ConsultOrganization\ConsultOrganization;
use Prospecter\Core\Application\Organization\ConsultOrganization\ConsultOrganizationHandler;
use Prospecter\Core\Domain\Organization\OrganizationId;
use Prospecter\Core\Domain\User\UserId;

/**
 * An organization as the API shows it: read by the query of the core, then presented. A controller calls
 * it after a command, which returns nothing, to answer with the fresh resource.
 */
final readonly class OrganizationReader
{
    public function __construct(
        private ConsultOrganizationHandler $consultOrganization,
        private OrganizationResourcePresenter $presenter,
    ) {
    }

    public function read(UserId $reader, OrganizationId $id): OrganizationResource
    {
        return $this->presenter->present(($this->consultOrganization)(new ConsultOrganization($reader, $id)));
    }
}
