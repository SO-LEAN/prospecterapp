<?php

namespace App\DataFixtures;

use App\Auth\Passwords;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Prospecter\Core\Accounts\Domain\Account;
use Prospecter\Core\Accounts\Domain\AccountRepository;
use Prospecter\Core\Accounts\Domain\Member;
use Prospecter\Core\Accounts\Domain\MemberRepository;
use Prospecter\Core\Accounts\Domain\Role;
use Prospecter\Core\SharedKernel\Domain\ContactDetails;
use Prospecter\Core\SharedKernel\Domain\Email;
use Prospecter\Core\SharedKernel\Domain\LegalIdentity;

class AppFixtures extends Fixture
{
    public function __construct(
        private readonly AccountRepository $accounts,
        private readonly MemberRepository $members,
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
        $prospector = Member::register($this->members->nextIdentity(), $account->id(), 'prospector', Role::User, Role::Prospector);
        $prospector->changeContactDetails(new ContactDetails(new Email('prospector@solean-it.io'), language: 'FR'));
        $this->members->save($prospector);
        $this->passwords->set($prospector->id(), 'password');
        $this->addReference('prospector-user', $prospector);
    }
}
