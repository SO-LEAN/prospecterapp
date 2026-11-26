<?php

declare(strict_types=1);

namespace App\Contacts\Mcp;

use App\Contacts\OrganizationContactsPresenter;
use App\Prospecting\OrganizationResolver;
use App\Prospecting\OrganizationVoter;
use App\Security\IntentLinks;
use App\Security\ToolAccess;
use App\Shared\Links;
use App\Shared\Mcp\Adapts;
use App\Shared\Mcp\ToolResults;
use Mcp\Schema\ToolAnnotations;
use Mcp\Server\Capability\Attribute\McpTool;
use Prospecter\Core\Prospecting\Application\Contact\ListOrganizationContacts\ListOrganizationContacts;
use Prospecter\Core\SharedKernel\Application\Handler\QueryHandler;

/**
 * `GET /api/organizations/{id}/contacts`, for an AI (ADR 0163).
 */
#[Adapts('api_organizations_contacts')]
final readonly class ShowOrganizationContactsTool
{
    public function __construct(
        private ToolAccess $access,
        private OrganizationResolver $organizations,
        private QueryHandler $listOrganizationContacts,
        private Links $links,
        private IntentLinks $intentLinks,
        private ToolResults $results,
    ) {
    }

    /**
     * @param string $id the organization's id
     *
     * @return array<string, mixed>
     */
    #[McpTool(name: 'list-organization-contacts', description: 'List an organization\'s contacts, unmasked or masked as the member reads them.', annotations: new ToolAnnotations(readOnlyHint: true))]
    public function __invoke(string $id): array
    {
        $organization = $this->organizations->read($id);
        $this->access->denyUnlessGranted('ROLE_PROSPECTOR');
        $this->access->denyUnlessGranted(OrganizationVoter::ACCESS, $organization);

        $presenter = new OrganizationContactsPresenter($this->links, $this->intentLinks);
        $this->listOrganizationContacts->__invoke(new ListOrganizationContacts($this->access->member()->memberId(), $organization->id), $presenter);

        return $this->results->of($presenter->resource);
    }
}
