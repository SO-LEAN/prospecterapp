<?php

declare(strict_types=1);

namespace App\Contacts\Mcp;

use App\Contacts\Input\TaggedContactsQuery;
use App\Contacts\TaggedContactsReader;
use App\Security\ToolAccess;
use App\Shared\Mcp\Adapts;
use App\Shared\Mcp\ToolInputs;
use App\Shared\Mcp\ToolResults;
use Mcp\Schema\ToolAnnotations;
use Mcp\Server\Capability\Attribute\McpTool;

/**
 * `GET /api/tagged-contacts`, for an AI (ADR 0163).
 */
#[Adapts('api_contacts_search_by_tags')]
final readonly class SearchContactsByTagsTool
{
    public function __construct(
        private ToolAccess $access,
        private ToolInputs $inputs,
        private TaggedContactsReader $contacts,
        private ToolResults $results,
    ) {
    }

    /**
     * @param string  $tags  the chosen tags, 1 to 5: their names without their mark, separated by commas (ESN,EMAIL)
     * @param ?string $after the next slice's cursor, read in the previous slice's link `next`
     *
     * @return array<string, mixed>
     */
    #[McpTool(name: 'search-contacts-by-tags', description: 'List and count the contacts that carry every chosen tag, with their organizations and tags, the most recent first, by slices. Choose 1 to 5 tags, by their names without their mark, separated by commas. list-suggested-tags gives the proposed tags.', annotations: new ToolAnnotations(readOnlyHint: true))]
    public function __invoke(string $tags, ?string $after = null): array
    {
        $this->access->denyUnlessGranted('ROLE_PROSPECTOR');
        $query = $this->inputs->valid(new TaggedContactsQuery($tags, $after));

        return $this->results->of($this->contacts->search($this->access->member()->memberId(), $query->chosen(), $query->tags, $query->after));
    }
}
