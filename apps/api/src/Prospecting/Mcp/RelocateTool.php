<?php

namespace App\Prospecting\Mcp;

use App\Prospecting\OrganizationReader;
use App\Prospecting\OrganizationResolver;
use App\Prospecting\OrganizationVoter;
use App\Security\ToolAccess;
use App\Shared\CommandBus;
use App\Shared\Input\AddressInput;
use App\Shared\Mcp\Adapts;
use App\Shared\Mcp\ToolInputs;
use App\Shared\Mcp\ToolResults;
use Mcp\Schema\ToolAnnotations;
use Mcp\Server\Capability\Attribute\McpTool;
use Prospecter\Core\Prospecting\Application\Relocate\Relocate;
use Symfony\Component\Clock\ClockInterface;

/**
 * `PATCH /api/organizations/{id}/address`, for an AI (ADR 0163).
 */
#[Adapts('api_organizations_relocate')]
final readonly class RelocateTool
{
    public function __construct(
        private ToolAccess $access,
        private OrganizationResolver $organizations,
        private ToolInputs $inputs,
        private CommandBus $commandBus,
        private ClockInterface $clock,
        private OrganizationReader $organizationReader,
        private ToolResults $results,
    ) {
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
    #[McpTool(name: 'demenager-organisation', description: 'Move an organization to an address, located again as on the screen; an address left empty removes it. Answers the organization.', annotations: new ToolAnnotations(readOnlyHint: false, destructiveHint: false, idempotentHint: true))]
    public function __invoke(string $id, ?string $street = null, ?string $postalCode = null, ?string $city = null, ?string $country = null): array
    {
        $organization = $this->organizations->read($id);
        $this->access->denyUnlessGranted('ROLE_PROSPECTOR');
        $this->access->denyUnlessGranted(OrganizationVoter::ACCESS, $organization);
        $input = $this->inputs->valid(new AddressInput($street, $postalCode, $city, $country));
        $this->commandBus->dispatch(new Relocate($this->access->member()->memberId(), $organization->id, $input->street, $input->postalCode, $input->city, $input->country, $this->clock->now()));

        return $this->results->of($this->organizationReader->read($organization->id));
    }
}
