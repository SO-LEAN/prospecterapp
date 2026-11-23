<?php

declare(strict_types=1);

namespace App\Contacts\Mcp;

use App\Contacts\ContactReader;
use App\PersonalData\Input\PersonNameInput;
use App\PersonalData\Persistence\PersonRevisions;
use App\Security\ToolAccess;
use App\Shared\CommandBus;
use App\Shared\Mcp\Adapts;
use App\Shared\Mcp\ToolInputs;
use App\Shared\Mcp\ToolResults;
use Mcp\Schema\ToolAnnotations;
use Mcp\Server\Capability\Attribute\McpTool;
use Prospecter\Core\PersonalData\Application\RenamePerson\RenamePerson;
use Symfony\Component\Clock\ClockInterface;

/**
 * `PATCH /api/contacts/{id}/name`, for an AI (ADR 0163).
 */
#[Adapts('api_contacts_rename')]
final readonly class RenameContactTool
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
     * @param string  $id        the contact's id
     * @param ?string $firstName their first name
     * @param ?string $lastName  their last name
     * @param ?int    $revision  the contact's `revision` as you read it. If it changed since, the change is refused (changedMeanwhile) and you read the contact again
     *
     * @return array<string, mixed>
     */
    #[McpTool(name: 'set-contact-name', description: 'Change a contact\'s first and last names. Nothing else changes. It needs the right to manage personal data. Returns the contact.', annotations: new ToolAnnotations(readOnlyHint: false, destructiveHint: false, idempotentHint: true, openWorldHint: false))]
    public function __invoke(string $id, ?string $firstName = null, ?string $lastName = null, ?int $revision = null): array
    {
        $this->access->denyUnlessGranted('ROLE_PROSPECTOR');
        $input = $this->inputs->valid(new PersonNameInput($firstName, $lastName));
        $memberId = $this->access->member()->memberId();
        $this->commandBus->dispatch(new RenamePerson(readerId: $memberId, id: $id, firstName: $input->firstName, lastName: $input->lastName, executedAt: $this->clock->now()), $this->revisions->readAt($id, $revision));

        return $this->results->of($this->contactReader->read($memberId, $id));
    }
}
