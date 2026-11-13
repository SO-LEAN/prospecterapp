<?php

namespace App\Prospecting\Persistence;

use App\Shared\Persistence\Documents;
use App\Shared\Persistence\Hydrator;
use Prospecter\Core\Prospecting\Application\ToDoInList;
use Prospecter\Core\Prospecting\Domain\Model\Due;
use Prospecter\Core\Prospecting\Domain\Model\ToDo;
use Prospecter\Core\Prospecting\Domain\Model\ToDoId;
use Prospecter\Core\Prospecting\Domain\Model\ToDoIntent;
use Prospecter\Core\Prospecting\Domain\Policy\ToDoPossibilities;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\MemberId;
use Prospecter\Core\SharedKernel\Domain\OrganizationId;
use Prospecter\Core\SharedKernel\Domain\PersonId;

/**
 * A to-do as a plain document of its DynamoDB item, and back (ADR 0048), its recorded events never.
 */
final class ToDoDocument
{
    /**
     * @return array<string, mixed>
     */
    public static function of(ToDo $toDo): array
    {
        $state = Hydrator::stateOf($toDo);

        return [
            'id' => (string) $state['id'],
            'accountId' => (string) $state['accountId'],
            'createdBy' => (string) $state['createdBy'],
            'contact' => $state['contact']?->value,
            'keptFor' => array_map(static fn (OrganizationId $organization): string => (string) $organization, $state['keptFor']),
            'text' => $state['text'],
            'due' => ['day' => $state['due']->day, 'time' => $state['due']->time],
            'doneAt' => null === $state['doneAt'] ? null : Documents::moment($state['doneAt']),
            'createdAt' => Documents::moment($state['createdAt']),
            'modifiedAt' => Documents::moment($state['modifiedAt']),
        ];
    }

    /**
     * @param array<string, mixed> $document
     */
    public static function toDo(array $document): ToDo
    {
        return Hydrator::restore(ToDo::class, [
            'id' => new ToDoId($document['id']),
            'accountId' => new AccountId($document['accountId']),
            'createdBy' => new MemberId($document['createdBy']),
            'contact' => null === $document['contact'] ? null : new PersonId($document['contact']),
            'keptFor' => array_map(static fn (string $organization): OrganizationId => new OrganizationId($organization), $document['keptFor']),
            'text' => $document['text'],
            'due' => Hydrator::restore(Due::class, ['day' => $document['due']['day'], 'time' => $document['due']['time']]),
            'doneAt' => null === $document['doneAt'] ? null : Documents::momentOf($document['doneAt']),
            'createdAt' => Documents::momentOf($document['createdAt']),
            'modifiedAt' => Documents::momentOf($document['modifiedAt']),
        ]);
    }

    /**
     * The read model, without restoring the to-do (ADR 0088).
     *
     * @param array<string, mixed> $document
     */
    public static function inList(array $document): ToDoInList
    {
        return new ToDoInList(
            id: $document['id'],
            accountId: $document['accountId'],
            personId: $document['contact'],
            organizationIds: $document['keptFor'],
            text: $document['text'],
            dueOn: $document['due']['day'],
            dueAt: $document['due']['time'],
            doneAt: null === $document['doneAt'] ? null : Documents::momentOf($document['doneAt']),
            heldBy: $document['createdBy'],
            possibleIntents: array_map(static fn (ToDoIntent $intent): string => $intent->name, ToDoPossibilities::given(null !== $document['doneAt'])),
        );
    }
}
