<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\ToDo\KeepToDosOfAttachedContact;

use Prospecter\Core\Prospecting\Domain\Model\ContactId;
use Prospecter\Core\Prospecting\Domain\Model\OrganizationId;
use Prospecter\Core\Prospecting\Domain\Repository\ToDoRepository;
use Prospecter\Core\SharedKernel\Application\Handler\CommandHandler;
use Prospecter\Core\SharedKernel\Application\Handler\PublishesEvents;
use Prospecter\Core\SharedKernel\Application\Port\Events;
use Prospecter\Core\SharedKernel\Domain\Identity\AccountId;
use Prospecter\Core\SharedKernel\Domain\Identity\MemberId;

/**
 * The holder kept some to-dos while the contact waited to be attached. These to-dos follow the contact into the
 * organization they join (ADR 0146). The to-dos already kept for organizations stay as they are (ADR 0113).
 */
final readonly class KeepToDosOfAttachedContactHandler implements CommandHandler
{
    use PublishesEvents;

    public function __construct(private ToDoRepository $toDos, private Events $events)
    {
    }

    public function __invoke(KeepToDosOfAttachedContact $command): void
    {
        $organization = new OrganizationId($command->organizationId);
        $member = new MemberId($command->memberId);

        foreach ($this->toDos->forContact(new ContactId($command->contactId), new AccountId($command->accountId)) as $toDo) {
            if (!$toDo->isKeptForAnOrganization()) {
                $toDo->keepFor($organization, by: $member, at: $command->executedAt);
                $this->toDos->save($toDo);
                $this->publishWhatHappenedTo($toDo);
            }
        }
    }
}
