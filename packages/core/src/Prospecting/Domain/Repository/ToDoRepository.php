<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Domain\Repository;

use Prospecter\Core\Prospecting\Domain\Exception\UnknownToDo;
use Prospecter\Core\Prospecting\Domain\Model\ToDo;
use Prospecter\Core\Prospecting\Domain\Model\ToDoId;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\OrganizationId;
use Prospecter\Core\SharedKernel\Domain\PersonId;

interface ToDoRepository
{
    /**
     * @throws UnknownToDo
     */
    public function get(ToDoId $id): ToDo;

    public function save(ToDo $toDo): void;

    public function remove(ToDo $toDo): void;

    /**
     * The to-dos kept for this organization.
     *
     * @return list<ToDo>
     */
    public function keptFor(OrganizationId $organization): array;

    /**
     * The to-dos of the account for the contact.
     *
     * @return list<ToDo>
     */
    public function forContact(PersonId $contact, AccountId $accountId): array;
}
