<?php

declare(strict_types=1);

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
 * `DELETE /api/contacts/{id}/possible-duplicates/{duplicateId}`, for an AI (ADR 0163). The AI asks its member before
 * it calls this tool.
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
     * @param string $id          the contact's id
     * @param string $duplicateId the id of the contact they resemble, as named among their possible duplicates
     *
     * @return array<string, mixed>
     */
    #[McpTool(name: 'dismiss-possible-duplicate-contact', description: 'Says, once checked, that a contact is not a duplicate of the one they resemble. Neither of them is flagged for the other any more. It needs the right to manage personal data. It returns the contact.', annotations: new ToolAnnotations(readOnlyHint: false, destructiveHint: true, idempotentHint: false, openWorldHint: false))]
    public function __invoke(string $id, string $duplicateId): array
    {
        $this->access->denyUnlessGranted('ROLE_PROSPECTOR');
        $memberId = $this->access->member()->memberId();
        $this->commandBus->dispatch(new DismissPossibleDuplicate(readerId: $memberId, id: $id, duplicateId: $duplicateId, executedAt: $this->clock->now()));

        return $this->results->of($this->contactReader->read($memberId, $id));
    }
}
