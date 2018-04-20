<?php

namespace App\DataFixtures;

use Solean\CleanProspecter\Entity\Organization;
use Solean\CleanProspecter\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\Persistence\ObjectManager;

/**
 * Class AppFixtures.
 */
class AppFixtures extends Fixture
{
    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        $this->createProspectorOrganization($manager);
        $this->createProspector($manager);
    }

    /**
     * @param ObjectManager $manager
     */
    private function createProspectorOrganization(ObjectManager $manager)
    {
        $org = new Organization();

        $org->setEmail('org@solean-it.io');
        $org->setCorporateName('SOLEAN IT');
        $org->setForm('SARL');
        $org->setCountry('FR');

        $manager->persist($org);
        $manager->flush();

        $this->addReference('user-organization', $org);
    }

    /**
     * @param ObjectManager $manager
     * @param Organization  $organization
     */
    private function createProspector(ObjectManager $manager)
    {
        $user = new User();
        /**
         * @var Organization
         */
        $org = $this->getReference(('user-organization'));

        $user->setEmail('prospector@solean-it.io');
        $user->setCountry($org->getCountry());
        $user->setUserName('prospector');
        $user->setPassword('password');
        $user->setSalt('salt');
        $user->setPassword(md5(sprintf('%s%s', $user->getPassword(), $user->getSalt())));
        $user->addRole('ROLE_PROSPECTOR');
        $user->setOrganization($org);

        $manager->persist($user);
        $manager->flush();

        $this->addReference('prospector-user', $user);
    }
}
