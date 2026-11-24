<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\Organization\Relocate;

use Prospecter\Core\Prospecting\Domain\Model\OrganizationId;
use Prospecter\Core\Prospecting\Domain\Repository\OrganizationRepository;
use Prospecter\Core\Prospecting\Domain\Service\DuplicateSuspicion;
use Prospecter\Core\SharedKernel\Application\Handler\CommandHandler;
use Prospecter\Core\SharedKernel\Application\Handler\PublishesEvents;
use Prospecter\Core\SharedKernel\Application\Port\Events;
use Prospecter\Core\SharedKernel\Application\Service\GeoLocalizer;
use Prospecter\Core\SharedKernel\Domain\Contact\Address;
use Prospecter\Core\SharedKernel\Domain\Identity\MemberId;

final readonly class RelocateHandler implements CommandHandler
{
    use PublishesEvents;

    public function __construct(
        private OrganizationRepository $organizations,
        private Events $events,
        private GeoLocalizer $geoLocalizer,
        private DuplicateSuspicion $duplicateSuspicion,
    ) {
    }

    public function __invoke(Relocate $command): void
    {
        $author = new MemberId($command->prospectorId);
        $organization = $this->organizations->get(new OrganizationId($command->id));
        $address = new Address($command->street, $command->postalCode, $command->city, $command->country);
        $organization->relocateTo($address, $this->geoLocalizer->locate($address), by: $author, at: $command->executedAt);
        $organization->reconsiderDuplicates($this->duplicateSuspicion->resemblingTo($organization), by: $author, at: $command->executedAt);
        $this->organizations->save($organization);
        $this->publishWhatHappenedTo($organization);
    }
}
