<?php

namespace App\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use League\Bundle\OAuth2ServerBundle\Manager\ClientManagerInterface;
use League\Bundle\OAuth2ServerBundle\Model\Client;
use League\Bundle\OAuth2ServerBundle\ValueObject\Grant;
use League\Bundle\OAuth2ServerBundle\ValueObject\RedirectUri;
use League\Bundle\OAuth2ServerBundle\ValueObject\Scope;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * The SPA is a public client (no secret): it must use PKCE.
 */
final class OAuthClientFixtures extends Fixture
{
    public const string FRONT_CLIENT_ID = 'prospecter-front';
    public const string CALLBACK_PATH = '/auth/callback';

    public function __construct(
        private readonly ClientManagerInterface $clientManager,
        #[Autowire(env: 'FRONT_URL')]
        private readonly string $frontUrl,
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        $client = new Client('Prospecter front', self::FRONT_CLIENT_ID, null);
        $client
            ->setRedirectUris(new RedirectUri($this->frontUrl.self::CALLBACK_PATH))
            ->setGrants(new Grant('authorization_code'), new Grant('refresh_token'))
            ->setScopes(new Scope('api'))
            ->setActive(true);

        $this->clientManager->save($client);
    }
}
