<?php

declare(strict_types=1);

namespace App\Contacts\Mcp;

use App\Contacts\OrganizationsOfContacts;
use App\Security\ToolAccess;
use App\Shared\Mcp\Adapts;
use App\Shared\Mcp\ToolResults;
use Mcp\Schema\ToolAnnotations;
use Mcp\Server\Capability\Attribute\McpTool;

/**
 * `GET /api/contacts/{id}/organizations`, for an AI (ADR 0163).
 */
#[Adapts('api_contacts_organizations')]
final readonly class ShowOrganizationsOfContactTool
{
    public function __construct(private ToolAccess $access, private OrganizationsOfContacts $organizationsOfContacts, private ToolResults $results)
    {
    }

    /**
     * @param string $id the contact's id
     *
     * @return array<string, mixed>
     */
    #[McpTool(name: 'list-contact-organizations', description: 'List the organizations a contact is attached to.', annotations: new ToolAnnotations(readOnlyHint: true))]
    public function __invoke(string $id): array
    {
        $this->access->denyUnlessGranted('ROLE_PROSPECTOR');

        return $this->results->of($this->organizationsOfContacts->ofOne($this->access->member()->memberId(), $id));
    }
}
