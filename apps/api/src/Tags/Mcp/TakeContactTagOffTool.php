<?php

namespace App\Tags\Mcp;

use App\Security\ToolAccess;
use App\Shared\CommandBus;
use App\Shared\Mcp\Adapts;
use App\Shared\Mcp\ToolResults;
use App\Tags\ContactTagsReader;
use App\Tags\TagRoutes;
use Mcp\Schema\ToolAnnotations;
use Mcp\Server\Capability\Attribute\McpTool;
use Prospecter\Core\Campaigns\Application\TakeOffTag\TakeOffTag;
use Symfony\Component\Clock\ClockInterface;

/**
 * `DELETE /api/contacts/{id}/tags/{tag}`, for an AI (ADR 0163), which asks its member before calling it.
 */
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
     * @param string $id  the id of the contact
     * @param string $tag the tag put by hand, named without its mark: `PHP` for #PHP
     *
     * @return array<string, mixed>
     */
    #[McpTool(name: 'retirer-tag-contact', description: 'Take a tag put by hand off a contact. Answers their tags.', annotations: new ToolAnnotations(readOnlyHint: false, destructiveHint: true, idempotentHint: false, openWorldHint: false))]
    public function __invoke(string $id, string $tag): array
    {
        $this->access->denyUnlessGranted('ROLE_PROSPECTOR');
        $memberId = $this->access->member()->memberId();
        $this->commandBus->dispatch(new TakeOffTag($memberId, TagRoutes::Contact->value, $id, $tag, $this->clock->now()));

        return $this->results->of($this->contactTagsReader->read($memberId, $id));
    }
}
