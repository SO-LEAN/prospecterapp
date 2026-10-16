<?php

declare(strict_types=1);

namespace Prospecter\Core\Accounts\Domain;

use Prospecter\Core\Accounts\Domain\Event\AccountIdentityRevised;
use Prospecter\Core\Accounts\Domain\Event\AccountLogoChanged;
use Prospecter\Core\Accounts\Domain\Exception\UnknownAccount;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\ContactDetails;
use Prospecter\Core\SharedKernel\Domain\File;
use Prospecter\Core\SharedKernel\Domain\LegalIdentity;
use Prospecter\Core\SharedKernel\Domain\RecordsEvents;
use Prospecter\Core\SharedKernel\Domain\ReleasesEvents;

/**
 * Aggregate root: an organization using Prospecter, managed by its members.
 */
final class Account implements ReleasesEvents
{
    use RecordsEvents;

    private ?File $logo = null;
    private \DateTimeImmutable $modifiedAt;

    private function __construct(
        private readonly AccountId $id,
        private LegalIdentity $legalIdentity,
        private readonly ContactDetails $contactDetails,
        private readonly \DateTimeImmutable $createdAt,
    ) {
        $this->modifiedAt = $createdAt;
    }

    public static function open(AccountId $id, LegalIdentity $legalIdentity, \DateTimeImmutable $at, ContactDetails $contactDetails = new ContactDetails()): self
    {
        return new self($id, $legalIdentity, $contactDetails, $at);
    }

    public function id(): AccountId
    {
        return $this->id;
    }

    public function legalIdentity(): LegalIdentity
    {
        return $this->legalIdentity;
    }

    public function contactDetails(): ContactDetails
    {
        return $this->contactDetails;
    }

    public function logo(): ?File
    {
        return $this->logo;
    }

    public function createdAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    /**
     * When an intention last changed it: its creation, then every change.
     */
    public function modifiedAt(): \DateTimeImmutable
    {
        return $this->modifiedAt;
    }

    /**
     * What this member may change of the account: everything if they belong to it, nothing otherwise.
     *
     * @return list<AccountIntent>
     */
    public function possibleIntentsOf(Member $member): array
    {
        return $this->hasMember($member) ? AccountIntent::cases() : [];
    }

    public function reviseLegalIdentity(LegalIdentity $legalIdentity, Member $by, \DateTimeImmutable $at): void
    {
        $this->ensureMember($by);
        $this->legalIdentity = $legalIdentity;
        $this->modifiedAt = $at;
        $this->recordThat(new AccountIdentityRevised($this->id, $by->id(), $at));
    }

    public function changeLogo(File $logo, Member $by, \DateTimeImmutable $at): void
    {
        $this->ensureMember($by);
        $this->logo = $logo;
        $this->modifiedAt = $at;
        $this->recordThat(new AccountLogoChanged($this->id, $by->id(), $at));
    }

    private function hasMember(Member $member): bool
    {
        return $this->id->equals($member->accountId());
    }

    private function ensureMember(Member $member): void
    {
        if (!$this->hasMember($member)) {
            throw UnknownAccount::identifiedBy($this->id);
        }
    }
}
