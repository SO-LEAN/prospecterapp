<?php

declare(strict_types=1);

namespace Prospecter\Core\Application\Account\ChangeMyPicture;

use Prospecter\Core\Application\CommandHandler;
use Prospecter\Core\Application\Port\Storage;
use Prospecter\Core\Application\Service\FileStore;
use Prospecter\Core\Domain\User\UserRepository;

final readonly class ChangeMyPictureHandler implements CommandHandler
{
    private FileStore $files;

    public function __construct(private UserRepository $users, Storage $storage)
    {
        $this->files = new FileStore($storage);
    }

    public function __invoke(ChangeMyPicture $command): void
    {
        $me = $this->users->get($command->userId);
        $me->changePicture($this->files->store($command->picture));
        $this->users->save($me);
    }
}
