<?php

namespace App\Contacts\Mcp;

use App\Contacts\ContactReader;
use App\PersonalData\Input\TitleInput;
use App\PersonalData\Persistence\PersonRevisions;
use App\Security\ToolAccess;
use App\Shared\CommandBus;
use App\Shared\Mcp\Adapts;
use App\Shared\Mcp\ToolInputs;
use App\Shared\Mcp\ToolResults;
use Mcp\Schema\ToolAnnotations;
use Mcp\Server\Capability\Attribute\McpTool;
use Prospecter\Core\PersonalData\Application\ChangeCivility\ChangeCivility;
use Symfony\Component\Clock\ClockInterface;

/**
 * `PATCH /api/contacts/{id}/title`, for an AI (ADR 0163).
 */
#[Adapts('api_contacts_change_title')]
final readonly class ChangeCivilityTool
{
    public function __construct(
        private ToolAccess $access,
        private ToolInputs $inputs,
        private CommandBus $commandBus,
        private ClockInterface $clock,
        private ContactReader $contactReader,
        private ToolResults $results,
        private PersonRevisions $revisions,
    ) {
    }

    /**
     * @param string $id       the id of the contact
     * @param string $title    how one addresses the contact: ms, mr, or x when unknown
     * @param ?int   $revision the contact's `revision` as you read it: if it changed since, the change is refused (changedMeanwhile) and you read it again
     *
     * @return array<string, mixed>
     */
    #[McpTool(name: 'set-contact-title', description: 'Note how one addresses a contact, Ms or Mr, or that it is unknown (x); needs the right to manage the personal data. Answers the contact.', annotations: new ToolAnnotations(readOnlyHint: false, destructiveHint: false, idempotentHint: true, openWorldHint: false))]
    public function __invoke(string $id, string $title = 'x', ?int $revision = null): array
    {
        $this->access->denyUnlessGranted('ROLE_PROSPECTOR');
        $input = $this->inputs->valid(new TitleInput($title));
        $memberId = $this->access->member()->memberId();
        $this->commandBus->dispatch(new ChangeCivility(readerId: $memberId, id: $id, civility: $input->title, executedAt: $this->clock->now()), $this->revisions->readAt($id, $revision));

        return $this->results->of($this->contactReader->read($memberId, $id));
    }
}
