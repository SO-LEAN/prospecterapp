<?php

namespace App\Tags\Mcp;

use App\Security\ToolAccess;
use App\Shared\Mcp\ToolResults;
use App\Tags\SuggestedTagsPresenter;
use Mcp\Schema\ToolAnnotations;
use Mcp\Server\Capability\Attribute\McpTool;
use Prospecter\Core\Campaigns\Application\ConsultSuggestedTags\ConsultSuggestedTags;
use Prospecter\Core\SharedKernel\Application\QueryHandler;

/**
 * `GET /api/tags`, for an AI (ADR 0163).
 */
final readonly class ShowSuggestedTagsTool
{
    public function __construct(private ToolAccess $access, private QueryHandler $consultSuggestedTags, private ToolResults $results)
    {
    }

    /**
     * @return array<string, mixed>
     */
    #[McpTool(name: 'lire-tags-proposes', description: 'Les tags proposés au compte, pour chercher ses contacts : les prédéfinis par famille, les siens, et à part les automatiques.', annotations: new ToolAnnotations(readOnlyHint: true))]
    public function __invoke(): array
    {
        $this->access->denyUnlessGranted('ROLE_PROSPECTOR');
        $presenter = new SuggestedTagsPresenter();
        ($this->consultSuggestedTags)(new ConsultSuggestedTags($this->access->member()->memberId()), $presenter);

        return $this->results->of($presenter->resource);
    }
}
