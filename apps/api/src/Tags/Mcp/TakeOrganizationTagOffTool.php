<?php

declare(strict_types=1);

namespace App\Tags\Mcp;

use App\Prospecting\OrganizationResolver;
use App\Prospecting\OrganizationVoter;
use App\Security\ToolAccess;
use App\Shared\CommandBus;
use App\Shared\Mcp\Adapts;
use App\Shared\Mcp\ToolResults;
use App\Tags\TagRoutes;
use App\Tags\TagsReader;
use Mcp\Schema\ToolAnnotations;
use Mcp\Server\Capability\Attribute\McpTool;
use Prospecter\Core\Campaigns\Application\TakeOffTag\TakeOffTag;
use Symfony\Component\Clock\ClockInterface;

/**
 * `DELETE /api/organizations/{id}/tags/{tag}`, for an AI (ADR 0163), which asks its member before calling it.
 */
#[Adapts('api_organizations_take_off_tag')]
final readonly class TakeOrganizationTagOffTool
{
    public function __construct(
        private ToolAccess $access,
        private OrganizationResolver $organizations,
        private CommandBus $commandBus,
        private ClockInterface $clock,
        private TagsReader $tagsReader,
        private ToolResults $results,
    ) {
    }

    /**
     * @param string $id  the id of the organization
     * @param string $tag the tag put by hand, named without its mark: `ESN` for #ESN
     *
     * @return array<string, mixed>
     */
    #[McpTool(name: 'untag-organization', description: 'Take a tag put by hand off an organization; its contacts no longer inherit it. Answers its tags.', annotations: new ToolAnnotations(readOnlyHint: false, destructiveHint: true, idempotentHint: false, openWorldHint: false))]
    public function __invoke(string $id, string $tag): array
    {
        $organization = $this->organizations->read($id);
        $this->access->denyUnlessGranted('ROLE_PROSPECTOR');
        $this->access->denyUnlessGranted(OrganizationVoter::ACCESS, $organization);
        $memberId = $this->access->member()->memberId();
        $this->commandBus->dispatch(new TakeOffTag(memberId: $memberId, holder: TagRoutes::Organization->value, holderId: $organization->id, tag: $tag, executedAt: $this->clock->now()));

        return $this->results->of($this->tagsReader->read($memberId, $organization->id));
    }
}
