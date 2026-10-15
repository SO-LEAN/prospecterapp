<?php

declare(strict_types=1);

namespace Prospecter\Core\Application\Organization\RemoveLogo;

use Prospecter\Core\Application\CommandHandler;
use Prospecter\Core\Application\Port\Storage;
use Prospecter\Core\Application\Service\FileStore;
use Prospecter\Core\Domain\Organization\OrganizationRepository;
use Prospecter\Core\Domain\User\UserRepository;

final readonly class RemoveLogoHandler implements CommandHandler
{
    private FileStore $files;

    public function __construct(private OrganizationRepository $organizations, private UserRepository $users, Storage $storage)
    {
        $this->files = new FileStore($storage);
    }

    public function __invoke(RemoveLogo $command): void
    {
        $prospector = $this->users->get($command->prospectorId);
        $organization = $this->organizations->get($command->id);
        $removedLogo = $organization->removeLogo(by: $prospector);
        $this->organizations->save($organization);
        $this->files->discard($removedLogo);
    }
}
