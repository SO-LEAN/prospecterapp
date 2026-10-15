<?php

namespace App\DataFixtures;

use App\Auth\Passwords;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Prospecter\Core\Domain\Organization\LegalIdentity;
use Prospecter\Core\Domain\Organization\Organization;
use Prospecter\Core\Domain\Organization\OrganizationRepository;
use Prospecter\Core\Domain\Shared\ContactDetails;
use Prospecter\Core\Domain\Shared\Email;
use Prospecter\Core\Domain\User\Role;
use Prospecter\Core\Domain\User\User;
use Prospecter\Core\Domain\User\UserRepository;

class AppFixtures extends Fixture
{
    public function __construct(
        private readonly OrganizationRepository $organizations,
        private readonly UserRepository $users,
        private readonly Passwords $passwords,
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        $soleanIt = Organization::usingProspecter($this->organizations->nextIdentity(), new LegalIdentity('SOLEAN IT', 'SARL'));
        $prospector = $this->itsProspector($soleanIt);
        $soleanIt->changeContactDetails(new ContactDetails(new Email('org@solean-it.io'), language: 'FR'), by: $prospector);
        $this->organizations->save($soleanIt);
        $this->addReference('user-organization', $soleanIt);
    }

    private function itsProspector(Organization $organization): User
    {
        $prospector = User::register($this->users->nextIdentity(), $organization->id(), 'prospector', Role::User, Role::Prospector);
        $prospector->changeContactDetails(new ContactDetails(new Email('prospector@solean-it.io'), language: 'FR'));
        $this->users->save($prospector);
        $this->passwords->set($prospector->id(), 'password');
        $this->addReference('prospector-user', $prospector);

        return $prospector;
    }
}
