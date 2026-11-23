<?php

declare(strict_types=1);

namespace Prospecter\Core\Accounts\Domain\Model;

use Prospecter\Core\Accounts\Domain\Event\AccountIdentityRevised;
use Prospecter\Core\Accounts\Domain\Event\AccountLogoChanged;
use Prospecter\Core\Accounts\Domain\Event\AccountOpened;
use Prospecter\Core\Accounts\Domain\Event\AccountRelocated;
use Prospecter\Core\Accounts\Domain\Exception\AccountWithoutCorporateName;
use Prospecter\Core\Accounts\Domain\Exception\UnknownAccount;
use Prospecter\Core\Accounts\Domain\Rule\AccountPossibilities;
use Prospecter\Core\SharedKernel\Domain\Company\LegalIdentity;
use Prospecter\Core\SharedKernel\Domain\Contact\Address;
use Prospecter\Core\SharedKernel\Domain\Contact\ContactDetails;
use Prospecter\Core\SharedKernel\Domain\Event\RecordsEvents;
use Prospecter\Core\SharedKernel\Domain\Event\ReleasesEvents;
use Prospecter\Core\SharedKernel\Domain\File\File;
use Prospecter\Core\SharedKernel\Domain\Gesture\Gesture;
use Prospecter\Core\SharedKernel\Domain\Identity\AccountId;
use Prospecter\Core\SharedKernel\Domain\Location\GeoPoint;

/**
 * Aggregate root: an organization that uses Prospecter. Its members manage it.
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
     * An organization opens its account with its founder. The founder becomes its first member under the given
     * profile. The gesture names the new account and its founder.
     *
     * @throws AccountWithoutCorporateName
     */
    public static function openBy(Gesture $gesture, ProfileId $founderProfile, LegalIdentity $legalIdentity): self
    {
        if (!$legalIdentity->hasCorporateName()) {
            throw AccountWithoutCorporateName::because('An account needs its organization\'s corporate name', 'corporateName');
        }

        $account = new self($gesture->accountId, $legalIdentity, new ContactDetails(), $gesture->at);
        $account->recordThat(new AccountOpened($gesture->accountId, $gesture->by, $founderProfile, $gesture->at));

        return $account;
    }

    public function id(): AccountId
    {
        return $this->id;
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
     * Sets where the account's organization is. The routes to the organizations it prospects start there. An address
     * without any part erases it.
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
     * An account is unknown to anyone who does not belong to it.
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
