<?php

namespace App\Prospecting;

use App\Prospecting\Presenter\OrganizationResourcePresenter;
use App\Prospecting\Resource\OrganizationResource;
use Prospecter\Core\Prospecting\Application\ConsultOrganization\ConsultOrganization;
use Prospecter\Core\SharedKernel\Application\RefusalTranslatingHandler;

/**
 * An organization as the API shows it: read by the query of the core, then presented. A controller calls
 * it after a command, which returns nothing, to answer with the fresh resource.
 */
final readonly class OrganizationReader
{
    public function __construct(
        private RefusalTranslatingHandler $consultOrganization,
        private OrganizationResourcePresenter $presenter,
    ) {
    }

    public function read(string $id): OrganizationResource
    {
        return $this->presenter->present(($this->consultOrganization)(new ConsultOrganization($id)));
    }
}
