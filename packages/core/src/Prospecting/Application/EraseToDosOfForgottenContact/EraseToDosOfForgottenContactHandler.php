<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\EraseToDosOfForgottenContact;

use Prospecter\Core\Prospecting\Domain\Model\ContactId;
use Prospecter\Core\Prospecting\Domain\Repository\ToDoRepository;
use Prospecter\Core\SharedKernel\Application\Handler\CommandHandler;
use Prospecter\Core\SharedKernel\Application\Handler\PublishesEvents;
use Prospecter\Core\SharedKernel\Application\Port\Events;
use Prospecter\Core\SharedKernel\Domain\Identity\AccountId;
use Prospecter\Core\SharedKernel\Domain\Identity\MemberId;

/**
 * Erases an erased contact's to-dos, by the member who erased the contact. Unlike the exchanges, they are no
 * commercial history to keep. Their text may also hold the contact's personal data (ADR 0113).
 */
final readonly class EraseToDosOfForgottenContactHandler implements CommandHandler
{
    use PublishesEvents;

    public function __construct(
        private ToDoRepository $toDos,
        private Events $events,
    ) {
    }

    public function __invoke(EraseToDosOfForgottenContact $command): void
    {
        $author = new MemberId($command->memberId);

        foreach ($this->toDos->forContact(new ContactId($command->contactId), new AccountId($command->accountId)) as $toDo) {
            $toDo->erase(by: $author, at: $command->executedAt);
            $this->toDos->remove($toDo);
            $this->publishWhatHappenedTo($toDo);
        }
    }
}
