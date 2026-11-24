<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\Organization\ReviseLegalIdentity;

use Prospecter\Core\Prospecting\Domain\Model\OrganizationId;
use Prospecter\Core\Prospecting\Domain\Repository\OrganizationRepository;
use Prospecter\Core\Prospecting\Domain\Service\DuplicateSuspicion;
use Prospecter\Core\Prospecting\Domain\Service\SiretUniqueness;
use Prospecter\Core\SharedKernel\Application\Handler\CommandHandler;
use Prospecter\Core\SharedKernel\Application\Handler\PublishesEvents;
use Prospecter\Core\SharedKernel\Application\Port\Events;
use Prospecter\Core\SharedKernel\Domain\Company\LegalIdentity;
use Prospecter\Core\SharedKernel\Domain\Company\Siret;
use Prospecter\Core\SharedKernel\Domain\Identity\MemberId;

final readonly class ReviseLegalIdentityHandler implements CommandHandler
{
    use PublishesEvents;

    public function __construct(
        private OrganizationRepository $organizations,
        private Events $events,
        private SiretUniqueness $sirets,
        private DuplicateSuspicion $duplicateSuspicion,
    ) {
    }

    public function __invoke(ReviseLegalIdentity $command): void
    {
        $author = new MemberId($command->prospectorId);
        $organization = $this->organizations->get(new OrganizationId($command->id));
        $organization->reviseLegalIdentity(new LegalIdentity($command->corporateName, $command->form, $command->type, Siret::orNone($command->siret)), by: $author, at: $command->executedAt);
        $this->sirets->ensureAvailableFor($organization);
        $organization->reconsiderDuplicates($this->duplicateSuspicion->resemblingTo($organization), by: $author, at: $command->executedAt);
        $this->organizations->save($organization);
        $this->publishWhatHappenedTo($organization);
    }
}
