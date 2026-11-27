<?php

declare(strict_types=1);

namespace Prospecter\Core\Campaigns\Domain\Service;

use Prospecter\Core\Campaigns\Domain\Exception\UnknownContact;
use Prospecter\Core\Campaigns\Domain\Model\Addressee;
use Prospecter\Core\Campaigns\Domain\Model\AddresseeOrganization;
use Prospecter\Core\Campaigns\Domain\Model\ContactId;
use Prospecter\Core\Campaigns\Domain\Port\AddresseeNames;
use Prospecter\Core\Campaigns\Domain\Port\AddresseeOrganizations;
use Prospecter\Core\SharedKernel\Domain\Identity\AccountId;
use Prospecter\Core\SharedKernel\Domain\Identity\MemberId;

/**
 * What a message calls a contact: their name as PersonalData lets the member read it, and the organization Campaigns
 * shows among those Prospecting knows them by (ADR 0144, 0223).
 */
final readonly class Addressees
{
    public function __construct(
        private AddresseeNames $names,
        private AddresseeOrganizations $organizations,
    ) {
    }

    /**
     * @throws UnknownContact when the member reads no such contact in their account
     */
    public function addressee(AccountId $accountId, MemberId $reader, ContactId $contact): Addressee
    {
        $named = $this->names->nameOf($reader, $contact);
        $organization = AddresseeOrganization::shownAmong($this->organizations->of($accountId, $contact));

        return new Addressee($named->firstName, $named->lastName, $named->civility, $organization?->name, $named->jobTitle);
    }
}
