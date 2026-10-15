<?php

declare(strict_types=1);

namespace Prospecter\Core\Accounts\Domain;

use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\ContactDetails;
use Prospecter\Core\SharedKernel\Domain\File;
use Prospecter\Core\SharedKernel\Domain\LegalIdentity;

/**
 * Aggregate root: an organization using Prospecter, managed by its members.
 */
class Account
{
    private ?File $logo = null;

    private function __construct(
        private readonly AccountId $id,
        private LegalIdentity $legalIdentity,
        private readonly ContactDetails $contactDetails,
    ) {
    }

    public static function open(AccountId $id, LegalIdentity $legalIdentity, ContactDetails $contactDetails = new ContactDetails()): self
    {
        return new self($id, $legalIdentity, $contactDetails);
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

    /**
     * What this member may change of the account: everything if they belong to it, nothing otherwise.
     *
     * @return list<AccountIntent>
     */
    public function possibleIntentsOf(Member $member): array
    {
        return $this->hasMember($member) ? AccountIntent::cases() : [];
    }

    public function reviseLegalIdentity(LegalIdentity $legalIdentity, Member $by): void
    {
        $this->ensureMember($by);
        $this->legalIdentity = $legalIdentity;
    }

    public function changeLogo(File $logo, Member $by): void
    {
        $this->ensureMember($by);
        $this->logo = $logo;
    }

    private function hasMember(Member $member): bool
    {
        return $this->id->equals($member->accountId());
    }

    private function ensureMember(Member $member): void
    {
        if (!$this->hasMember($member)) {
            throw new UnknownAccount($this->id);
        }
    }
}
