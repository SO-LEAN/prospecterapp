<?php

declare(strict_types=1);

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
     * @param string  $q     the start of the searched contacts' name, or their email with the right to manage personal data. An empty value finds them all
     * @param ?string $after the next slice's cursor, read in the previous slice's link `next`
     *
     * @return array<string, mixed>
     */
    #[McpTool(name: 'search-contacts', description: 'Search the account\'s contacts by the start of their name, with their organizations and tags, the most recent first, by slices. With the right to manage personal data, it also searches by e-mail.', annotations: new ToolAnnotations(readOnlyHint: true))]
    public function __invoke(string $q = '', ?string $after = null): array
    {
        $this->access->denyUnlessGranted('ROLE_PROSPECTOR');
        $query = $this->inputs->valid(new ContactListQuery($q, $after));

        return $this->results->of($this->contacts->search($this->access->member()->memberId(), $query->q, $query->after));
    }
}
