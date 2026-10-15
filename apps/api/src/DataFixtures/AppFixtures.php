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
use Prospecter\Core\PersonalData\Domain\PersonName;
use Prospecter\Core\PersonalData\Domain\PersonRepository;
use Prospecter\Core\PersonalData\Domain\Purpose;
use Prospecter\Core\PersonalData\Domain\Reader;
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
    /** A prospector without the right to manage personal data: sees the contacts masked. */
    public const string ASSISTANT = '01900000-0000-7000-8000-000000000002';
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
        $this->addReference('prospector-user', $this->aMember(self::PROSPECTOR, 'prospector@solean-it.io', $soleanIt, Role::User, Role::Prospector, Role::PersonalData));
        $this->aMember(self::ASSISTANT, 'assistant@solean-it.io', $soleanIt, Role::User, Role::Prospector);
        $this->aContact($soleanIt);
    }

    private function aMember(string $id, string $email, Account $account, Role ...$roles): Member
    {
        $profile = Person::collect(new PersonId($this->identities->next()), $account->id(), Purpose::Membership, new \DateTimeImmutable(self::OPENED_AT), contactDetails: new ContactDetails(new Email($email), language: 'FR'));
        $this->persons->save($profile);
        $member = Member::register(new MemberId($id), $account->id(), $profile->id(), ...$roles);
        $this->members->save($member);

        return $member;
    }

    private function aContact(Account $account): void
    {
        $manager = new Reader(new MemberId(self::PROSPECTOR), $account->id(), new PersonId($this->identities->next()), true);
        $this->persons->save(Person::collectAsContact($manager, new PersonId($this->identities->next()), new PersonName('Ada', 'Lovelace'), 'Directrice commerciale', new ContactDetails(new Email('ada@acme.test'), '03 88 00 00 42', 'FR'), new \DateTimeImmutable(self::OPENED_AT)));
    }
}
