<?php

declare(strict_types=1);

namespace App\Contacts\Mcp;

use App\Contacts\ContactComposition;
use App\PersonalData\Input\TitleInput;
use App\PersonalData\Persistence\PersonRevisions;
use App\Security\ToolAccess;
use App\Shared\CommandBus;
use App\Shared\Mcp\Adapts;
use App\Shared\Mcp\ToolInputs;
use App\Shared\Mcp\ToolResults;
use Mcp\Schema\ToolAnnotations;
use Mcp\Server\Capability\Attribute\McpTool;
use Prospecter\Core\PersonalData\Application\Person\ChangeCivility\ChangeCivility;
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
        private ContactComposition $contactComposition,
        private ToolResults $results,
        private PersonRevisions $revisions,
    ) {
    }

    /**
     * @param string $id       the contact's id
     * @param string $title    how to address the contact: ms, mr, or x when unknown
     * @param ?int   $revision the contact's `revision` as you read it. If it changed since, the change is refused (changedMeanwhile) and you read the contact again
     *
     * @return array<string, mixed>
     */
    #[McpTool(name: 'set-contact-title', description: 'Set how to address a contact: Ms, Mr, or unknown (x). It needs the right to manage personal data. Returns the contact.', annotations: new ToolAnnotations(readOnlyHint: false, destructiveHint: false, idempotentHint: true, openWorldHint: false))]
    public function __invoke(string $id, string $title = 'x', ?int $revision = null): array
    {
        $this->access->denyUnlessGranted('ROLE_PROSPECTOR');
        $input = $this->inputs->valid(new TitleInput($title));
        $memberId = $this->access->member()->memberId();
        $this->commandBus->dispatch(new ChangeCivility(readerId: $memberId, id: $id, civility: $input->title, executedAt: $this->clock->now()), $this->revisions->readAt($id, $revision));

        return $this->results->of($this->contactComposition->read($memberId, $id));
    }
}
