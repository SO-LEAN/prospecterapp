<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\ChangeLogo;

use Prospecter\Core\Prospecting\Domain\OrganizationRepository;
use Prospecter\Core\Prospecting\Domain\ProspectorService;
use Prospecter\Core\SharedKernel\Application\CommandHandler;
use Prospecter\Core\SharedKernel\Application\FileStore;
use Prospecter\Core\SharedKernel\Application\Port\Events;
use Prospecter\Core\SharedKernel\Application\PublishesEvents;
use Prospecter\Core\SharedKernel\Domain\MemberId;
use Prospecter\Core\SharedKernel\Domain\OrganizationId;

final readonly class ChangeLogoHandler implements CommandHandler
{
    use PublishesEvents;

    public function __construct(
        private OrganizationRepository $organizations,
        private ProspectorService $prospectorService,
        private Events $events,
        private FileStore $files,
    ) {
    }

    public function __invoke(ChangeLogo $command): void
    {
        $prospector = $this->prospectorService->prospectorFrom(new MemberId($command->prospectorId));
        $organization = $this->organizations->get(new OrganizationId($command->id));
        $organization->changeLogo($this->files->store($command->logo), by: $prospector, at: $command->executedAt);
        $this->organizations->save($organization);
        $this->publishWhatHappenedTo($organization);
    }
}
