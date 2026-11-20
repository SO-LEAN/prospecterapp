<?php

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
     * @param string  $tags  the tags chosen, 1 to 5, by their names without their mark, separated by commas (ESN,EMAIL)
     * @param ?string $after the cursor of the next slice, read in the link `next` of the slice before
     *
     * @return array<string, mixed>
     */
    #[McpTool(name: 'rechercher-contacts-par-tags', description: 'Les contacts portant chacun des tags choisis (1 à 5, leurs noms sans leur marque, séparés par des virgules), comptés, avec leurs organisations et leurs tags, les plus récents d\'abord, par tranches. Les tags proposés : lire-tags-proposes.', annotations: new ToolAnnotations(readOnlyHint: true))]
    public function __invoke(string $tags, ?string $after = null): array
    {
        $this->access->denyUnlessGranted('ROLE_PROSPECTOR');
        $query = $this->inputs->valid(new TaggedContactsQuery($tags, $after));

        return $this->results->of($this->contacts->search($this->access->member()->memberId(), $query->chosen(), $query->tags, $query->after));
    }
}
