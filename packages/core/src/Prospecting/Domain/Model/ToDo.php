<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Domain\Model;

use Prospecter\Core\Prospecting\Domain\Event\ToDoErased;
use Prospecter\Core\Prospecting\Domain\Event\ToDoKeptFor;
use Prospecter\Core\Prospecting\Domain\Event\ToDoLeftWithoutOrganization;
use Prospecter\Core\Prospecting\Domain\Event\ToDoNoLongerKeptFor;
use Prospecter\Core\Prospecting\Domain\Event\ToDoNoted;
use Prospecter\Core\Prospecting\Domain\Event\ToDoRevised;
use Prospecter\Core\Prospecting\Domain\Event\ToDoTickedOff;
use Prospecter\Core\Prospecting\Domain\Event\ToDoUnticked;
use Prospecter\Core\Prospecting\Domain\Exception\ToDoWithoutText;
use Prospecter\Core\Prospecting\Domain\Rule\ToDoPossibilities;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\Exception\NotPossibleInItsState;
use Prospecter\Core\SharedKernel\Domain\Gesture;
use Prospecter\Core\SharedKernel\Domain\MemberId;
use Prospecter\Core\SharedKernel\Domain\RecordsEvents;
use Prospecter\Core\SharedKernel\Domain\ReleasesEvents;

/**
 * Aggregate root: work a prospector has to prepare for a contact or an organization, without them present. It is due
 * on a day (ADR 0113). Its author, the member who logged it, holds it. Its text may hold personal data. Like an
 * exchange, it is kept for the organizations the contact is part of when it is logged, or for the organization
 * itself. It lives as long as one of them is prospected. Unlike an exchange, it is erased with its contact.
 */
final class ToDo implements ReleasesEvents
{
    use RecordsEvents;

    private string $text;
    private \DateTimeImmutable $modifiedAt;
    private ?\DateTimeImmutable $doneAt = null;

    /**
     * @param list<OrganizationId> $keptFor
     *
     * @throws ToDoWithoutText
     */
    private function __construct(
        private readonly ToDoId $id,
        private readonly AccountId $accountId,
        private readonly MemberId $createdBy,
        private readonly ?ContactId $contact,
        private array $keptFor,
        string $text,
        private Due $due,
        private readonly \DateTimeImmutable $createdAt,
    ) {
        $this->text = self::said($text);
        $this->modifiedAt = $createdAt;
    }

    /**
     * Like an exchange, the to-do is kept for its correspondent's current organizations. These are the organizations
     * the contact is part of, or the organization itself.
     *
     * @throws ToDoWithoutText
     */
    public static function noted(Gesture $gesture, ToDoId $id, ContactCorrespondent|OrganizationCorrespondent $correspondent, Task $task): self
    {
        $toDo = new self($id, $gesture->accountId, $gesture->by, $correspondent->contact, $correspondent->keptFor, $task->text, $task->due, $gesture->at);
        $toDo->recordThat(new ToDoNoted($gesture->accountId, $gesture->by, $id, $gesture->at));

        return $toDo;
    }

    /**
     * @throws ToDoWithoutText
     */
    private static function said(string $text): string
    {
        $said = trim($text);

        return '' === $said ? throw ToDoWithoutText::because('A to-do has a text', 'text') : $said;
    }

    public function id(): ToDoId
    {
        return $this->id;
    }

    public function accountId(): AccountId
    {
        return $this->accountId;
    }

    public function isKeptForAnOrganization(): bool
    {
        return [] !== $this->keptFor;
    }

    /**
     * A member attached its contact to an organization. From now on, that organization keeps the to-do (ADR 0146).
     */
    public function keepFor(OrganizationId $organization, MemberId $by, \DateTimeImmutable $at): void
    {
        $this->keptFor = [$organization];
        $this->modifiedAt = $at;
        $this->recordThat(new ToDoKeptFor($this->accountId, $by, $this->id, $organization, $at));
    }

    /**
     * @throws ToDoWithoutText
     */
    public function revise(string $text, Due $due, MemberId $by, \DateTimeImmutable $at): void
    {
        $this->ensurePossible(ToDoIntent::ReviseToDo);
        $this->text = self::said($text);
        $this->due = $due;
        $this->modifiedAt = $at;
        $this->recordThat(new ToDoRevised($this->accountId, $by, $this->id, $at));
    }

    /**
     * @throws NotPossibleInItsState
     */
    public function tickOff(MemberId $by, \DateTimeImmutable $at): void
    {
        $this->ensurePossible(ToDoIntent::TickOffToDo);
        $this->doneAt = $at;
        $this->modifiedAt = $at;
        $this->recordThat(new ToDoTickedOff($this->accountId, $by, $this->id, $at));
    }

    /**
     * @throws NotPossibleInItsState
     */
    public function untick(MemberId $by, \DateTimeImmutable $at): void
    {
        $this->ensurePossible(ToDoIntent::UntickToDo);
        $this->doneAt = null;
        $this->modifiedAt = $at;
        $this->recordThat(new ToDoUnticked($this->accountId, $by, $this->id, $at));
    }

    /**
     * The to-do is erased everywhere once the repository removes it.
     */
    public function erase(MemberId $by, \DateTimeImmutable $at): void
    {
        $this->ensurePossible(ToDoIntent::EraseToDo);
        $this->recordThat(new ToDoErased($this->accountId, $by, $this->id, $at));
    }

    /**
     * The account stopped prospecting this organization. A to-do that no organization keeps any more must not stay
     * (ToDoLeftWithoutOrganization).
     */
    public function stopKeepingFor(OrganizationId $organization, MemberId $by, \DateTimeImmutable $at): void
    {
        $this->keptFor = array_values(array_filter($this->keptFor, static fn (OrganizationId $keptFor): bool => !$keptFor->equals($organization)));
        $this->modifiedAt = $at;
        $this->recordThat(new ToDoNoLongerKeptFor($this->accountId, $by, $this->id, $organization, $at));

        if (!$this->isKeptForAnOrganization()) {
            $this->recordThat(new ToDoLeftWithoutOrganization($this->accountId, $by, $this->id, $at));
        }
    }

    /**
     * @throws NotPossibleInItsState
     */
    private function ensurePossible(ToDoIntent $intent): void
    {
        if (in_array($intent, ToDoPossibilities::given(null !== $this->doneAt), true)) {
            return;
        }

        throw NotPossibleInItsState::because(ToDoIntent::TickOffToDo === $intent ? 'A to-do done is not ticked off again' : 'Only a to-do done is unticked');
    }
}
