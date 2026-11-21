<?php

namespace App\Prospecting\Mcp;

use App\Prospecting\Input\ObservationsInput;
use App\Shared\Mcp\Adapts;
use Mcp\Schema\ToolAnnotations;
use Mcp\Server\Capability\Attribute\McpTool;
use Prospecter\Core\Prospecting\Application\OrganizationSheet;
use Prospecter\Core\Prospecting\Application\WriteObservations\WriteObservations;

/**
 * `PATCH /api/organizations/{id}/observations`, for an AI (ADR 0163).
 */
#[Adapts('api_organizations_write_observations')]
final readonly class WriteObservationsTool
{
    public function __construct(private OrganizationWrites $writes)
    {
    }

    /**
     * @param string  $id           the id of the organization
     * @param ?string $observations what the members note on it, 255 characters at most; none when empty
     *
     * @return array<string, mixed>
     */
    #[McpTool(name: 'set-organization-notes', description: 'Write the observations on an organization, in place of those before. Answers the organization.', annotations: new ToolAnnotations(readOnlyHint: false, destructiveHint: false, idempotentHint: true, openWorldHint: false))]
    public function __invoke(string $id, ?string $observations = null): array
    {
        return $this->writes->write(
            $id,
            new ObservationsInput($observations),
            static fn (ObservationsInput $input, OrganizationSheet $organization, string $memberId, \DateTimeImmutable $now): WriteObservations => new WriteObservations($memberId, $organization->id, $input->observations, $now),
        );
    }
}
