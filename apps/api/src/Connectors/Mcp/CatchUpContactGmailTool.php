<?php

declare(strict_types=1);

namespace App\Connectors\Mcp;

use App\Security\ToolAccess;
use App\Security\User;
use App\Shared\CommandBus;
use App\Shared\Mcp\Adapts;
use Mcp\Schema\ToolAnnotations;
use Mcp\Server\Capability\Attribute\McpTool;
use Prospecter\Core\Connectors\Application\MailImport\CatchUpMailHistory\CatchUpMailHistory;
use Symfony\Component\Clock\ClockInterface;

/**
 * `POST /api/contacts/{id}/gmail-reimports`, for an AI (ADR 0163).
 */
#[Adapts('api_contacts_reimport_gmail')]
final readonly class CatchUpContactGmailTool
{
    public function __construct(private ToolAccess $access, private CommandBus $commandBus, private ClockInterface $clock)
    {
    }

    /**
     * @param string $id the contact's id. The contact has an e-mail
     */
    #[McpTool(name: 'catch-up-contact-emails', description: 'Reimport the e-mails that the member\'s Gmail mailbox exchanged with a contact over the past 12 months, the 200 newest. The member\'s e-mail import must be active, and the member must be allowed to manage personal data. Returns nothing: the e-mails reach the contact\'s timeline a moment later.', annotations: new ToolAnnotations(readOnlyHint: false, destructiveHint: false, idempotentHint: true, openWorldHint: true))]
    public function __invoke(string $id): void
    {
        $this->access->denyUnlessGranted('ROLE_USER');
        $this->access->denyUnlessGranted(User::PERSONAL_DATA);
        $this->commandBus->dispatch(CatchUpMailHistory::ofContact($this->access->member()->memberId(), $id, $this->clock->now()));
    }
}
