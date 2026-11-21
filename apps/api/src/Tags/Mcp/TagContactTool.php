<?php

namespace App\Tags\Mcp;

use App\Security\ToolAccess;
use App\Shared\CommandBus;
use App\Shared\Mcp\Adapts;
use App\Shared\Mcp\ToolInputs;
use App\Shared\Mcp\ToolResults;
use App\Tags\ContactTagsReader;
use App\Tags\Input\TagsInput;
use App\Tags\TagRoutes;
use Mcp\Schema\ToolAnnotations;
use Mcp\Server\Capability\Attribute\McpTool;
use Prospecter\Core\Campaigns\Application\PutTags\PutTags;
use Symfony\Component\Clock\ClockInterface;

/**
 * `POST /api/contacts/{id}/tags`, for an AI (ADR 0163).
 */
#[Adapts('api_contacts_tag')]
final readonly class TagContactTool
{
    public function __construct(
        private ToolAccess $access,
        private ToolInputs $inputs,
        private CommandBus $commandBus,
        private ClockInterface $clock,
        private ContactTagsReader $contactTagsReader,
        private ToolResults $results,
    ) {
    }

    /**
     * @param string       $id   the id of the contact
     * @param list<string> $tags each written one way, with its mark or not: `éditeur logiciel` is #EDITEUR_LOGICIEL
     *
     * @return array<string, mixed>
     */
    #[McpTool(name: 'tag-contact', description: 'Put tags on a contact, a tag they carry already carried once. Answers their tags.', annotations: new ToolAnnotations(readOnlyHint: false, destructiveHint: false, idempotentHint: true, openWorldHint: false))]
    public function __invoke(string $id, array $tags): array
    {
        $this->access->denyUnlessGranted('ROLE_PROSPECTOR');
        $input = $this->inputs->valid(new TagsInput($tags));
        $memberId = $this->access->member()->memberId();
        $this->commandBus->dispatch(new PutTags($memberId, TagRoutes::Contact->value, $id, $input->tags, $this->clock->now()));

        return $this->results->of($this->contactTagsReader->read($memberId, $id));
    }
}
