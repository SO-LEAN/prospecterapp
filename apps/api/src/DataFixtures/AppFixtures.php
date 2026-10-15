<?php

namespace App\DataFixtures;

use App\Auth\LoginEmails;
use App\Auth\Passwords;
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
use Prospecter\Core\SharedKernel\Domain\ContactDetails;
use Prospecter\Core\SharedKernel\Domain\Email;
use Prospecter\Core\SharedKernel\Domain\LegalIdentity;

class AppFixtures extends Fixture
{
    public function __construct(
        private readonly AccountRepository $accounts,
        private readonly MemberRepository $members,
        private readonly PersonRepository $persons,
        private readonly LoginEmails $loginEmails,
        private readonly Passwords $passwords,
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        $soleanIt = Account::open($this->accounts->nextIdentity(), new LegalIdentity('SOLEAN IT', 'SARL'), new ContactDetails(new Email('org@solean-it.io'), language: 'FR'));
        $this->accounts->save($soleanIt);
        $this->addReference('solean-it-account', $soleanIt);
        $this->itsProspector($soleanIt);
    }

    private function itsProspector(Account $account): void
    {
        $profile = Person::collect($this->persons->nextIdentity(), $account->id(), Purpose::Membership, contactDetails: new ContactDetails(new Email('prospector@solean-it.io'), language: 'FR'));
        $this->persons->save($profile);
        $prospector = Member::register($this->members->nextIdentity(), $account->id(), $profile->id(), Role::User, Role::Prospector, Role::PersonalData);
        $this->members->save($prospector);
        $this->loginEmails->assign($prospector->id(), new Email('prospector@solean-it.io'));
        $this->passwords->set($prospector->id(), 'password');
        $this->addReference('prospector-user', $prospector);
    }
}
