<?php

declare(strict_types=1);

namespace App\Prospecting\Mcp;

use App\Prospecting\Input\ReviseLegalIdentityInput;
use App\Shared\Mcp\Adapts;
use Mcp\Schema\ToolAnnotations;
use Mcp\Server\Capability\Attribute\McpTool;
use Prospecter\Core\Prospecting\Application\OrganizationSheet;
use Prospecter\Core\Prospecting\Application\ReviseLegalIdentity\ReviseLegalIdentity;

/**
 * `PATCH /api/organizations/{id}/legal-identity`, for an AI (ADR 0163).
 */
#[Adapts('api_organizations_revise_legal_identity')]
final readonly class ReviseLegalIdentityTool
{
    public function __construct(private OrganizationWrites $writes)
    {
    }

    /**
     * @param string  $id       the id of the organization
     * @param ?string $form     its legal form (SAS, SARL…)
     * @param ?string $siret    its SIRET, 14 digits
     * @param ?int    $revision the organization's `revision` as you read it. If it has changed since, the change is refused (changedMeanwhile): read the organization again
     *
     * @return array<string, mixed>
     */
    #[McpTool(name: 'update-organization-identity', description: 'Revise an organization\'s legal identity: its corporate name, form, type and SIRET. Nothing else changes. A value left out is removed. Returns the organization.', annotations: new ToolAnnotations(readOnlyHint: false, destructiveHint: false, idempotentHint: true, openWorldHint: false))]
    public function __invoke(string $id, ?string $corporateName = null, ?string $form = null, ?string $type = null, ?string $siret = null, ?int $revision = null): array
    {
        return $this->writes->writeOnRevision(
            $id,
            $revision,
            new ReviseLegalIdentityInput($corporateName, $form, $type, $siret),
            static fn (ReviseLegalIdentityInput $input, OrganizationSheet $organization, string $memberId, \DateTimeImmutable $now): ReviseLegalIdentity => new ReviseLegalIdentity(prospectorId: $memberId, id: $organization->id, corporateName: $input->corporateName, form: $input->form, type: $input->type, executedAt: $now, siret: $input->siret),
        );
    }
}
