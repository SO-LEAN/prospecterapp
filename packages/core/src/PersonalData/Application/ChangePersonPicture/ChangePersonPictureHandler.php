<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Application\ChangePersonPicture;

use Prospecter\Core\PersonalData\Domain\PersonRepository;
use Prospecter\Core\PersonalData\Domain\Readers;
use Prospecter\Core\SharedKernel\Application\CommandHandler;
use Prospecter\Core\SharedKernel\Application\FileStore;
use Prospecter\Core\SharedKernel\Application\Port\Storage;

final readonly class ChangePersonPictureHandler implements CommandHandler
{
    private FileStore $files;

    public function __construct(private PersonRepository $persons, private Readers $readers, Storage $storage)
    {
        $this->files = new FileStore($storage);
    }

    public function __invoke(ChangePersonPicture $command): void
    {
        $person = $this->persons->get($command->id);
        $person->changePicture($this->files->store($command->picture), by: $this->readers->get($command->readerId));
        $this->persons->save($person);
    }
}
