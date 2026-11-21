<?php

namespace App\Contacts\Mcp;

use App\Contacts\ContactReader;
use App\PersonalData\Input\CollectContactInput;
use App\PersonalData\Input\PersonContactDetailsInput;
use App\PersonalData\Input\PersonNameInput;
use App\Security\ToolAccess;
use App\Shared\CommandBus;
use App\Shared\Identities;
use App\Shared\Mcp\Adapts;
use App\Shared\Mcp\ToolInputs;
use App\Shared\Mcp\ToolResults;
use Mcp\Schema\ToolAnnotations;
use Mcp\Server\Capability\Attribute\McpTool;
use Symfony\Component\Clock\ClockInterface;

/**
 * `POST /api/contacts`, for an AI (ADR 0163).
 */
#[Adapts('api_contacts_collect')]
final readonly class CollectContactTool
{
    public function __construct(
        private ToolAccess $access,
        private ToolInputs $inputs,
        private Identities $identities,
        private CommandBus $commandBus,
        private ClockInterface $clock,
        private ContactReader $contactReader,
        private ToolResults $results,
    ) {
    }

    /**
     * @param list<string> $organizationIds the ids of the organizations they work for, at least one
     * @param ?string      $firstName       their first name
     * @param ?string      $lastName        their last name
     * @param string       $civility        how one addresses them: ms, mr, or x when unknown
     * @param ?string      $jobTitle        what they do in their organization
     * @param ?string      $email           their e-mail
     * @param ?string      $phoneNumber     their phone number, international (+33 3 88 11 22 33) or national in France (03 88 11 22 33)
     * @param ?string      $language        the country of the language spoken with them (FR, GB…)
     * @param list<string> $links           the addresses of their pages on the tools outside (their profile on LinkedIn or Malt, any other site), five at most
     *
     * @return array<string, mixed>
     */
    #[McpTool(name: 'collecter-contact', description: 'Collect a person the account prospects, as a contact of the organizations they work for (at least one); needs the right to manage the personal data. Answers the new contact.', annotations: new ToolAnnotations(readOnlyHint: false, destructiveHint: false, idempotentHint: false, openWorldHint: false))]
    public function __invoke(
        array $organizationIds,
        ?string $firstName = null,
        ?string $lastName = null,
        string $civility = 'x',
        ?string $jobTitle = null,
        ?string $email = null,
        ?string $phoneNumber = null,
        ?string $language = null,
        array $links = [],
    ): array {
        $this->access->denyUnlessGranted('ROLE_PROSPECTOR');
        $input = $this->inputs->valid(new CollectContactInput(new PersonNameInput($firstName, $lastName), $civility, $jobTitle, new PersonContactDetailsInput($email, $phoneNumber, $language, $links), $organizationIds));
        $memberId = $this->access->member()->memberId();
        $id = $this->identities->next();
        $this->commandBus->dispatch($input->commandOfAnAI($memberId, $id, $this->clock->now()));

        return $this->results->of($this->contactReader->read($memberId, $id));
    }
}
