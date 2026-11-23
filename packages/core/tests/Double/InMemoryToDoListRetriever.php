<?php

declare(strict_types=1);

namespace Tests\Core\Double;

use Prospecter\Core\Prospecting\Application\Port\ToDoListRetriever;
use Prospecter\Core\Prospecting\Application\ToDoInList;
use Prospecter\Core\Prospecting\Domain\Exception\UnknownToDo;
use Prospecter\Core\Prospecting\Domain\Model\CalendarPeriod;
use Prospecter\Core\Prospecting\Domain\Model\ContactId;
use Prospecter\Core\Prospecting\Domain\Model\Due;
use Prospecter\Core\Prospecting\Domain\Model\OrganizationId;
use Prospecter\Core\Prospecting\Domain\Model\ToDoId;
use Prospecter\Core\Prospecting\Domain\Model\ToDoIntent;
use Prospecter\Core\Prospecting\Domain\Rule\ToDoPossibilities;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\MemberId;

/**
 * Reads the to-dos that the in-memory repository saved, as the adapter reads them, without their getters.
 */
final readonly class InMemoryToDoListRetriever implements ToDoListRetriever
{
    public function __construct(private InMemoryDatabase $database)
    {
    }

    public function get(ToDoId $id): ToDoInList
    {
        return $this->inList(static fn (State $state): bool => $state->object('id', ToDoId::class)->equals($id))[0] ?? throw UnknownToDo::identifiedBy($id);
    }

    public function forContact(ContactId $contact, AccountId $accountId): array
    {
        return $this->inList(static fn (State $state): bool => true === $state->objectOrNull('contact', ContactId::class)?->equals($contact) && $state->object('accountId', AccountId::class)->equals($accountId));
    }

    public function keptFor(OrganizationId $organization): array
    {
        return $this->inList(static fn (State $state): bool => array_any($state->listOf('keptFor', OrganizationId::class), $organization->equals(...)));
    }

    public function stillToDoBy(MemberId $holder): array
    {
        return $this->inList(static fn (State $state): bool => null === $state->objectOrNull('doneAt', \DateTimeImmutable::class) && $state->object('createdBy', MemberId::class)->equals($holder));
    }

    public function dueIn(MemberId $holder, CalendarPeriod $period): array
    {
        $due = $this->inList(static fn (State $state): bool => $state->object('createdBy', MemberId::class)->equals($holder) && $state->object('due', Due::class)->day >= $period->firstDay() && $state->object('due', Due::class)->day <= $period->lastDay());
        usort($due, static fn (ToDoInList $a, ToDoInList $b): int => [$a->dueOn, $a->dueAt ?? '', $a->id] <=> [$b->dueOn, $b->dueAt ?? '', $b->id]);

        return $due;
    }

    /**
     * @param \Closure(State): bool $kept
     *
     * @return list<ToDoInList>
     */
    private function inList(\Closure $kept): array
    {
        $states = array_filter(array_map(StateOf::the(...), $this->database->toDos()), $kept);

        return array_values(array_map(static fn (State $state): ToDoInList => new ToDoInList(
            $state->object('id', ToDoId::class)->value,
            $state->object('accountId', AccountId::class)->value,
            $state->objectOrNull('contact', ContactId::class)?->value,
            array_map(static fn (OrganizationId $organization): string => $organization->value, $state->listOf('keptFor', OrganizationId::class)),
            $state->string('text'),
            $state->object('due', Due::class)->day,
            $state->object('due', Due::class)->time,
            $state->objectOrNull('doneAt', \DateTimeImmutable::class),
            $state->object('createdBy', MemberId::class)->value,
            array_map(static fn (ToDoIntent $intent): string => $intent->name, ToDoPossibilities::given(null !== $state->objectOrNull('doneAt', \DateTimeImmutable::class))),
        ), $states));
    }
}
