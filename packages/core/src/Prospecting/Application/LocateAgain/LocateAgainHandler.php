<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\LocateAgain;

use Prospecter\Core\Prospecting\Domain\Model\OrganizationId;
use Prospecter\Core\Prospecting\Domain\Repository\OrganizationRepository;
use Prospecter\Core\SharedKernel\Application\Handler\CommandHandler;
use Prospecter\Core\SharedKernel\Application\Handler\PublishesEvents;
use Prospecter\Core\SharedKernel\Application\Port\Events;
use Prospecter\Core\SharedKernel\Application\Service\GeoLocalizer;

final readonly class LocateAgainHandler implements CommandHandler
{
    use PublishesEvents;

    public function __construct(
        private OrganizationRepository $organizations,
        private Events $events,
        private GeoLocalizer $geoLocalizer,
    ) {
    }

    public function __invoke(LocateAgain $command): void
    {
        $organization = $this->organizations->get(new OrganizationId($command->id));
        $address = $organization->addressToLocateAgain();

        if (null === $address) {
            return;
        }

        $organization->locateAgain($this->geoLocalizer->locate($address), at: $command->executedAt);
        $this->organizations->save($organization);
        $this->publishWhatHappenedTo($organization);
    }
}
