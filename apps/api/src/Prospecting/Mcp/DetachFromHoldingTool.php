<?php

namespace App\Prospecting\Mcp;

use App\Shared\Mcp\Adapts;
use Mcp\Schema\ToolAnnotations;
use Mcp\Server\Capability\Attribute\McpTool;
use Prospecter\Core\Prospecting\Application\DetachFromHolding\DetachFromHolding;
use Prospecter\Core\Prospecting\Application\OrganizationSheet;

/**
 * `DELETE /api/organizations/{id}/holding`, for an AI (ADR 0163), which asks its member before calling it.
 */
#[Adapts('api_organizations_detach_from_holding')]
final readonly class DetachFromHoldingTool
{
    public function __construct(private OrganizationWrites $writes)
    {
    }

    /**
     * @param string $id the id of the organization
     *
     * @return array<string, mixed>
     */
    #[McpTool(name: 'detach-from-holding', description: 'Detach an organization from its holding. Answers the organization.', annotations: new ToolAnnotations(readOnlyHint: false, destructiveHint: true, idempotentHint: false, openWorldHint: false))]
    public function __invoke(string $id): array
    {
        return $this->writes->write(
            $id,
            null,
            static fn (null $input, OrganizationSheet $organization, string $memberId, \DateTimeImmutable $now): DetachFromHolding => new DetachFromHolding($memberId, $organization->id, $now),
        );
    }
}
