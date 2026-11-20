<?php

namespace App\Contacts\Mcp;

use App\Contacts\ContactSearchReader;
use App\PersonalData\Input\ContactListQuery;
use App\Security\ToolAccess;
use App\Shared\Mcp\Adapts;
use App\Shared\Mcp\ToolInputs;
use App\Shared\Mcp\ToolResults;
use Mcp\Schema\ToolAnnotations;
use Mcp\Server\Capability\Attribute\McpTool;

/**
 * `GET /api/contacts`, for an AI (ADR 0163).
 */
#[Adapts('api_contacts_search')]
final readonly class SearchContactsTool
{
    public function __construct(
        private ToolAccess $access,
        private ToolInputs $inputs,
        private ContactSearchReader $contacts,
        private ToolResults $results,
    ) {
    }

    /**
     * @param string  $q     the start of the name of the contacts searched, or their email with the right to manage the personal data; all when empty
     * @param ?string $after the cursor of the next slice, read in the link `next` of the slice before
     *
     * @return array<string, mixed>
     */
    #[McpTool(name: 'rechercher-contacts', description: 'The contacts of the account, by the start of their name (or their e-mail, with the right to manage the personal data), with their organizations and tags, the most recent first, by slices.', annotations: new ToolAnnotations(readOnlyHint: true))]
    public function __invoke(string $q = '', ?string $after = null): array
    {
        $this->access->denyUnlessGranted('ROLE_PROSPECTOR');
        $query = $this->inputs->valid(new ContactListQuery($q, $after));

        return $this->results->of($this->contacts->search($this->access->member()->memberId(), $query->q, $query->after));
    }
}
