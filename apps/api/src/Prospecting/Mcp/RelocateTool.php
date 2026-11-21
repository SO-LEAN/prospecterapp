<?php

namespace App\Prospecting\Mcp;

use App\Shared\Input\AddressInput;
use App\Shared\Mcp\Adapts;
use Mcp\Schema\ToolAnnotations;
use Mcp\Server\Capability\Attribute\McpTool;
use Prospecter\Core\Prospecting\Application\OrganizationSheet;
use Prospecter\Core\Prospecting\Application\Relocate\Relocate;

/**
 * `PATCH /api/organizations/{id}/address`, for an AI (ADR 0163).
 */
#[Adapts('api_organizations_relocate')]
final readonly class RelocateTool
{
    public function __construct(private OrganizationWrites $writes)
    {
    }

    /**
     * @param string  $id         the id of the organization
     * @param ?string $street     the number and the street
     * @param ?string $postalCode the postal code
     * @param ?string $city       the city
     * @param ?string $country    the country, its code (FR, BE…)
     *
     * @return array<string, mixed>
     */
    #[McpTool(name: 'set-organization-address', description: 'Move an organization to an address, located again as on the screen; an address left empty removes it. Answers the organization.', annotations: new ToolAnnotations(readOnlyHint: false, destructiveHint: false, idempotentHint: true, openWorldHint: false))]
    public function __invoke(string $id, ?string $street = null, ?string $postalCode = null, ?string $city = null, ?string $country = null): array
    {
        return $this->writes->write(
            $id,
            new AddressInput($street, $postalCode, $city, $country),
            static fn (AddressInput $input, OrganizationSheet $organization, string $memberId, \DateTimeImmutable $now): Relocate => new Relocate($memberId, $organization->id, $input->street, $input->postalCode, $input->city, $input->country, $now),
        );
    }
}
