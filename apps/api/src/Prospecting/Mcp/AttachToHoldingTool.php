<?php

declare(strict_types=1);

namespace App\Prospecting\Mcp;

use App\Prospecting\Input\HoldingInput;
use App\Shared\Mcp\Adapts;
use Mcp\Schema\ToolAnnotations;
use Mcp\Server\Capability\Attribute\McpTool;
use Prospecter\Core\Prospecting\Application\AttachToHolding\AttachToHolding;
use Prospecter\Core\Prospecting\Application\OrganizationSheet;

/**
 * `PATCH /api/organizations/{id}/holding`, for an AI (ADR 0163).
 */
#[Adapts('api_organizations_attach_to_holding')]
final readonly class AttachToHoldingTool
{
    public function __construct(private OrganizationWrites $writes)
    {
    }

    /**
     * @param string $id        the id of the organization
     * @param string $holdingId the id of its holding, an organization the account prospects
     *
     * @return array<string, mixed>
     */
    #[McpTool(name: 'attach-to-holding', description: 'Attach an organization to its holding, an organization the account prospects. Answers the organization.', annotations: new ToolAnnotations(readOnlyHint: false, destructiveHint: false, idempotentHint: true, openWorldHint: false))]
    public function __invoke(string $id, string $holdingId): array
    {
        return $this->writes->write(
            $id,
            new HoldingInput($holdingId),
            static fn (HoldingInput $input, OrganizationSheet $organization, string $memberId, \DateTimeImmutable $now): AttachToHolding => new AttachToHolding(prospectorId: $memberId, id: $organization->id, holdingId: $input->holdingId, executedAt: $now),
        );
    }
}
