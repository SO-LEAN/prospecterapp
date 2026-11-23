<?php

declare(strict_types=1);

namespace App\Prospecting;

use App\Prospecting\Persistence\OrganizationRevisions;
use App\Prospecting\Presenter\OrganizationResourcePresenter;
use App\Prospecting\Resource\OrganizationResource;
use App\Security\IntentLinks;
use App\Shared\ImageResourceFactory;
use App\Shared\Links;
use Prospecter\Core\Prospecting\Application\ConsultOrganization\ConsultOrganization;
use Prospecter\Core\Prospecting\Application\OrganizationSheet;
use Prospecter\Core\SharedKernel\Application\Handler\QueryHandler;

/**
 * Reads an organization as the API shows it. The core's query reads it, and a presenter built for the call presents
 * it (ADR 0085). A command returns nothing, so a controller calls this reader after the command to answer with the
 * fresh resource. A controller also calls it to present an organization that a resolver already read.
 */
final readonly class OrganizationReader
{
    public function __construct(
        private QueryHandler $consultOrganization,
        private ImageResourceFactory $images,
        private Links $links,
        private IntentLinks $intents,
        private OrganizationRevisions $revisions,
    ) {
    }

    public function read(string $id): OrganizationResource
    {
        $presenter = $this->presenter();
        $this->revisions->watch($id);
        $this->consultOrganization->__invoke(new ConsultOrganization($id), $presenter);

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
        return new OrganizationResourcePresenter($this->images, $this->links, $this->intents, $this->revisions);
    }
}
