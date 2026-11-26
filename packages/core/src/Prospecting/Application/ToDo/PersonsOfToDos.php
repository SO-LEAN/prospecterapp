<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\ToDo;

use Prospecter\Core\Prospecting\Application\PersonsShown;
use Prospecter\Core\Prospecting\Application\Port\PersonDescriptionRetriever;
use Prospecter\Core\Prospecting\Application\ToDoInList;
use Prospecter\Core\Prospecting\Domain\Model\ContactId;
use Prospecter\Core\SharedKernel\Domain\Identity\MemberId;

/**
 * The persons that to-dos name: their contacts and their holders, as the reader reads them (ADR 0217).
 */
final readonly class PersonsOfToDos
{
    public function __construct(private PersonDescriptionRetriever $personDescriptionRetriever)
    {
    }

    /**
     * @param list<ToDoInList> $toDos
     */
    public function shownTo(string $readerId, array $toDos): PersonsShown
    {
        $contacts = array_values(array_filter(array_map(static fn (ToDoInList $toDo): ?string => $toDo->personId, $toDos)));
        $holders = array_map(static fn (ToDoInList $toDo): string => $toDo->heldBy, $toDos);

        return $this->personDescriptionRetriever->shownTo(
            new MemberId($readerId),
            array_map(static fn (string $contact): ContactId => new ContactId($contact), $contacts),
            array_map(static fn (string $holder): MemberId => new MemberId($holder), $holders),
        );
    }
}
