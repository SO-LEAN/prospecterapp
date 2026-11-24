<?php

declare(strict_types=1);

namespace App\Prospecting\Mcp;

use App\Shared\Mcp\Adapts;
use Mcp\Schema\ToolAnnotations;
use Mcp\Server\Capability\Attribute\McpTool;
use Prospecter\Core\Prospecting\Application\Organization\RemoveLogo\RemoveLogo;
use Prospecter\Core\Prospecting\Application\OrganizationSheet;

/**
 * `DELETE /api/organizations/{id}/logo`, for an AI (ADR 0163). The AI asks its member
 * before it calls the tool.
 */
#[Adapts('api_organizations_remove_logo')]
final readonly class RemoveLogoTool
{
    public function __construct(private OrganizationWrites $writes)
    {
    }

    /**
     * @param string $id the id of the organization
     *
     * @return array<string, mixed>
     */
    #[McpTool(name: 'delete-organization-logo', description: 'Remove an organization\'s logo. Returns the organization.', annotations: new ToolAnnotations(readOnlyHint: false, destructiveHint: true, idempotentHint: false, openWorldHint: false))]
    public function __invoke(string $id): array
    {
        return $this->writes->change(
            $id,
            static fn (OrganizationSheet $organization, string $memberId, \DateTimeImmutable $now): RemoveLogo => new RemoveLogo($memberId, $organization->id, $now),
        );
    }
}
