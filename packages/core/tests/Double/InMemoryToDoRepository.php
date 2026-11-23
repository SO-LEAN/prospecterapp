<?php

declare(strict_types=1);

namespace Tests\Core\Double;

use Prospecter\Core\Prospecting\Domain\Exception\UnknownToDo;
use Prospecter\Core\Prospecting\Domain\Model\ContactId;
use Prospecter\Core\Prospecting\Domain\Model\OrganizationId;
use Prospecter\Core\Prospecting\Domain\Model\ToDo;
use Prospecter\Core\Prospecting\Domain\Model\ToDoId;
use Prospecter\Core\Prospecting\Domain\Repository\ToDoRepository;
use Prospecter\Core\SharedKernel\Domain\AccountId;

final readonly class InMemoryToDoRepository implements ToDoRepository
{
    public function __construct(private InMemoryDatabase $database)
    {
    }

    public function get(ToDoId $id): ToDo
    {
        return $this->database->toDo((string) $id) ?? throw UnknownToDo::identifiedBy($id);
    }

    public function save(ToDo $toDo): void
    {
        $this->database->storeToDo($toDo);
    }

    public function remove(ToDo $toDo): void
    {
        $this->database->forgetToDo((string) $toDo->id());
    }

    public function keptFor(OrganizationId $organization): array
    {
        return array_values(array_filter($this->database->toDos(), static fn (ToDo $toDo): bool => array_any(StateOf::the($toDo)->listOf('keptFor', OrganizationId::class), $organization->equals(...))));
    }

    public function forContact(ContactId $contact, AccountId $accountId): array
    {
        return array_values(array_filter($this->database->toDos(), static fn (ToDo $toDo): bool => true === StateOf::the($toDo)->objectOrNull('contact', ContactId::class)?->equals($contact) && $toDo->accountId()->equals($accountId)));
    }
}
