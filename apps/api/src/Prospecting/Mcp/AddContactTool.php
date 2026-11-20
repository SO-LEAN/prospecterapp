<?php

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
     * @param string $personId the id of the contact of the account to attach to it, a contact to attach among them
     *
     * @return array<string, mixed>
     */
    #[McpTool(name: 'ajouter-contact-organisation', description: 'Attach a contact of the account to an organization, a contact to attach among them; needs the right to manage the personal data. Answers the organization.', annotations: new ToolAnnotations(readOnlyHint: false, destructiveHint: false, idempotentHint: false))]
    public function __invoke(string $id, string $personId): array
    {
        return $this->writes->write(
            $id,
            new ContactInput($personId),
            static fn (ContactInput $input, OrganizationSheet $organization, string $memberId, \DateTimeImmutable $now): AddContact => new AddContact($memberId, $organization->id, $input->personId, $now),
            User::PERSONAL_DATA,
        );
    }
}
