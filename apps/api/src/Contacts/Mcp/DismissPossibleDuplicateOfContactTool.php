<?php

namespace App\Contacts\Mcp;

use App\Contacts\ContactReader;
use App\Security\ToolAccess;
use App\Shared\CommandBus;
use App\Shared\Mcp\Adapts;
use App\Shared\Mcp\ToolResults;
use Mcp\Schema\ToolAnnotations;
use Mcp\Server\Capability\Attribute\McpTool;
use Prospecter\Core\PersonalData\Application\DismissPossibleDuplicate\DismissPossibleDuplicate;
use Symfony\Component\Clock\ClockInterface;

/**
 * `DELETE /api/contacts/{id}/possible-duplicates/{duplicateId}`, for an AI (ADR 0163), which asks its member before
 * calling it.
 */
#[Adapts('api_contacts_dismiss_possible_duplicate')]
final readonly class DismissPossibleDuplicateOfContactTool
{
    public function __construct(
        private ToolAccess $access,
        private CommandBus $commandBus,
        private ClockInterface $clock,
        private ContactReader $contactReader,
        private ToolResults $results,
    ) {
    }

    /**
     * @param string $id          the id of the contact
     * @param string $duplicateId the id of the contact they resemble, named among their possible duplicates
     *
     * @return array<string, mixed>
     */
    #[McpTool(name: 'dismiss-possible-duplicate-contact', description: 'Say, once checked, that a contact is no duplicate of the one they resemble; neither is flagged for the other any more. Needs the right to manage the personal data. Answers the contact.', annotations: new ToolAnnotations(readOnlyHint: false, destructiveHint: true, idempotentHint: false, openWorldHint: false))]
    public function __invoke(string $id, string $duplicateId): array
    {
        $this->access->denyUnlessGranted('ROLE_PROSPECTOR');
        $memberId = $this->access->member()->memberId();
        $this->commandBus->dispatch(new DismissPossibleDuplicate(readerId: $memberId, id: $id, duplicateId: $duplicateId, executedAt: $this->clock->now()));

        return $this->results->of($this->contactReader->read($memberId, $id));
    }
}
