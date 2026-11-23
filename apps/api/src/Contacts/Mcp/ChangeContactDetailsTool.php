<?php

declare(strict_types=1);

namespace App\Contacts\Mcp;

use App\Contacts\ContactReader;
use App\PersonalData\Input\PersonContactDetailsInput;
use App\PersonalData\Persistence\PersonRevisions;
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
        private PersonRevisions $revisions,
    ) {
    }

    /**
     * @param string       $id          the contact's id
     * @param ?string      $email       their e-mail
     * @param ?string      $phoneNumber their phone number, in international format (+33 3 88 11 22 33) or in the French national format (03 88 11 22 33)
     * @param ?string      $language    the country of the language you speak with them (FR, GB…)
     * @param list<string> $links       the addresses of their pages on outside tools (their LinkedIn or Malt profile, or any other site), five at most
     * @param ?int         $revision    the contact's `revision` as you read it. If it changed since, the change is refused (changedMeanwhile) and you read the contact again
     *
     * @return array<string, mixed>
     */
    #[McpTool(name: 'update-contact-details', description: 'Change a contact\'s e-mail, phone number, language and links, their LinkedIn profile among them. Whatever you leave out is removed. It needs the right to manage personal data. Returns the contact.', annotations: new ToolAnnotations(readOnlyHint: false, destructiveHint: false, idempotentHint: true, openWorldHint: false))]
    public function __invoke(string $id, ?string $email = null, ?string $phoneNumber = null, ?string $language = null, array $links = [], ?int $revision = null): array
    {
        $this->access->denyUnlessGranted('ROLE_PROSPECTOR');
        $input = $this->inputs->valid(new PersonContactDetailsInput($email, $phoneNumber, $language, $links));
        $memberId = $this->access->member()->memberId();
        $this->commandBus->dispatch(new ChangePersonContactDetails(readerId: $memberId, id: $id, email: $input->email, phoneNumber: $input->phoneNumber, language: $input->language, links: $input->links, executedAt: $this->clock->now()), $this->revisions->readAt($id, $revision));

        return $this->results->of($this->contactReader->read($memberId, $id));
    }
}
