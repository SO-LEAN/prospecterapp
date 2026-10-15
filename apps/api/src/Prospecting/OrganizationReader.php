<?php

namespace App\Prospecting;

use App\Prospecting\Presenter\OrganizationResourcePresenter;
use App\Prospecting\Resource\OrganizationResource;
use Prospecter\Core\Prospecting\Application\ConsultOrganization\ConsultOrganization;
use Prospecter\Core\Prospecting\Application\ConsultOrganization\ConsultOrganizationHandler;
use Prospecter\Core\SharedKernel\Application\RefusalTranslatingHandler;

/**
 * An organization as the API shows it: read by the query of the core, then presented. A controller calls
 * it after a command, which returns nothing, to answer with the fresh resource.
 */
final readonly class OrganizationReader
{
    private RefusalTranslatingHandler $consultOrganization;

    public function __construct(
        ConsultOrganizationHandler $consultOrganization,
        private OrganizationResourcePresenter $presenter,
    ) {
        $this->consultOrganization = new RefusalTranslatingHandler($consultOrganization);
    }

    public function read(string $reader, string $id): OrganizationResource
    {
        return $this->presenter->present(($this->consultOrganization)(new ConsultOrganization($reader, $id)));
    }
}
