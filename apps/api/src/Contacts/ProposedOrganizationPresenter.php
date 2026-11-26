<?php

declare(strict_types=1);

namespace App\Contacts;

use App\Contacts\Resource\OrganizationProposedResource;
use App\Contacts\Resource\ProposedOrganizationResource;
use Prospecter\Core\Prospecting\Application\Contact\ProposeOrganizationToAttach\ProposedOrganization;
use Prospecter\Core\Prospecting\Application\Contact\ProposeOrganizationToAttach\ProposeOrganizationToAttachOutput;

/**
 * Presents the organization proposed to attach a contact to, or none (ADR 0085, 0146).
 */
final class ProposedOrganizationPresenter implements ProposeOrganizationToAttachOutput
{
    public private(set) ProposedOrganizationResource $resource;

    public function present(?ProposedOrganization $organization): void
    {
        $this->resource = new ProposedOrganizationResource(null === $organization ? null : new OrganizationProposedResource($organization->id, $organization->name));
    }
}
