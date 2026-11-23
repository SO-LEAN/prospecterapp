<?php

declare(strict_types=1);

namespace App\Prospecting\Persistence;

use App\Shared\Persistence\Documents;
use App\Shared\Persistence\Hydrator;
use App\Shared\Typed;
use Prospecter\Core\Prospecting\Application\ToDoInList;
use Prospecter\Core\Prospecting\Domain\Model\ContactId;
use Prospecter\Core\Prospecting\Domain\Model\Due;
use Prospecter\Core\Prospecting\Domain\Model\OrganizationId;
use Prospecter\Core\Prospecting\Domain\Model\ToDo;
use Prospecter\Core\Prospecting\Domain\Model\ToDoId;
use Prospecter\Core\Prospecting\Domain\Model\ToDoIntent;
use Prospecter\Core\Prospecting\Domain\Rule\ToDoPossibilities;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\MemberId;

/**
 * Turns a to-do into a plain document for its DynamoDB item, and back (ADR 0048). Its recorded events are never
 * stored.
 */
final class ToDoDocument
{
    /**
     * @return array<string, mixed>
     */
    public static function of(ToDo $toDo): array
    {
        $state = Hydrator::stateOf($toDo);
        $due = Typed::object($state['due'], Due::class);

        return [
            'id' => Typed::object($state['id'], ToDoId::class)->value,
            'accountId' => Typed::object($state['accountId'], AccountId::class)->value,
            'createdBy' => Typed::object($state['createdBy'], MemberId::class)->value,
            'contact' => Typed::objectOrNull($state['contact'], ContactId::class)?->value,
            'keptFor' => array_map(static fn (OrganizationId $organization): string => $organization->value, Typed::objects($state['keptFor'], OrganizationId::class)),
            'text' => Typed::string($state['text']),
            'due' => ['day' => $due->day, 'time' => $due->time],
            'doneAt' => Documents::momentOrNone(Typed::objectOrNull($state['doneAt'], \DateTimeImmutable::class)),
            'createdAt' => Documents::moment(Typed::object($state['createdAt'], \DateTimeImmutable::class)),
            'modifiedAt' => Documents::moment(Typed::object($state['modifiedAt'], \DateTimeImmutable::class)),
        ];
    }

    /**
     * Restores the to-do from its item's untyped document.
     */
    public static function toDo(mixed $document): ToDo
    {
        $document = Typed::document($document);
        $contact = Typed::stringOrNull($document['contact']);
        $due = Typed::document($document['due']);

        return Hydrator::restore(ToDo::class, [
            'id' => new ToDoId(Typed::string($document['id'])),
            'accountId' => new AccountId(Typed::string($document['accountId'])),
            'createdBy' => new MemberId(Typed::string($document['createdBy'])),
            'contact' => null === $contact ? null : new ContactId($contact),
            'keptFor' => array_map(static fn (string $organization): OrganizationId => new OrganizationId($organization), Typed::strings($document['keptFor'])),
            'text' => Typed::string($document['text']),
            'due' => Hydrator::restore(Due::class, ['day' => Typed::string($due['day']), 'time' => Typed::stringOrNull($due['time'])]),
            'doneAt' => Documents::momentOfOrNone(Typed::stringOrNull($document['doneAt'])),
            'createdAt' => Documents::momentOf(Typed::string($document['createdAt'])),
            'modifiedAt' => Documents::momentOf(Typed::string($document['modifiedAt'])),
        ]);
    }

    /**
     * Builds the read model from its item's untyped document, without restoring the to-do (ADR 0088).
     */
    public static function inList(mixed $document): ToDoInList
    {
        $document = Typed::document($document);
        $due = Typed::document($document['due']);
        $doneAt = Documents::momentOfOrNone(Typed::stringOrNull($document['doneAt']));

        return new ToDoInList(
            id: Typed::string($document['id']),
            accountId: Typed::string($document['accountId']),
            personId: Typed::stringOrNull($document['contact']),
            organizationIds: Typed::strings($document['keptFor']),
            text: Typed::string($document['text']),
            dueOn: Typed::string($due['day']),
            dueAt: Typed::stringOrNull($due['time']),
            doneAt: $doneAt,
            heldBy: Typed::string($document['createdBy']),
            possibleIntents: array_map(static fn (ToDoIntent $intent): string => $intent->name, ToDoPossibilities::given(null !== $doneAt)),
        );
    }
}
