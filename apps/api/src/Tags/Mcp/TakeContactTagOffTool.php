<?php

declare(strict_types=1);

namespace App\Tags\Mcp;

use App\Security\ToolAccess;
use App\Shared\CommandBus;
use App\Shared\Mcp\Adapts;
use App\Shared\Mcp\ToolResults;
use App\Tags\ContactTagsReader;
use App\Tags\TagHolder;
use Mcp\Schema\ToolAnnotations;
use Mcp\Server\Capability\Attribute\McpTool;
use Prospecter\Core\Campaigns\Application\Tagging\TakeOffTag\TakeOffTag;
use Symfony\Component\Clock\ClockInterface;

/** `DELETE /api/contacts/{id}/tags/{tag}`, for an AI (ADR 0163). The AI asks its member before it calls this tool. */
#[Adapts('api_contacts_take_off_tag')]
final readonly class TakeContactTagOffTool
{
    public function __construct(
        private ToolAccess $access,
        private CommandBus $commandBus,
        private ClockInterface $clock,
        private ContactTagsReader $contactTagsReader,
        private ToolResults $results,
    ) {
    }

    /**
     * @param string $id  the contact's id
     * @param string $tag the tag put by hand, named without its `#`: `PHP` for #PHP
     *
     * @return array<string, mixed>
     */
    #[McpTool(name: 'untag-contact', description: 'Take a tag put by hand off a contact. Returns the contact\'s tags.', annotations: new ToolAnnotations(readOnlyHint: false, destructiveHint: true, idempotentHint: false, openWorldHint: false))]
    public function __invoke(string $id, string $tag): array
    {
        $this->access->denyUnlessGranted('ROLE_PROSPECTOR');
        $memberId = $this->access->member()->memberId();
        $this->commandBus->dispatch(new TakeOffTag(memberId: $memberId, holder: TagHolder::Contact->value, holderId: $id, tag: $tag, executedAt: $this->clock->now()));

        return $this->results->of($this->contactTagsReader->read($memberId, $id));
    }
}
