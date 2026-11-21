<?php

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
     * @param string  $id            the id of the organization
     * @param ?string $corporateName its corporate name
     * @param ?string $form          its legal form (SAS, SARL…)
     * @param ?string $type          its type
     * @param ?string $siret         its SIRET, 14 digits
     *
     * @return array<string, mixed>
     */
    #[McpTool(name: 'update-organization-identity', description: 'Revise the legal identity of an organization (corporate name, form, type, SIRET), nothing else changes; what is left out is removed. Answers the organization.', annotations: new ToolAnnotations(readOnlyHint: false, destructiveHint: false, idempotentHint: true, openWorldHint: false))]
    public function __invoke(string $id, ?string $corporateName = null, ?string $form = null, ?string $type = null, ?string $siret = null): array
    {
        return $this->writes->write(
            $id,
            new ReviseLegalIdentityInput($corporateName, $form, $type, $siret),
            static fn (ReviseLegalIdentityInput $input, OrganizationSheet $organization, string $memberId, \DateTimeImmutable $now): ReviseLegalIdentity => new ReviseLegalIdentity($memberId, $organization->id, $input->corporateName, $input->form, $input->type, $now, $input->siret),
        );
    }
}
