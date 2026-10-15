<?php

namespace App\DataFixtures;

use App\Shared\Identities;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Prospecter\Core\Accounts\Domain\Account;
use Prospecter\Core\Accounts\Domain\AccountRepository;
use Prospecter\Core\Accounts\Domain\Member;
use Prospecter\Core\Accounts\Domain\MemberRepository;
use Prospecter\Core\Accounts\Domain\Role;
use Prospecter\Core\PersonalData\Domain\Person;
use Prospecter\Core\PersonalData\Domain\PersonRepository;
use Prospecter\Core\PersonalData\Domain\Purpose;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\ContactDetails;
use Prospecter\Core\SharedKernel\Domain\Email;
use Prospecter\Core\SharedKernel\Domain\LegalIdentity;
use Prospecter\Core\SharedKernel\Domain\MemberId;
use Prospecter\Core\SharedKernel\Domain\PersonId;

/**
 * SOLEAN IT and its prospector. The prospector signs in on the authorization server (apps/auth), whose
 * fixtures give the same member id: the two apps share it, nothing else.
 */
class AppFixtures extends Fixture
{
    public const string PROSPECTOR = '01900000-0000-7000-8000-000000000001';
    private const string OPENED_AT = '2026-10-13 09:00';

    public function __construct(
        private readonly AccountRepository $accounts,
        private readonly MemberRepository $members,
        private readonly PersonRepository $persons,
        private readonly Identities $identities,
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        $soleanIt = Account::open(new AccountId($this->identities->next()), new LegalIdentity('SOLEAN IT', 'SARL'), new \DateTimeImmutable(self::OPENED_AT), new ContactDetails(new Email('org@solean-it.io'), language: 'FR'));
        $this->accounts->save($soleanIt);
        $this->addReference('solean-it-account', $soleanIt);
        $this->itsProspector($soleanIt);
    }

    private function itsProspector(Account $account): void
    {
        $profile = Person::collect(new PersonId($this->identities->next()), $account->id(), Purpose::Membership, new \DateTimeImmutable(self::OPENED_AT), contactDetails: new ContactDetails(new Email('prospector@solean-it.io'), language: 'FR'));
        $this->persons->save($profile);
        $prospector = Member::register(new MemberId(self::PROSPECTOR), $account->id(), $profile->id(), Role::User, Role::Prospector, Role::PersonalData);
        $this->members->save($prospector);
        $this->addReference('prospector-user', $prospector);
    }
}
