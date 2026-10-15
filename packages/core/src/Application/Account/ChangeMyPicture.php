<?php

declare(strict_types=1);

namespace Prospecter\Core\Application\Account;

use Prospecter\Core\Application\Port\Storage;
use Prospecter\Core\Application\Service\FileStore;
use Prospecter\Core\Application\UseCaseConsumer;
use Prospecter\Core\Domain\Organization\OrganizationRepository;
use Prospecter\Core\Domain\User\UserRepository;

final class ChangeMyPicture extends AccountUseCase
{
    private readonly FileStore $files;

    public function __construct(UserRepository $users, OrganizationRepository $organizations, Storage $storage)
    {
        parent::__construct($users, $organizations);
        $this->files = new FileStore($storage);
    }

    public function execute(ChangeMyPictureRequest $request, AccountPresenter $presenter, UseCaseConsumer $consumer): mixed
    {
        $me = $this->me($consumer);
        $me->changePicture($this->files->store($request->picture));
        $this->users->save($me);

        return $this->present($consumer, $presenter);
    }
}
