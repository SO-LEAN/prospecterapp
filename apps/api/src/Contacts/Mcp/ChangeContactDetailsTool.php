<?php

namespace App\Contacts\Mcp;

use App\Contacts\ContactReader;
use App\PersonalData\Input\PersonContactDetailsInput;
use App\Security\ToolAccess;
use App\Shared\CommandBus;
use App\Shared\Mcp\Adapts;
use App\Shared\Mcp\ToolInputs;
use App\Shared\Mcp\ToolResults;
use Mcp\Schema\ToolAnnotations;
use Mcp\Server\Capability\Attribute\McpTool;
use Prospecter\Core\PersonalData\Application\ChangePersonContactDetails\ChangePersonContactDetails;
use Symfony\Component\Clock\ClockInterface;

/**
 * `PATCH /api/contacts/{id}/contact-details`, for an AI (ADR 0163).
 */
#[Adapts('api_contacts_change_contact_details')]
final readonly class ChangeContactDetailsTool
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
     * @param string       $id          the id of the contact
     * @param ?string      $email       their e-mail
     * @param ?string      $phoneNumber their phone number, international (+33 3 88 11 22 33) or national in France (03 88 11 22 33)
     * @param ?string      $language    the country of the language spoken with them (FR, GB…)
     * @param list<string> $links       the addresses of their pages on the tools outside (their profile on LinkedIn or Malt, any other site), five at most
     *
     * @return array<string, mixed>
     */
    #[McpTool(name: 'changer-coordonnees-contact', description: 'Change the e-mail, phone number, language and links (their profile on LinkedIn among them) of a contact; what is left out is removed. Needs the right to manage the personal data. Answers the contact.', annotations: new ToolAnnotations(readOnlyHint: false, destructiveHint: false, idempotentHint: true, openWorldHint: false))]
    public function __invoke(string $id, ?string $email = null, ?string $phoneNumber = null, ?string $language = null, array $links = []): array
    {
        $this->access->denyUnlessGranted('ROLE_PROSPECTOR');
        $input = $this->inputs->valid(new PersonContactDetailsInput($email, $phoneNumber, $language, $links));
        $memberId = $this->access->member()->memberId();
        $this->commandBus->dispatch(new ChangePersonContactDetails($memberId, $id, $input->email, $input->phoneNumber, $input->language, $input->links, $this->clock->now()));

        return $this->results->of($this->contactReader->read($memberId, $id));
    }
}
