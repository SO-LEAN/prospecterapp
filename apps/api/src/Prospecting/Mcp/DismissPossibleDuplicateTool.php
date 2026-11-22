<?php

declare(strict_types=1);

namespace App\Prospecting\Mcp;

use App\Shared\Mcp\Adapts;
use Mcp\Schema\ToolAnnotations;
use Mcp\Server\Capability\Attribute\McpTool;
use Prospecter\Core\Prospecting\Application\DismissPossibleDuplicate\DismissPossibleDuplicate;
use Prospecter\Core\Prospecting\Application\OrganizationSheet;

/**
 * `DELETE /api/organizations/{id}/possible-duplicates/{duplicateId}`, for an AI (ADR 0163), which asks its member
 * before calling it.
 */
#[Adapts('api_organizations_dismiss_possible_duplicate')]
final readonly class DismissPossibleDuplicateTool
{
    public function __construct(private OrganizationWrites $writes)
    {
    }

    /**
     * @param string $id          the id of the organization
     * @param string $duplicateId the id of the organization it resembles, named among its possible duplicates
     *
     * @return array<string, mixed>
     */
    #[McpTool(name: 'dismiss-possible-duplicate-organization', description: 'Say, once checked, that an organization is no duplicate of the one it resembles; neither is flagged for the other any more. Answers the organization.', annotations: new ToolAnnotations(readOnlyHint: false, destructiveHint: true, idempotentHint: false, openWorldHint: false))]
    public function __invoke(string $id, string $duplicateId): array
    {
        return $this->writes->change(
            $id,
            static fn (OrganizationSheet $organization, string $memberId, \DateTimeImmutable $now): DismissPossibleDuplicate => new DismissPossibleDuplicate(prospectorId: $memberId, id: $organization->id, duplicateId: $duplicateId, executedAt: $now),
        );
    }
}
