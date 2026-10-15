<?php

declare(strict_types=1);

namespace Prospecter\Core\Application\Account\ChangeMyOrganizationLogo;

use Prospecter\Core\Application\CommandHandler;
use Prospecter\Core\Application\Port\Storage;
use Prospecter\Core\Application\Service\FileStore;
use Prospecter\Core\Domain\Organization\OrganizationRepository;
use Prospecter\Core\Domain\User\UserRepository;

final readonly class ChangeMyOrganizationLogoHandler implements CommandHandler
{
    private FileStore $files;

    public function __construct(private OrganizationRepository $organizations, private UserRepository $users, Storage $storage)
    {
        $this->files = new FileStore($storage);
    }

    public function __invoke(ChangeMyOrganizationLogo $command): void
    {
        $me = $this->users->get($command->userId);
        $myOrganization = $this->organizations->get($me->organizationId());
        $myOrganization->changeLogo($this->files->store($command->logo), by: $me);
        $this->organizations->save($myOrganization);
    }
}
