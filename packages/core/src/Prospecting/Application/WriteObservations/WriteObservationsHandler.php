<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\WriteObservations;

use Prospecter\Core\Prospecting\Domain\OrganizationId;
use Prospecter\Core\Prospecting\Domain\OrganizationRepository;
use Prospecter\Core\Prospecting\Domain\Prospectors;
use Prospecter\Core\SharedKernel\Application\CommandHandler;
use Prospecter\Core\SharedKernel\Domain\MemberId;

final readonly class WriteObservationsHandler implements CommandHandler
{
    public function __construct(private OrganizationRepository $organizations, private Prospectors $prospectors)
    {
    }

    public function __invoke(WriteObservations $command): void
    {
        $prospector = $this->prospectors->get(new MemberId($command->prospectorId));
        $organization = $this->organizations->get(new OrganizationId($command->id));
        $organization->writeObservations($command->observations, by: $prospector, at: $command->executedAt);
        $this->organizations->save($organization);
    }
}
