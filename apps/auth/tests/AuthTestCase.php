<?php

namespace App\Tests;

use App\DataFixtures\CredentialsFixtures;
use App\DataFixtures\OAuthClientFixtures;
use App\Security\SignedInMember;
use Doctrine\Common\DataFixtures\Executor\ORMExecutor;
use Doctrine\Common\DataFixtures\Loader;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DomCrawler\Crawler;

/**
 * The authorization server as the SPA uses it, on a database recreated for each test with the OAuth client and
 * the credentials of the prospector.
 */
abstract class AuthTestCase extends WebTestCase
{
    protected KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = static::createClient([], ['HTTP_HOST' => 'api.test.prospecter.io']);
        $this->loadFixtures();
    }

    protected function signIn(): void
    {
        $this->client->loginUser(new SignedInMember(CredentialsFixtures::PROSPECTOR, 'prospector@solean-it.io'), 'main');
    }

    /**
     * Signs in, then runs the authorization code flow with PKCE like the SPA, and sends the access token.
     *
     * @return array<string, mixed> the token response
     */
    protected function signInWithAToken(): array
    {
        $this->signIn();
        $tokens = $this->obtainTokens();
        $this->client->setServerParameter('HTTP_AUTHORIZATION', 'Bearer '.$tokens['access_token']);

        return $tokens;
    }

    /**
     * @return array<string, mixed>
     */
    protected function obtainTokens(): array
    {
        $verifier = bin2hex(random_bytes(32));
        $challenge = rtrim(strtr(base64_encode(hash('sha256', $verifier, true)), '+/', '-_'), '=');

        $this->client->request('GET', '/authorize?'.http_build_query([
            'response_type' => 'code',
            'client_id' => OAuthClientFixtures::FRONT_CLIENT_ID,
            'redirect_uri' => $this->redirectUri(),
            'scope' => 'api',
            'state' => 'test-state',
            'code_challenge' => $challenge,
            'code_challenge_method' => 'S256',
        ]));

        $location = (string) $this->client->getResponse()->headers->get('Location');
        parse_str((string) parse_url($location, PHP_URL_QUERY), $query);
        $this->assertSame('test-state', $query['state'] ?? null, 'authorization failed: '.$location);

        $this->client->request('POST', '/token', [
            'grant_type' => 'authorization_code',
            'client_id' => OAuthClientFixtures::FRONT_CLIENT_ID,
            'redirect_uri' => $this->redirectUri(),
            'code' => $query['code'],
            'code_verifier' => $verifier,
        ]);
        $this->assertResponseIsSuccessful();

        return $this->responseData();
    }

    protected function redirectUri(): string
    {
        return $_ENV['FRONT_URL'].OAuthClientFixtures::CALLBACK_PATH;
    }

    /**
     * @param array<string, mixed>|null $payload
     *
     * @return array<string, mixed>
     */
    protected function requestJson(string $method, string $uri, ?array $payload = null): array
    {
        $this->client->request($method, $uri, server: ['HTTP_ACCEPT' => 'application/json', 'CONTENT_TYPE' => 'application/json'], content: null === $payload ? null : json_encode($payload, JSON_THROW_ON_ERROR));

        return $this->responseData();
    }

    /**
     * @return array<string, mixed>
     */
    protected function responseData(): array
    {
        $content = (string) $this->client->getResponse()->getContent();

        return '' === $content ? [] : json_decode($content, true, flags: JSON_THROW_ON_ERROR);
    }

    protected function grab(Crawler $crawler, string $name): string
    {
        return trim($crawler->filter(sprintf('#func-%s', $name))->eq(0)->text());
    }

    private function loadFixtures(): void
    {
        $entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $schemaTool = new SchemaTool($entityManager);
        $schemaTool->dropDatabase();
        $schemaTool->createSchema($entityManager->getMetadataFactory()->getAllMetadata());

        $loader = new Loader();
        $loader->addFixture(static::getContainer()->get(OAuthClientFixtures::class));
        $loader->addFixture(static::getContainer()->get(CredentialsFixtures::class));
        new ORMExecutor($entityManager, new ORMPurger($entityManager))->execute($loader->getFixtures());
    }
}
