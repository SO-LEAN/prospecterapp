<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\ChangeContactDetails;

use Prospecter\Core\Prospecting\Domain\OrganizationRepository;
use Prospecter\Core\Prospecting\Domain\Prospectors;
use Prospecter\Core\SharedKernel\Application\CommandHandler;

final readonly class ChangeContactDetailsHandler implements CommandHandler
{
    public function __construct(private OrganizationRepository $organizations, private Prospectors $prospectors)
    {
    }

    public function __invoke(ChangeContactDetails $command): void
    {
        $prospector = $this->prospectors->get($command->prospectorId);
        $organization = $this->organizations->get($command->id);
        $organization->changeContactDetails($command->contactDetails, by: $prospector);
        $this->organizations->save($organization);
    }
}
