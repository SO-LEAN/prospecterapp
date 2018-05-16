<?php

namespace Tests\App\Base;

use App\Entity\User;
use App\DataFixtures\AppFixtures;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Bundle\FrameworkBundle\Client;
use Liip\FunctionalTestBundle\Test\WebTestCase;
use Doctrine\Common\DataFixtures\ReferenceRepository;
use Solean\CleanProspecter\UseCase\RefreshUser\RefreshUserResponse;

abstract class ControllerTest extends WebTestCase
{
    /**
     * @var Client
     */
    protected $client;

    /**
     * @var array
     */
    protected $serverHeader;

    /**
     * @var ReferenceRepository
     */
    protected $fixtures;


    public function setUp(): void
    {
        $this->fixtures = $this->loadFixtures([AppFixtures::class])->getReferenceRepository();
        $this->serverHeader = ['HTTP_HOST' => 'test.prospecter.io'];

        $this->client = $this->createClient([], $this->serverHeader);
    }

    /**
     * @param string $uri
     * @dataProvider provideUri
     */
    public function testRouteIsReachedIfAuthenticatedAsProspector(string $uri): void
    {
        $this->login();

        $crawler = $this->client->request('GET', $uri);

        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());
        $this->assertEquals('prospector', $this->grab($crawler, 'logged-user'));
    }

    /**
     * @param string $userName
     * @param string $firewall
     */
    protected function login(string $userName = 'prospector-user', string $firewall = 'main'): void
    {
        $entity = $this->fixtures->getReference($userName);

        $user = new User(new RefreshUserResponse($entity->getId(), $entity->getRoles(), $entity->getUserName(), $entity->getPassword(), $entity->getOrganization()->getId()));
        $this->loginAs($user, $firewall);

        $this->client = $this->makeClient(false, $this->serverHeader);
    }

    /**
     * @param Crawler $crawler
     * @param $name
     *
     * @return string
     */
    protected function grab(Crawler $crawler, string $name): string
    {
        return trim($crawler->filter(sprintf('#func-%s', $name))->eq(0)->text());
    }

    /**
     * @param array $form
     * @param string $page
     * @param string $button
     */
    protected function submitForm(array $form, string $page, string $button): void
    {
        $crawler = $this->client->request('GET', $page);
        $formCrawler = $crawler->selectButton($button)->form();

        foreach($form as $name => $field)
        {
            $formCrawler[$name] = $field;
        }

        $this->client->submit($formCrawler);
    }

    /**
     * @return array
     */
    abstract public function provideUri(): array;
}
