<?php

namespace App\Prospecting\Mcp;

use App\Prospecting\OrganizationResolver;
use App\Prospecting\OrganizationVoter;
use App\Prospecting\Presenter\EstablishmentsPresenter;
use App\Security\ToolAccess;
use App\Shared\Mcp\Adapts;
use App\Shared\Mcp\ToolResults;
use Mcp\Schema\ToolAnnotations;
use Mcp\Server\Capability\Attribute\McpTool;
use Prospecter\Core\Prospecting\Application\SearchEstablishments\SearchEstablishments;
use Prospecter\Core\SharedKernel\Application\QueryHandler;

/**
 * `GET /api/organizations/{id}/establishments`, for an AI (ADR 0163).
 */
#[Adapts('api_organizations_establishments')]
final readonly class SearchEstablishmentsTool
{
    public function __construct(
        private ToolAccess $access,
        private OrganizationResolver $organizations,
        private QueryHandler $searchEstablishments,
        private ToolResults $results,
    ) {
    }

    /**
     * @param string $id the id of the organization
     *
     * @return array<string, mixed>
     */
    #[McpTool(name: 'chercher-etablissements', description: 'Les établissements d\'une organisation que propose l\'annuaire public des entreprises, par son nom : SIRET, adresse, activité, fermé ou non.', annotations: new ToolAnnotations(readOnlyHint: true))]
    public function __invoke(string $id): array
    {
        $organization = $this->organizations->read($id);
        $this->access->denyUnlessGranted('ROLE_PROSPECTOR');
        $this->access->denyUnlessGranted(OrganizationVoter::ACCESS, $organization);
        $presenter = new EstablishmentsPresenter();
        ($this->searchEstablishments)(new SearchEstablishments($organization->id), $presenter);

        return $this->results->of($presenter->resource);
    }
}
