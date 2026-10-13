<?php

namespace App\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Solean\CleanProspecter\Entity\Organization;
use Solean\CleanProspecter\Entity\User;

class AppFixtures extends Fixture
{
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
        $user->setPassword('password');
        $user->setSalt('salt');
        $user->encodePassword();
        $user->addRole('ROLE_PROSPECTOR');
        $user->addRole('ROLE_USER');
        $user->setOrganization($org);

        $manager->persist($user);
        $manager->flush();

        $this->addReference('prospector-user', $user);
    }
}
