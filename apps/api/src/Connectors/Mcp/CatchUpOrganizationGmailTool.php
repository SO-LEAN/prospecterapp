<?php

namespace App\Connectors\Mcp;

use App\Prospecting\OrganizationResolver;
use App\Prospecting\OrganizationVoter;
use App\Security\ToolAccess;
use App\Security\User;
use App\Shared\CommandBus;
use App\Shared\Mcp\Adapts;
use Mcp\Schema\ToolAnnotations;
use Mcp\Server\Capability\Attribute\McpTool;
use Prospecter\Core\Connectors\Application\CatchUpMailHistory\CatchUpMailHistory;
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
     * @param string $id the id of the organization, which has an e-mail of its own
     */
    #[McpTool(name: 'catch-up-organization-emails', description: 'Catch up the e-mails the mailbox of Gmail of the member exchanged with the address of an organization itself over the 12 months before, the 200 newest, while the import of their e-mails is active; needs the right to manage the personal data. Answers nothing: the e-mails arrive in the timeline of the organization a moment later.', annotations: new ToolAnnotations(readOnlyHint: false, destructiveHint: false, idempotentHint: true, openWorldHint: true))]
    public function __invoke(string $id): void
    {
        $organization = $this->organizations->read($id);
        $this->access->denyUnlessGranted('ROLE_USER');
        $this->access->denyUnlessGranted(OrganizationVoter::ACCESS, $organization);
        $this->access->denyUnlessGranted(User::PERSONAL_DATA);
        $this->commandBus->dispatch(CatchUpMailHistory::ofOrganization($this->access->member()->memberId(), $organization->id, $this->clock->now()));
    }
}
