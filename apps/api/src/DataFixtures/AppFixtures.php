<?php

namespace App\DataFixtures;

use App\Auth\Passwords;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Prospecter\Core\Entity\Organization;
use Prospecter\Core\Entity\User;

class AppFixtures extends Fixture
{
    public function __construct(private readonly Passwords $passwords)
    {
    }

    public function load(ObjectManager $manager): void
    {
        $this->createProspectorOrganization($manager);
        $this->createProspector($manager);
    }

    private function createProspectorOrganization(ObjectManager $manager): void
    {
        $org = new Organization();

        $org->setEmail('org@solean-it.io');
        $org->setCorporateName('SOLEAN IT');
        $org->setForm('SARL');
        $org->setLanguage('FR');

        $manager->persist($org);
        $manager->flush();

        $this->addReference('user-organization', $org);
    }

    private function createProspector(ObjectManager $manager): void
    {
        $user = new User();
        $org = $this->getReference('user-organization', Organization::class);

        $user->setEmail('prospector@solean-it.io');
        $user->setLanguage($org->getLanguage());
        $user->setUserName('prospector');
        $user->addRole('ROLE_PROSPECTOR');
        $user->addRole('ROLE_USER');
        $user->setOrganization($org);

        $manager->persist($user);
        $manager->flush();
        $this->passwords->set($user->getId(), 'password');

        $this->addReference('prospector-user', $user);
    }
}
