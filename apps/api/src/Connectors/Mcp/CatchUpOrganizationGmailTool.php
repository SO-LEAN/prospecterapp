<?php

declare(strict_types=1);

namespace App\Connectors\Mcp;

use App\Prospecting\OrganizationResolver;
use App\Prospecting\OrganizationVoter;
use App\Security\ToolAccess;
use App\Security\User;
use App\Shared\CommandBus;
use App\Shared\Mcp\Adapts;
use Mcp\Schema\ToolAnnotations;
use Mcp\Server\Capability\Attribute\McpTool;
use Prospecter\Core\Connectors\Application\MailImport\CatchUpMailHistory\CatchUpMailHistory;
use Symfony\Component\Clock\ClockInterface;

/**
 * `POST /api/organizations/{id}/gmail-reimports`, for an AI (ADR 0163).
 */
#[Adapts('api_organizations_reimport_gmail')]
final readonly class CatchUpOrganizationGmailTool
{
    public function __construct(
        private ToolAccess $access,
        private OrganizationResolver $organizations,
        private CommandBus $commandBus,
        private ClockInterface $clock,
    ) {
    }

    /**
     * @param string $id the organization's id. The organization has an e-mail of its own
     */
    #[McpTool(name: 'catch-up-organization-emails', description: 'Reimport the e-mails that the member\'s Gmail mailbox exchanged with an organization\'s own address over the past 12 months, the 200 newest. The member\'s e-mail import must be active, and the member must be allowed to manage personal data. Returns nothing: the e-mails reach the organization\'s timeline a moment later.', annotations: new ToolAnnotations(readOnlyHint: false, destructiveHint: false, idempotentHint: true, openWorldHint: true))]
    public function __invoke(string $id): void
    {
        $organization = $this->organizations->read($id);
        $this->access->denyUnlessGranted('ROLE_USER');
        $this->access->denyUnlessGranted(OrganizationVoter::ACCESS, $organization);
        $this->access->denyUnlessGranted(User::PERSONAL_DATA);
        $this->commandBus->dispatch(CatchUpMailHistory::ofOrganization($this->access->member()->memberId(), $organization->id, $this->clock->now()));
    }
}
