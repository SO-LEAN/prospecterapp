<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\ReviseLegalIdentity;

use Prospecter\Core\Prospecting\Domain\OrganizationRepository;
use Prospecter\Core\Prospecting\Domain\Prospectors;
use Prospecter\Core\SharedKernel\Application\CommandHandler;
use Prospecter\Core\SharedKernel\Domain\LegalIdentity;
use Prospecter\Core\SharedKernel\Domain\MemberId;
use Prospecter\Core\SharedKernel\Domain\OrganizationId;

final readonly class ReviseLegalIdentityHandler implements CommandHandler
{
    public function __construct(private OrganizationRepository $organizations, private Prospectors $prospectors)
    {
    }

    public function __invoke(ReviseLegalIdentity $command): void
    {
        $prospector = $this->prospectors->get(new MemberId($command->prospectorId));
        $organization = $this->organizations->get(new OrganizationId($command->id));
        $organization->reviseLegalIdentity(new LegalIdentity($command->corporateName, $command->form, $command->type), by: $prospector, at: $command->executedAt);
        $this->organizations->save($organization);
    }
}
