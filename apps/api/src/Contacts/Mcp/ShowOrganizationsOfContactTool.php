<?php

declare(strict_types=1);

namespace App\Contacts\Mcp;

use App\Contacts\OrganizationsOfContactPresenter;
use App\Security\ToolAccess;
use App\Shared\Links;
use App\Shared\Mcp\Adapts;
use App\Shared\Mcp\ToolResults;
use Mcp\Schema\ToolAnnotations;
use Mcp\Server\Capability\Attribute\McpTool;
use Prospecter\Core\Prospecting\Application\Contact\ConsultOrganizationsOfContacts\ConsultOrganizationsOfContacts;
use Prospecter\Core\SharedKernel\Application\Handler\QueryHandler;

/**
 * `GET /api/contacts/{id}/organizations`, for an AI (ADR 0163).
 */
#[Adapts('api_contacts_organizations')]
final readonly class ShowOrganizationsOfContactTool
{
    public function __construct(private ToolAccess $access, private QueryHandler $consultOrganizationsOfContacts, private Links $links, private ToolResults $results)
    {
    }

    /**
     * @param string $id the contact's id
     *
     * @return array<string, mixed>
     */
    #[McpTool(name: 'list-contact-organizations', description: 'List the organizations a contact is attached to.', annotations: new ToolAnnotations(readOnlyHint: true))]
    public function __invoke(string $id): array
    {
        $this->access->denyUnlessGranted('ROLE_PROSPECTOR');

        $presenter = new OrganizationsOfContactPresenter($id, $this->links);
        $this->consultOrganizationsOfContacts->__invoke(new ConsultOrganizationsOfContacts($this->access->member()->memberId(), [$id]), $presenter);

        return $this->results->of($presenter->resource);
    }
}
