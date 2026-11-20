<?php

namespace App\Contacts\Mcp;

use App\Contacts\ProposedOrganizationReader;
use App\Security\ToolAccess;
use App\Security\User;
use App\Shared\Mcp\Adapts;
use App\Shared\Mcp\ToolResults;
use Mcp\Schema\ToolAnnotations;
use Mcp\Server\Capability\Attribute\McpTool;

/**
 * `GET /api/contacts/{id}/proposed-organization`, for an AI (ADR 0163).
 */
#[Adapts('api_contacts_proposed_organization')]
final readonly class ShowProposedOrganizationTool
{
    public function __construct(private ToolAccess $access, private ProposedOrganizationReader $proposedOrganizationReader, private ToolResults $results)
    {
    }

    /**
     * @param string $id the id of the contact to attach
     *
     * @return array<string, mixed>
     */
    #[McpTool(name: 'lire-organisation-proposee', description: 'L\'organisation proposée pour rattacher un contact à rattacher, d\'après le domaine de son e-mail ; avec le droit de gérer les données personnelles.', annotations: new ToolAnnotations(readOnlyHint: true))]
    public function __invoke(string $id): array
    {
        $this->access->denyUnlessGranted('ROLE_PROSPECTOR');
        $this->access->denyUnlessGranted(User::PERSONAL_DATA);

        return $this->results->of($this->proposedOrganizationReader->read($this->access->member()->memberId(), $id));
    }
}
