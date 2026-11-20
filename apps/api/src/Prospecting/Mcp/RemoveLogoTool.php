<?php

namespace App\Prospecting\Mcp;

use App\Shared\Mcp\Adapts;
use Mcp\Schema\ToolAnnotations;
use Mcp\Server\Capability\Attribute\McpTool;
use Prospecter\Core\Prospecting\Application\OrganizationSheet;
use Prospecter\Core\Prospecting\Application\RemoveLogo\RemoveLogo;

/**
 * `DELETE /api/organizations/{id}/logo`, for an AI (ADR 0163), which asks its member before calling it.
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
    #[McpTool(name: 'retirer-logo-organisation', description: 'Remove the logo of an organization. Answers the organization.', annotations: new ToolAnnotations(readOnlyHint: false, destructiveHint: true, idempotentHint: false, openWorldHint: false))]
    public function __invoke(string $id): array
    {
        return $this->writes->write(
            $id,
            null,
            static fn (null $input, OrganizationSheet $organization, string $memberId, \DateTimeImmutable $now): RemoveLogo => new RemoveLogo($memberId, $organization->id, $now),
        );
    }
}
