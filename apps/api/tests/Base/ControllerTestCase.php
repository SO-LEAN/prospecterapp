<?php

namespace Tests\App\Base;

use App\DataFixtures\AppFixtures;
use App\DataFixtures\OAuthClientFixtures;
use App\Entity\User;
use Doctrine\Common\DataFixtures\Executor\ORMExecutor;
use Doctrine\Common\DataFixtures\Loader;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Doctrine\Common\DataFixtures\ReferenceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Prospecter\Core\Domain\User\User as UserEntity;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DomCrawler\Crawler;

abstract class ControllerTestCase extends WebTestCase
{
    protected KernelBrowser $client;
    protected ReferenceRepository $fixtures;

    protected function setUp(): void
    {
        $this->client = static::createClient([], ['HTTP_HOST' => 'api.test.prospecter.io']);
        $this->fixtures = $this->loadFixtures();
    }

    protected function login(string $reference = 'prospector-user'): void
    {
        $entity = $this->fixtures->getReference($reference, UserEntity::class);

        $this->client->loginUser(User::from($entity), 'main');
    }

    protected function grab(Crawler $crawler, string $name): string
    {
        return trim($crawler->filter(sprintf('#func-%s', $name))->eq(0)->text());
    }

    /**
     * @param array<string, string> $fields
     */
    protected function submitForm(array $fields, string $page, string $button): void
    {
        $crawler = $this->client->request('GET', $page);
        $this->client->submit($crawler->selectButton($button)->form(), $fields);
    }

    private function loadFixtures(): ReferenceRepository
    {
        $entityManager = static::getContainer()->get(EntityManagerInterface::class);

        $schemaTool = new SchemaTool($entityManager);
        $metadata = $entityManager->getMetadataFactory()->getAllMetadata();
        $schemaTool->dropDatabase();
        $schemaTool->createSchema($metadata);

        $loader = new Loader();
        $loader->addFixture(static::getContainer()->get(AppFixtures::class));
        $loader->addFixture(static::getContainer()->get(OAuthClientFixtures::class));

        $executor = new ORMExecutor($entityManager, new ORMPurger($entityManager));
        $executor->execute($loader->getFixtures());

        return $executor->getReferenceRepository();
    }
}
