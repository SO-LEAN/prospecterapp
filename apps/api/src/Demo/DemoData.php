<?php

namespace App\Demo;

use App\Shared\Identities;
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
use Prospecter\Core\Prospecting\Domain\Organization;
use Prospecter\Core\Prospecting\Domain\OrganizationRepository;
use Prospecter\Core\Prospecting\Domain\Prospector;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\ContactDetails;
use Prospecter\Core\SharedKernel\Domain\Email;
use Prospecter\Core\SharedKernel\Domain\LegalIdentity;
use Prospecter\Core\SharedKernel\Domain\MemberId;
use Prospecter\Core\SharedKernel\Domain\OrganizationId;
use Prospecter\Core\SharedKernel\Domain\PersonId;

/**
 * SOLEAN IT and its prospector. The prospector signs in on the authorization server (apps/auth), whose
 * fixtures give the same member id: the two apps share it, nothing else.
 */
/**
 * The data of the demonstration and of the API tests, written through the repositories, so through the
 * domain (ADR 0048): the account SOLEAN IT, its prospector and its assistant (the member ids of the
 * credentials of the auth app), and Babbage & Co with its contact Ada Lovelace. The identities are fixed:
 * the tests name them.
 */
final readonly class DemoData
{
    public const string SOLEAN_IT = '01900000-0000-7000-8000-0000000000a1';
    public const string PROSPECTOR = '01900000-0000-7000-8000-000000000001';
    /** A prospector without the right to manage personal data: sees the contacts masked. */
    public const string ASSISTANT = '01900000-0000-7000-8000-000000000002';
    private const string OPENED_AT = '2026-10-13 09:00';

    public function __construct(
        private AccountRepository $accounts,
        private MemberRepository $members,
        private PersonRepository $persons,
        private OrganizationRepository $organizations,
        private Identities $identities,
    ) {
    }

    public function load(): void
    {
        $soleanIt = Account::open(new AccountId(self::SOLEAN_IT), new LegalIdentity('SOLEAN IT', 'SARL'), new \DateTimeImmutable(self::OPENED_AT), new ContactDetails(new Email('org@solean-it.io'), language: 'FR'));
        $this->accounts->save($soleanIt);
        $this->aMember(self::PROSPECTOR, 'prospector@solean-it.io', $soleanIt, Role::User, Role::Prospector, Role::PersonalData);
        $this->aMember(self::ASSISTANT, 'assistant@solean-it.io', $soleanIt, Role::User, Role::Prospector);
        $this->aContact($soleanIt);
    }

    private function aMember(string $id, string $email, Account $account, Role ...$roles): void
    {
        $profile = Person::collect(new PersonId($this->identities->next()), $account->id(), Purpose::Membership, new \DateTimeImmutable(self::OPENED_AT), contactDetails: new ContactDetails(new Email($email), language: 'FR'));
        $this->persons->save($profile);
        $this->members->save(Member::register(new MemberId($id), $account->id(), $profile->id(), ...$roles));
    }

    /**
     * An organization SOLEAN IT prospects, Babbage & Co, and its contact: a contact never comes alone (ADR 0042).
     */
    private function aContact(Account $account): void
    {
        $openedAt = new \DateTimeImmutable(self::OPENED_AT);
        $prospector = new Prospector(new MemberId(self::PROSPECTOR), $account->id(), managesPersonalData: true);
        $babbage = Organization::prospectedBy($prospector, new OrganizationId($this->identities->next()), new LegalIdentity('Babbage & Co', 'SAS'), new ContactDetails(new Email('contact@babbage.test')), $openedAt);
        $manager = new Reader($prospector->id, $account->id(), new PersonId($this->identities->next()), true);
        $ada = Person::collectAsContact($manager, new PersonId($this->identities->next()), new PersonName('Ada', 'Lovelace'), 'Directrice commerciale', new ContactDetails(new Email('ada@babbage.test'), '03 88 00 00 42', 'FR'), [$babbage->id()], $openedAt);
        $this->persons->save($ada);
        $babbage->addContact($ada->id(), by: $prospector, at: $openedAt);
        $this->organizations->save($babbage);
    }
}
