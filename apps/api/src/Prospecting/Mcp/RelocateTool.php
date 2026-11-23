<?php

declare(strict_types=1);

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
     * @param ?string $country    the country's code (FR, BE…)
     * @param ?int    $revision   the organization's `revision` as you read it. If it has changed since, the change is refused (changedMeanwhile): read the organization again
     *
     * @return array<string, mixed>
     */
    #[McpTool(name: 'set-organization-address', description: 'Move an organization to an address. Prospecter locates it again, as on the screen. An empty address removes the address. Returns the organization.', annotations: new ToolAnnotations(readOnlyHint: false, destructiveHint: false, idempotentHint: true, openWorldHint: false))]
    public function __invoke(string $id, ?string $street = null, ?string $postalCode = null, ?string $city = null, ?string $country = null, ?int $revision = null): array
    {
        return $this->writes->writeOnRevision(
            $id,
            $revision,
            new AddressInput($street, $postalCode, $city, $country),
            static fn (AddressInput $input, OrganizationSheet $organization, string $memberId, \DateTimeImmutable $now): Relocate => new Relocate(prospectorId: $memberId, id: $organization->id, street: $input->street, postalCode: $input->postalCode, city: $input->city, country: $input->country, executedAt: $now),
        );
    }
}
