<?php

declare(strict_types=1);

namespace Prospecter\Core\Application\Account\ChangeMyPicture;

use Prospecter\Core\Application\Account\AccountPresenter;
use Prospecter\Core\Application\Account\AccountResponses;
use Prospecter\Core\Application\Port\Storage;
use Prospecter\Core\Application\Service\FileStore;
use Prospecter\Core\Application\UseCase;
use Prospecter\Core\Domain\Organization\OrganizationRepository;
use Prospecter\Core\Domain\User\UserRepository;

final class ChangeMyPicture extends UseCase
{
    private readonly AccountResponses $responses;
    private readonly FileStore $files;

    public function __construct(private readonly UserRepository $users, OrganizationRepository $organizations, Storage $storage)
    {
        $this->responses = new AccountResponses($organizations);
        $this->files = new FileStore($storage);
    }

    public function execute(ChangeMyPictureRequest $request, AccountPresenter $presenter): mixed
    {
        $me = $this->users->get($request->userId);
        $me->changePicture($this->files->store($request->picture));
        $this->users->save($me);

        return $presenter->present($this->responses->of($me));
    }
}
