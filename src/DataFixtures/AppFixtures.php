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
        $org = $this->createProspectorOrganization($manager);
        $this->createProspector($manager, $org);
    }

    /**
     * @param ObjectManager $manager
     *
     * @return Organization
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

        return $org;
    }

    /**
     * @param ObjectManager $manager
     * @param Organization  $organization
     */
    private function createProspector(ObjectManager $manager, Organization $organization)
    {
        $user = new User();

        $user->setEmail('prospector@solean-it.io');
        $user->setCountry($organization->getCountry());
        $user->setUserName('prospector');
        $user->setPassword('password');
        $user->setSalt(md5(time()));
        $user->setPassword(md5(sprintf('%s%s', $user->getPassword(), $user->getSalt())));
        $user->addRole('ROLE_PROSPECTOR');
        $user->setOrganization($organization);

        $manager->persist($user);
        $manager->flush();
    }
}
