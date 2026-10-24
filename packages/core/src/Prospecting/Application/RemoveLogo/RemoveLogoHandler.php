<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\RemoveLogo;

use Prospecter\Core\Prospecting\Domain\OrganizationRepository;
use Prospecter\Core\SharedKernel\Application\CommandHandler;
use Prospecter\Core\SharedKernel\Application\FileStore;
use Prospecter\Core\SharedKernel\Application\Port\Events;
use Prospecter\Core\SharedKernel\Application\PublishesEvents;
use Prospecter\Core\SharedKernel\Domain\MemberId;
use Prospecter\Core\SharedKernel\Domain\OrganizationId;

final readonly class RemoveLogoHandler implements CommandHandler
{
    use PublishesEvents;

    public function __construct(
        private OrganizationRepository $organizations,
        private Events $events,
        private FileStore $files,
    ) {
    }

    public function __invoke(RemoveLogo $command): void
    {
        $author = new MemberId($command->prospectorId);
        $organization = $this->organizations->get(new OrganizationId($command->id));
        $removedLogo = $organization->removeLogo(by: $author, at: $command->executedAt);
        $this->organizations->save($organization);
        $this->publishWhatHappenedTo($organization);
        $this->files->discard($removedLogo);
    }
}
