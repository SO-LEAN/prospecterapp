<?php

declare(strict_types=1);

namespace App\Tags\Mcp;

use App\Security\ToolAccess;
use App\Shared\Mcp\Adapts;
use App\Shared\Mcp\ToolResults;
use App\Tags\SuggestedTagsPresenter;
use Mcp\Schema\ToolAnnotations;
use Mcp\Server\Capability\Attribute\McpTool;
use Prospecter\Core\Campaigns\Application\ConsultSuggestedTags\ConsultSuggestedTags;
use Prospecter\Core\SharedKernel\Application\QueryHandler;

/**
 * `GET /api/tags`, for an AI (ADR 0163).
 */
#[Adapts('api_tags')]
final readonly class ShowSuggestedTagsTool
{
    public function __construct(private ToolAccess $access, private QueryHandler $consultSuggestedTags, private ToolResults $results)
    {
    }

    /**
     * @return array<string, mixed>
     */
    #[McpTool(name: 'list-suggested-tags', description: 'Get the tags proposed to the account, to search its contacts by. Returns the predefined tags by family, the account\'s own tags, and the automatic tags separately.', annotations: new ToolAnnotations(readOnlyHint: true))]
    public function __invoke(): array
    {
        $this->access->denyUnlessGranted('ROLE_PROSPECTOR');
        $presenter = new SuggestedTagsPresenter();
        $this->consultSuggestedTags->__invoke(new ConsultSuggestedTags($this->access->member()->memberId()), $presenter);

        return $this->results->of($presenter->resource);
    }
}
