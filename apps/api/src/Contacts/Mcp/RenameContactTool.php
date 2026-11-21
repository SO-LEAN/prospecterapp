<?php

namespace App\Contacts\Mcp;

use App\Contacts\ContactReader;
use App\PersonalData\Input\PersonNameInput;
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
    ) {
    }

    /**
     * @param string  $id        the id of the contact
     * @param ?string $firstName their first name
     * @param ?string $lastName  their last name
     *
     * @return array<string, mixed>
     */
    #[McpTool(name: 'set-contact-name', description: 'Change the first and last names of a contact, nothing else changes; needs the right to manage the personal data. Answers the contact.', annotations: new ToolAnnotations(readOnlyHint: false, destructiveHint: false, idempotentHint: true, openWorldHint: false))]
    public function __invoke(string $id, ?string $firstName = null, ?string $lastName = null): array
    {
        $this->access->denyUnlessGranted('ROLE_PROSPECTOR');
        $input = $this->inputs->valid(new PersonNameInput($firstName, $lastName));
        $memberId = $this->access->member()->memberId();
        $this->commandBus->dispatch(new RenamePerson($memberId, $id, $input->firstName, $input->lastName, $this->clock->now()));

        return $this->results->of($this->contactReader->read($memberId, $id));
    }
}
