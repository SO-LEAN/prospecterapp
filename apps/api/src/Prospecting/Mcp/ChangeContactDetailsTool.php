<?php

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
     * @param ?string      $phoneNumber its phone number, international (+33 3 88 11 22 33) or national in France (03 88 11 22 33)
     * @param ?string      $language    the country of the language spoken with it (FR, GB…)
     * @param list<string> $links       the addresses of its pages on the tools outside (a company on LinkedIn or Welcome to the Jungle, any other site), five at most
     * @param ?string      $website     the address of its site, with or without its scheme
     *
     * @return array<string, mixed>
     */
    #[McpTool(name: 'update-organization-details', description: 'Change the e-mail, phone number, language, links and website of an organization, nothing else changes; what is left out is removed. Answers the organization.', annotations: new ToolAnnotations(readOnlyHint: false, destructiveHint: false, idempotentHint: true, openWorldHint: false))]
    public function __invoke(string $id, ?string $email = null, ?string $phoneNumber = null, ?string $language = null, array $links = [], ?string $website = null): array
    {
        return $this->writes->write(
            $id,
            new OrganizationContactDetailsInput($email, $phoneNumber, $language, $links, $website),
            static fn (OrganizationContactDetailsInput $input, OrganizationSheet $organization, string $memberId, \DateTimeImmutable $now): ChangeContactDetails => new ChangeContactDetails($memberId, $organization->id, $input->email, $input->phoneNumber, $input->language, $now, $input->links, $input->website),
        );
    }
}
