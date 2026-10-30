<?php

declare(strict_types=1);

namespace Prospecter\Core\Accounts\Domain\Model;

use Prospecter\Core\Accounts\Domain\Event\AccountIdentityRevised;
use Prospecter\Core\Accounts\Domain\Event\AccountLogoChanged;
use Prospecter\Core\Accounts\Domain\Event\AccountOpened;
use Prospecter\Core\Accounts\Domain\Event\AccountRelocated;
use Prospecter\Core\Accounts\Domain\Exception\AccountWithoutCorporateName;
use Prospecter\Core\Accounts\Domain\Exception\UnknownAccount;
use Prospecter\Core\Accounts\Domain\Policy\AccountPossibilities;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\Address;
use Prospecter\Core\SharedKernel\Domain\ContactDetails;
use Prospecter\Core\SharedKernel\Domain\File;
use Prospecter\Core\SharedKernel\Domain\GeoPoint;
use Prospecter\Core\SharedKernel\Domain\LegalIdentity;
use Prospecter\Core\SharedKernel\Domain\MemberId;
use Prospecter\Core\SharedKernel\Domain\PersonId;
use Prospecter\Core\SharedKernel\Domain\RecordsEvents;
use Prospecter\Core\SharedKernel\Domain\ReleasesEvents;

/**
 * Aggregate root: an organization using Prospecter, managed by its members.
 */
final class Account implements ReleasesEvents
{
    use RecordsEvents;

    private ?File $logo = null;
    private ?Address $address = null;
    private ?GeoPoint $geoPoint = null;
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

    /**
     * An organization opens its account with its founder, who becomes its first member under the profile given.
     *
     * @throws AccountWithoutCorporateName
     */
    public static function openBy(MemberId $founder, PersonId $founderProfile, AccountId $id, LegalIdentity $legalIdentity, \DateTimeImmutable $at): self
    {
        if (!$legalIdentity->hasCorporateName()) {
            throw AccountWithoutCorporateName::because('An account needs the corporate name of its organization', 'corporateName');
        }

        $account = new self($id, $legalIdentity, new ContactDetails(), $at);
        $account->recordThat(new AccountOpened($id, $founder, $founderProfile, $at));

        return $account;
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

    public function address(): ?Address
    {
        return $this->address;
    }

    public function geoPoint(): ?GeoPoint
    {
        return $this->geoPoint;
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

    public function reviseLegalIdentity(LegalIdentity $legalIdentity, Member $by, \DateTimeImmutable $at): void
    {
        $this->ensurePossible(AccountIntent::ReviseLegalIdentity, $by);
        $this->legalIdentity = $legalIdentity;
        $this->modifiedAt = $at;
        $this->recordThat(new AccountIdentityRevised($this->id, $by->id(), $at));
    }

    public function changeLogo(File $logo, Member $by, \DateTimeImmutable $at): void
    {
        $this->ensurePossible(AccountIntent::ChangeLogo, $by);
        $this->logo = $logo;
        $this->modifiedAt = $at;
        $this->recordThat(new AccountLogoChanged($this->id, $by->id(), $at));
    }

    /**
     * Where the organization of the account is, the start of the routes to the organizations it prospects; an
     * address without any part erases it.
     */
    public function relocateTo(Address $address, ?GeoPoint $geoPoint, Member $by, \DateTimeImmutable $at): void
    {
        $this->ensurePossible(AccountIntent::Relocate, $by);
        $this->address = $address->isEmpty() ? null : $address;
        $this->geoPoint = $geoPoint;
        $this->modifiedAt = $at;
        $this->recordThat(new AccountRelocated($this->id, $by->id(), $at));
    }

    /**
     * An account is unknown to whom does not belong to it.
     *
     * @throws UnknownAccount
     */
    private function ensurePossible(AccountIntent $intent, Member $by): void
    {
        if (!in_array($intent, AccountPossibilities::given($this->id->equals($by->accountId())), true)) {
            throw UnknownAccount::identifiedBy($this->id);
        }
    }
}
