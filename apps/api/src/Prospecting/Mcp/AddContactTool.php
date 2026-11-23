<?php

declare(strict_types=1);

namespace App\Prospecting\Mcp;

use App\Prospecting\Input\ContactInput;
use App\Security\User;
use App\Shared\Mcp\Adapts;
use Mcp\Schema\ToolAnnotations;
use Mcp\Server\Capability\Attribute\McpTool;
use Prospecter\Core\Prospecting\Application\AddContact\AddContact;
use Prospecter\Core\Prospecting\Application\OrganizationSheet;

/**
 * `POST /api/organizations/{id}/contacts`, for an AI (ADR 0163).
 */
#[Adapts('api_organizations_add_contact')]
final readonly class AddContactTool
{
    public function __construct(private OrganizationWrites $writes)
    {
    }

    /**
     * @param string $id       the id of the organization
     * @param string $personId the id of one of the account's contacts. It may be a contact to attach
     *
     * @return array<string, mixed>
     */
    #[McpTool(name: 'add-organization-contact', description: 'Attach one of the account\'s contacts to an organization. It may be a contact to attach. Needs the right to manage personal data. Returns the organization.', annotations: new ToolAnnotations(readOnlyHint: false, destructiveHint: false, idempotentHint: false, openWorldHint: false))]
    public function __invoke(string $id, string $personId): array
    {
        return $this->writes->write(
            $id,
            new ContactInput($personId),
            static fn (ContactInput $input, OrganizationSheet $organization, string $memberId, \DateTimeImmutable $now): AddContact => new AddContact(prospectorId: $memberId, id: $organization->id, personId: $input->personId, executedAt: $now),
            User::PERSONAL_DATA,
        );
    }
}
