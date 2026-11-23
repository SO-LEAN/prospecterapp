<?php

declare(strict_types=1);

namespace App\Prospecting\Mcp;

use App\Prospecting\OrganizationResolver;
use App\Prospecting\OrganizationVoter;
use App\Security\ToolAccess;
use App\Security\User;
use App\Shared\CommandBus;
use App\Shared\Mcp\Adapts;
use Mcp\Schema\ToolAnnotations;
use Mcp\Server\Capability\Attribute\McpTool;
use Prospecter\Core\Prospecting\Application\OrganizationSheet;
use Prospecter\Core\Prospecting\Application\StopProspecting\StopProspecting;
use Symfony\Component\Clock\ClockInterface;

/**
 * `DELETE /api/organizations/{id}`, for an AI (ADR 0163). The AI asks its member
 * before it calls the tool.
 */
#[Adapts('api_organizations_stop_prospecting')]
final readonly class StopProspectingTool
{
    public function __construct(
        private ToolAccess $access,
        private OrganizationResolver $organizations,
        private CommandBus $commandBus,
        private ClockInterface $clock,
    ) {
    }

    /**
     * @param string $id the id of the organization
     */
    #[McpTool(name: 'stop-prospecting', description: 'Stop prospecting an organization. Its subsidiaries lose their holding. Its contacts that no other organization names are erased, which needs the right to manage personal data. Returns nothing.', annotations: new ToolAnnotations(readOnlyHint: false, destructiveHint: true, idempotentHint: false, openWorldHint: false))]
    public function __invoke(string $id): void
    {
        $organization = $this->organizations->read($id);
        $this->access->denyUnlessGranted('ROLE_PROSPECTOR');
        $this->access->denyUnlessGranted(OrganizationVoter::ACCESS, $organization);
        $this->ensureMayEraseItsContacts($organization);
        $this->commandBus->dispatch(new StopProspecting($this->access->member()->memberId(), $organization->id, $this->clock->now()));
    }

    /**
     * The contacts that no other organization names are erased with the organization. They are personal data.
     */
    private function ensureMayEraseItsContacts(OrganizationSheet $organization): void
    {
        if ([] !== $organization->contactIds) {
            $this->access->denyUnlessGranted(User::PERSONAL_DATA);
        }
    }
}
