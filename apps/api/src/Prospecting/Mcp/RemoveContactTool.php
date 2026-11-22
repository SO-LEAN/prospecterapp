<?php

declare(strict_types=1);

namespace App\Prospecting\Mcp;

use App\Security\User;
use App\Shared\Mcp\Adapts;
use Mcp\Schema\ToolAnnotations;
use Mcp\Server\Capability\Attribute\McpTool;
use Prospecter\Core\Prospecting\Application\OrganizationSheet;
use Prospecter\Core\Prospecting\Application\RemoveContact\RemoveContact;

/**
 * `DELETE /api/organizations/{id}/contacts/{personId}`, for an AI (ADR 0163), which asks its member before calling it.
 */
#[Adapts('api_organizations_remove_contact')]
final readonly class RemoveContactTool
{
    public function __construct(private OrganizationWrites $writes)
    {
    }

    /**
     * @param string $id       the id of the organization
     * @param string $personId the id of the contact to detach from it
     *
     * @return array<string, mixed>
     */
    #[McpTool(name: 'remove-organization-contact', description: 'Detach a contact from an organization; the person stays among the contacts of the account. Needs the right to manage the personal data. Answers the organization.', annotations: new ToolAnnotations(readOnlyHint: false, destructiveHint: true, idempotentHint: false, openWorldHint: false))]
    public function __invoke(string $id, string $personId): array
    {
        return $this->writes->write(
            $id,
            null,
            static fn (null $input, OrganizationSheet $organization, string $memberId, \DateTimeImmutable $now): RemoveContact => new RemoveContact(prospectorId: $memberId, id: $organization->id, personId: $personId, executedAt: $now),
            User::PERSONAL_DATA,
        );
    }
}
