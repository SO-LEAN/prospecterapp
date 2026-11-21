<?php

namespace App\Connectors\Mcp;

use App\Security\ToolAccess;
use App\Security\User;
use App\Shared\CommandBus;
use App\Shared\Mcp\Adapts;
use Mcp\Schema\ToolAnnotations;
use Mcp\Server\Capability\Attribute\McpTool;
use Prospecter\Core\Connectors\Application\CatchUpMailHistory\CatchUpMailHistory;
use Symfony\Component\Clock\ClockInterface;

/**
 * `POST /api/contacts/{id}/gmail-catch-ups`, for an AI (ADR 0163).
 */
#[Adapts('api_contacts_catch_up_gmail')]
final readonly class CatchUpContactGmailTool
{
    public function __construct(private ToolAccess $access, private CommandBus $commandBus, private ClockInterface $clock)
    {
    }

    /**
     * @param string $id the id of the contact, who has an e-mail
     */
    #[McpTool(name: 'rattraper-gmail-contact', description: 'Catch up the e-mails the mailbox of Gmail of the member exchanged with a contact over the 12 months before, the 200 newest, while the import of their e-mails is active; needs the right to manage the personal data. Answers nothing: the e-mails arrive in the timeline of the contact a moment later.', annotations: new ToolAnnotations(readOnlyHint: false, destructiveHint: false, idempotentHint: true, openWorldHint: true))]
    public function __invoke(string $id): void
    {
        $this->access->denyUnlessGranted('ROLE_USER');
        $this->access->denyUnlessGranted(User::PERSONAL_DATA);
        $this->commandBus->dispatch(CatchUpMailHistory::ofContact($this->access->member()->memberId(), $id, $this->clock->now()));
    }
}
