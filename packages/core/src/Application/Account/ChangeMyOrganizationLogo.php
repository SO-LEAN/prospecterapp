<?php

declare(strict_types=1);

namespace Prospecter\Core\Application\Account;

use Prospecter\Core\Application\Port\Storage;
use Prospecter\Core\Application\Service\FileStore;
use Prospecter\Core\Application\UseCaseConsumer;
use Prospecter\Core\Domain\Organization\OrganizationRepository;
use Prospecter\Core\Domain\User\UserRepository;

final class ChangeMyOrganizationLogo extends AccountUseCase
{
    private readonly FileStore $files;

    public function __construct(UserRepository $users, OrganizationRepository $organizations, Storage $storage)
    {
        parent::__construct($users, $organizations);
        $this->files = new FileStore($storage);
    }

    public function execute(ChangeMyOrganizationLogoRequest $request, AccountPresenter $presenter, UseCaseConsumer $consumer): mixed
    {
        $myOrganization = $this->myOrganization($consumer);
        $myOrganization->changeLogo($this->files->store($request->logo));
        $this->organizations->save($myOrganization);

        return $this->present($consumer, $presenter);
    }
}
