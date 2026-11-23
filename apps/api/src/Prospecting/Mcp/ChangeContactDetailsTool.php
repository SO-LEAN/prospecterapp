<?php

declare(strict_types=1);

namespace App\Prospecting\Mcp;

use App\Prospecting\Input\OrganizationContactDetailsInput;
use App\Shared\Mcp\Adapts;
use Mcp\Schema\ToolAnnotations;
use Mcp\Server\Capability\Attribute\McpTool;
use Prospecter\Core\Prospecting\Application\ChangeContactDetails\ChangeContactDetails;
use Prospecter\Core\Prospecting\Application\OrganizationSheet;

/**
 * `PATCH /api/organizations/{id}/contact-details`, for an AI (ADR 0163).
 */
#[Adapts('api_organizations_change_contact_details')]
final readonly class ChangeContactDetailsTool
{
    public function __construct(private OrganizationWrites $writes)
    {
    }

    /**
     * @param string       $id          the id of the organization
     * @param ?string      $email       its e-mail
     * @param ?string      $phoneNumber its phone number, in international format (+33 3 88 11 22 33) or in French national format (03 88 11 22 33)
     * @param ?string      $language    the country code of the language spoken with it (FR, GB…)
     * @param list<string> $links       the addresses of its pages on outside tools (a company on LinkedIn or Welcome to the Jungle, or any other site), five at most
     * @param ?string      $website     its website's address, with or without the scheme
     * @param ?int         $revision    the organization's `revision` as you read it. If it has changed since, the change is refused (changedMeanwhile): read the organization again
     *
     * @return array<string, mixed>
     */
    #[McpTool(name: 'update-organization-details', description: 'Change an organization\'s e-mail, phone number, language, links and website. Nothing else changes. A value left out is removed. Returns the organization.', annotations: new ToolAnnotations(readOnlyHint: false, destructiveHint: false, idempotentHint: true, openWorldHint: false))]
    public function __invoke(string $id, ?string $email = null, ?string $phoneNumber = null, ?string $language = null, array $links = [], ?string $website = null, ?int $revision = null): array
    {
        return $this->writes->writeOnRevision(
            $id,
            $revision,
            new OrganizationContactDetailsInput($email, $phoneNumber, $language, $links, $website),
            static fn (OrganizationContactDetailsInput $input, OrganizationSheet $organization, string $memberId, \DateTimeImmutable $now): ChangeContactDetails => new ChangeContactDetails(prospectorId: $memberId, id: $organization->id, email: $input->email, phoneNumber: $input->phoneNumber, language: $input->language, executedAt: $now, links: $input->links, website: $input->website),
        );
    }
}
