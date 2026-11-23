<?php

declare(strict_types=1);

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
    #[McpTool(name: 'search-company-directory', description: 'Search the public company directory for an organization\'s establishments, by its name. Returns each establishment\'s SIRET, address and activity, and whether it has closed.', annotations: new ToolAnnotations(readOnlyHint: true))]
    public function __invoke(string $id): array
    {
        $organization = $this->organizations->read($id);
        $this->access->denyUnlessGranted('ROLE_PROSPECTOR');
        $this->access->denyUnlessGranted(OrganizationVoter::ACCESS, $organization);
        $presenter = new EstablishmentsPresenter();
        $this->searchEstablishments->__invoke(new SearchEstablishments($organization->id), $presenter);

        return $this->results->of($presenter->resource);
    }
}
