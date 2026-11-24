<?php

declare(strict_types=1);

namespace App\Prospecting\Mcp;

use App\Security\User;
use App\Shared\Mcp\Adapts;
use Mcp\Schema\ToolAnnotations;
use Mcp\Server\Capability\Attribute\McpTool;
use Prospecter\Core\Prospecting\Application\Contact\RemoveContact\RemoveContact;
use Prospecter\Core\Prospecting\Application\OrganizationSheet;

/**
 * `DELETE /api/organizations/{id}/contacts/{personId}`, for an AI (ADR 0163). The AI asks its member
 * before it calls the tool.
 */
#[Adapts('api_organizations_remove_contact')]
final readonly class RemoveContactTool
{
    public function __construct(private OrganizationWrites $writes)
    {
    }

    /**
     * @param string $id       the id of the organization
     * @param string $personId the id of the contact to detach from the organization
     *
     * @return array<string, mixed>
     */
    #[McpTool(name: 'remove-organization-contact', description: 'Detach a contact from an organization. The person stays among the account\'s contacts. Needs the right to manage personal data. Returns the organization.', annotations: new ToolAnnotations(readOnlyHint: false, destructiveHint: true, idempotentHint: false, openWorldHint: false))]
    public function __invoke(string $id, string $personId): array
    {
        return $this->writes->change(
            $id,
            static fn (OrganizationSheet $organization, string $memberId, \DateTimeImmutable $now): RemoveContact => new RemoveContact(prospectorId: $memberId, id: $organization->id, personId: $personId, executedAt: $now),
            User::PERSONAL_DATA,
        );
    }
}
