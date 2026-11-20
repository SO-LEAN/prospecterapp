<?php

namespace App\Contacts\Mcp;

use App\Contacts\ContactReader;
use App\PersonalData\Input\CivilityInput;
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
 * `PATCH /api/contacts/{id}/civility`, for an AI (ADR 0163).
 */
#[Adapts('api_contacts_change_civility')]
final readonly class ChangeCivilityTool
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
     * @param string $id       the id of the contact
     * @param string $civility how one addresses the contact: ms, mr, or x when unknown
     *
     * @return array<string, mixed>
     */
    #[McpTool(name: 'changer-civilite-contact', description: 'Note how one addresses a contact, Ms or Mr, or that it is unknown (x); needs the right to manage the personal data. Answers the contact.', annotations: new ToolAnnotations(readOnlyHint: false, destructiveHint: false, idempotentHint: true, openWorldHint: false))]
    public function __invoke(string $id, string $civility = 'x'): array
    {
        $this->access->denyUnlessGranted('ROLE_PROSPECTOR');
        $input = $this->inputs->valid(new CivilityInput($civility));
        $memberId = $this->access->member()->memberId();
        $this->commandBus->dispatch(new ChangeCivility($memberId, $id, $input->civility, $this->clock->now()));

        return $this->results->of($this->contactReader->read($memberId, $id));
    }
}
