<?php

declare(strict_types=1);

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
     * @param ?string $observations the members' notes about it, 255 characters at most. None when empty
     * @param ?int    $revision     the organization's `revision` as you read it. If it has changed since, the change is refused (changedMeanwhile): read the organization again
     *
     * @return array<string, mixed>
     */
    #[McpTool(name: 'set-organization-notes', description: 'Write an organization\'s observations, replacing the previous ones. Returns the organization.', annotations: new ToolAnnotations(readOnlyHint: false, destructiveHint: false, idempotentHint: true, openWorldHint: false))]
    public function __invoke(string $id, ?string $observations = null, ?int $revision = null): array
    {
        return $this->writes->writeOnRevision(
            $id,
            $revision,
            new ObservationsInput($observations),
            static fn (ObservationsInput $input, OrganizationSheet $organization, string $memberId, \DateTimeImmutable $now): WriteObservations => new WriteObservations(prospectorId: $memberId, id: $organization->id, observations: $input->observations, executedAt: $now),
        );
    }
}
