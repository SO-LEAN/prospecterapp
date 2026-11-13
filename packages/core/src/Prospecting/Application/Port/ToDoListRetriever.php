<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\Port;

use Prospecter\Core\Prospecting\Application\ToDoInList;
use Prospecter\Core\Prospecting\Domain\Exception\UnknownToDo;
use Prospecter\Core\Prospecting\Domain\Model\ToDoId;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\MemberId;
use Prospecter\Core\SharedKernel\Domain\OrganizationId;
use Prospecter\Core\SharedKernel\Domain\PersonId;

/**
 * Reads the to-dos without restoring them (ADR 0088), each with what its state offers (`ToDoPossibilities`), in no
 * particular order: `ToDoList` orders them.
 */
interface ToDoListRetriever
{
    /**
     * @throws UnknownToDo
     */
    public function get(ToDoId $id): ToDoInList;

    /**
     * The to-dos of the account for the contact, done or not.
     *
     * @return list<ToDoInList>
     */
    public function forContact(PersonId $contact, AccountId $accountId): array;

    /**
     * The to-dos kept for the organization, for it or for its contacts, done or not.
     *
     * @return list<ToDoInList>
     */
    public function keptFor(OrganizationId $organization): array;

    /**
     * The to-dos the member holds that are still to do.
     *
     * @return list<ToDoInList>
     */
    public function stillToDoBy(MemberId $holder): array;
}
