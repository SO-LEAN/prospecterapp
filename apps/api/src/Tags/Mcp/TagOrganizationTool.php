<?php

namespace App\Tags\Mcp;

use App\Prospecting\OrganizationResolver;
use App\Prospecting\OrganizationVoter;
use App\Security\ToolAccess;
use App\Shared\CommandBus;
use App\Shared\Mcp\Adapts;
use App\Shared\Mcp\ToolInputs;
use App\Shared\Mcp\ToolResults;
use App\Tags\Input\TagsInput;
use App\Tags\TagRoutes;
use App\Tags\TagsReader;
use Mcp\Schema\ToolAnnotations;
use Mcp\Server\Capability\Attribute\McpTool;
use Prospecter\Core\Campaigns\Application\PutTags\PutTags;
use Symfony\Component\Clock\ClockInterface;

/**
 * `POST /api/organizations/{id}/tags`, for an AI (ADR 0163).
 */
#[Adapts('api_organizations_tag')]
final readonly class TagOrganizationTool
{
    public function __construct(
        private ToolAccess $access,
        private OrganizationResolver $organizations,
        private ToolInputs $inputs,
        private CommandBus $commandBus,
        private ClockInterface $clock,
        private TagsReader $tagsReader,
        private ToolResults $results,
    ) {
    }

    /**
     * @param string       $id   the id of the organization
     * @param list<string> $tags each written one way, with its mark or not: `éditeur logiciel` is #EDITEUR_LOGICIEL
     *
     * @return array<string, mixed>
     */
    #[McpTool(name: 'tag-organization', description: 'Put tags on an organization, which its contacts inherit, a tag it carries already carried once. Answers its tags.', annotations: new ToolAnnotations(readOnlyHint: false, destructiveHint: false, idempotentHint: true, openWorldHint: false))]
    public function __invoke(string $id, array $tags): array
    {
        $organization = $this->organizations->read($id);
        $this->access->denyUnlessGranted('ROLE_PROSPECTOR');
        $this->access->denyUnlessGranted(OrganizationVoter::ACCESS, $organization);
        $input = $this->inputs->valid(new TagsInput($tags));
        $memberId = $this->access->member()->memberId();
        $this->commandBus->dispatch(new PutTags($memberId, TagRoutes::Organization->value, $organization->id, $input->tags, $this->clock->now()));

        return $this->results->of($this->tagsReader->read($memberId, $organization->id));
    }
}
