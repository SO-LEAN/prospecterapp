<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Domain\Model;

use Prospecter\Core\Prospecting\Domain\Event\ToDoErased;
use Prospecter\Core\Prospecting\Domain\Event\ToDoLeftWithoutOrganization;
use Prospecter\Core\Prospecting\Domain\Event\ToDoNoLongerKeptFor;
use Prospecter\Core\Prospecting\Domain\Event\ToDoNoted;
use Prospecter\Core\Prospecting\Domain\Event\ToDoRevised;
use Prospecter\Core\Prospecting\Domain\Event\ToDoTickedOff;
use Prospecter\Core\Prospecting\Domain\Event\ToDoUnticked;
use Prospecter\Core\Prospecting\Domain\Exception\ToDoWithoutText;
use Prospecter\Core\Prospecting\Domain\Policy\ToDoPossibilities;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\Exception\NotPossibleInItsState;
use Prospecter\Core\SharedKernel\Domain\MemberId;
use Prospecter\Core\SharedKernel\Domain\OrganizationId;
use Prospecter\Core\SharedKernel\Domain\PersonId;
use Prospecter\Core\SharedKernel\Domain\RecordsEvents;
use Prospecter\Core\SharedKernel\Domain\ReleasesEvents;

/**
 * Aggregate root: work a prospector has to prepare for a contact or an organization, without them in front, due on a
 * day (ADR 0112). Held by the member who noted it, its author. Its text may hold personal data: like an exchange, it
 * is kept for the organizations the contact is part of when it is noted, or for the organization itself, and lives
 * as long as one of them is prospected; unlike an exchange, it is erased with its contact.
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
        private readonly ?PersonId $contact,
        private array $keptFor,
        string $text,
        private Due $due,
        private readonly \DateTimeImmutable $createdAt,
    ) {
        $this->text = self::said($text);
        $this->modifiedAt = $createdAt;
    }

    /**
     * Kept, like an exchange, for the organizations the contact is part of now.
     *
     * @param list<OrganizationId> $organizationsOfTheContact
     *
     * @throws ToDoWithoutText
     */
    public static function forAContact(AccountId $accountId, MemberId $by, ToDoId $id, PersonId $contact, array $organizationsOfTheContact, string $text, Due $due, \DateTimeImmutable $at): self
    {
        return self::noted(new self($id, $accountId, $by, $contact, $organizationsOfTheContact, $text, $due, $at));
    }

    /**
     * @throws ToDoWithoutText
     */
    public static function forTheOrganization(Organization $organization, MemberId $by, ToDoId $id, string $text, Due $due, \DateTimeImmutable $at): self
    {
        return self::noted(new self($id, $organization->accountId(), $by, null, [$organization->id()], $text, $due, $at));
    }

    private static function noted(self $toDo): self
    {
        $toDo->recordThat(new ToDoNoted($toDo->accountId, $toDo->createdBy, $toDo->id, $toDo->createdAt));

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
     * Erased everywhere, once the repository removed it.
     */
    public function erase(MemberId $by, \DateTimeImmutable $at): void
    {
        $this->ensurePossible(ToDoIntent::EraseToDo);
        $this->recordThat(new ToDoErased($this->accountId, $by, $this->id, $at));
    }

    /**
     * The account stopped prospecting this organization: kept for no organization any more, the to-do must not
     * stay (ToDoLeftWithoutOrganization).
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
