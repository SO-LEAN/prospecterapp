<?php
namespace App\DataFixtures;

use Solean\CleanProspecter\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\Persistence\ObjectManager;

/**
 * Class AppFixtures
 * @package App\DataFixtures
 */
class AppFixtures extends Fixture
{
    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
      $this->createProspector($manager);
    }

    /**
     * @param ObjectManager $manager
     */
    private function createProspector(ObjectManager $manager)
    {
        $user = new User();

        $user->setUserName('prospector');
        $user->setPassword('password');
        $user->setSalt(md5(time()));
        $user->setPassword(md5(sprintf('%s%s', $user->getPassword(), $user->getSalt())));
        $user->addRole('ROLE_PROSPECTOR');

        $manager->persist($user);
        $manager->flush();
    }
}