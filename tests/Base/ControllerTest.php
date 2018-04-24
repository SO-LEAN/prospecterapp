<?php

namespace Tests\App\Base;

use App\Entity\User;
use App\DataFixtures\AppFixtures;
use Symfony\Bundle\FrameworkBundle\Client;
use Liip\FunctionalTestBundle\Test\WebTestCase;
use Doctrine\Common\DataFixtures\ReferenceRepository;
use Symfony\Component\DomCrawler\Crawler;
use Solean\CleanProspecter\UseCase\FindByUserName\FindByUserNameResponse;

abstract class ControllerTest extends WebTestCase
{
    /**
     * @var Client
     */
    protected $client;

    /**
     * @var ReferenceRepository
     */
    protected $fixtures;

    public function setUp() : void
    {
        $this->fixtures = $this->loadFixtures([AppFixtures::class])->getReferenceRepository();
        $this->client = $this->createClient();
    }

    /**
     * @param string $userName
     * @param string $firewall
     */
    protected function login(string $userName = 'prospector-user', string $firewall = 'main') : void
    {
        $entity = $this->fixtures->getReference($userName);

        $user = new User(new FindByUserNameResponse($entity->getRoles(), $entity->getUserName(), $entity->getPassword()));
        $this->loginAs($user, $firewall);

        $this->client = $this->makeClient();
    }

    /**
     * @param Crawler $crawler
     * @param $name
     *
     * @return string
     */
    protected function grab(Crawler $crawler, string $name) : string
    {
        return trim($crawler->filter(sprintf('#func-%s', $name))->eq(0)->text());
    }

    /**
     * @param array $form
     * @param string $page
     * @param string $button
     */
    protected function submitForm(array $form, string $page, string $button) : void
    {
        $crawler = $this->client->request('GET', $page);
        $formCrawler = $crawler->selectButton($button)->form();

        foreach($form as $name => $field)
        {
            $formCrawler[$name] = $field;
        }

        $this->client->submit($formCrawler);
    }
}
