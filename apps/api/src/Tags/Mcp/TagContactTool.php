<?php

declare(strict_types=1);

namespace App\Tags\Mcp;

use App\Security\ToolAccess;
use App\Shared\CommandBus;
use App\Shared\Mcp\Adapts;
use App\Shared\Mcp\ToolInputs;
use App\Shared\Mcp\ToolResults;
use App\Tags\ContactTagsReader;
use App\Tags\Input\TagsInput;
use App\Tags\TagHolder;
use Mcp\Schema\ToolAnnotations;
use Mcp\Server\Capability\Attribute\McpTool;
use Prospecter\Core\Campaigns\Application\Tagging\PutTags\PutTags;
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
     * @param string       $id   the contact's id
     * @param list<string> $tags with or without their `#`; each is written one way: `Café owners` is #CAFE_OWNERS
     *
     * @return array<string, mixed>
     */
    #[McpTool(name: 'tag-contact', description: 'Put tags on a contact. A tag they already carry is kept once. Returns the contact\'s tags.', annotations: new ToolAnnotations(readOnlyHint: false, destructiveHint: false, idempotentHint: true, openWorldHint: false))]
    public function __invoke(string $id, array $tags): array
    {
        $this->access->denyUnlessGranted('ROLE_PROSPECTOR');
        $input = $this->inputs->valid(new TagsInput($tags));
        $memberId = $this->access->member()->memberId();
        $this->commandBus->dispatch(new PutTags(memberId: $memberId, holder: TagHolder::Contact->value, holderId: $id, tags: $input->tags, executedAt: $this->clock->now()));

        return $this->results->of($this->contactTagsReader->read($memberId, $id));
    }
}
