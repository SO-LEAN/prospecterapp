<?php

namespace App\Prospecting;

use App\Prospecting\Presenter\OrganizationResourcePresenter;
use App\Prospecting\Resource\OrganizationResource;
use App\Security\IntentLinks;
use App\Shared\ImageResourceFactory;
use App\Shared\Links;
use Prospecter\Core\Prospecting\Application\ConsultOrganization\ConsultOrganization;
use Prospecter\Core\Prospecting\Application\OrganizationSheet;
use Prospecter\Core\SharedKernel\Application\RefusalTranslatingHandler;

/**
 * An organization as the API shows it: read by the query of the core, presented to the presenter it builds for the
 * call (ADR 0085). A controller calls it after a command, which returns nothing, to answer with the fresh resource,
 * or to present an organization a resolver already read.
 */
final readonly class OrganizationReader
{
    public function __construct(
        private RefusalTranslatingHandler $consultOrganization,
        private ImageResourceFactory $images,
        private Links $links,
        private IntentLinks $intents,
    ) {
    }

    public function read(string $id): OrganizationResource
    {
        $presenter = $this->presenter();
        ($this->consultOrganization)(new ConsultOrganization($id), $presenter);

        return $presenter->resource;
    }

    public function resourceOf(OrganizationSheet $organization): OrganizationResource
    {
        $presenter = $this->presenter();
        $presenter->present($organization);

        return $presenter->resource;
    }

    private function presenter(): OrganizationResourcePresenter
    {
        return new OrganizationResourcePresenter($this->images, $this->links, $this->intents);
    }
}
