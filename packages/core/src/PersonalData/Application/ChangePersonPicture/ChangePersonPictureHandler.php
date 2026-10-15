<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Application\ChangePersonPicture;

use Prospecter\Core\PersonalData\Domain\PersonRepository;
use Prospecter\Core\PersonalData\Domain\ReaderService;
use Prospecter\Core\SharedKernel\Application\CommandHandler;
use Prospecter\Core\SharedKernel\Application\FileStore;
use Prospecter\Core\SharedKernel\Application\Port\Events;
use Prospecter\Core\SharedKernel\Application\PublishesEvents;
use Prospecter\Core\SharedKernel\Domain\MemberId;
use Prospecter\Core\SharedKernel\Domain\PersonId;

final readonly class ChangePersonPictureHandler implements CommandHandler
{
    use PublishesEvents;

    public function __construct(
        private PersonRepository $persons,
        private ReaderService $readerService,
        private Events $events,
        private FileStore $files,
    ) {
    }

    public function __invoke(ChangePersonPicture $command): void
    {
        $person = $this->persons->get(new PersonId($command->id));
        $person->changePicture($this->files->store($command->picture), by: $this->readerService->readerFrom(new MemberId($command->readerId)), at: $command->executedAt);
        $this->persons->save($person);
        $this->publishWhatHappenedTo($person);
    }
}
