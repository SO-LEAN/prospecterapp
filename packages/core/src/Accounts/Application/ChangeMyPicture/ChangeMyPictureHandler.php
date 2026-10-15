<?php

declare(strict_types=1);

namespace Prospecter\Core\Accounts\Application\ChangeMyPicture;

use Prospecter\Core\Accounts\Domain\MemberRepository;
use Prospecter\Core\SharedKernel\Application\CommandHandler;
use Prospecter\Core\SharedKernel\Application\FileStore;
use Prospecter\Core\SharedKernel\Application\Port\Storage;

final readonly class ChangeMyPictureHandler implements CommandHandler
{
    private FileStore $files;

    public function __construct(private MemberRepository $members, Storage $storage)
    {
        $this->files = new FileStore($storage);
    }

    public function __invoke(ChangeMyPicture $command): void
    {
        $me = $this->members->get($command->userId);
        $me->changePicture($this->files->store($command->picture));
        $this->members->save($me);
    }
}
