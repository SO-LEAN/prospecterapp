<?php

namespace Tests\App\Base;

use App\DataFixtures\OAuthClientFixtures;
use Symfony\Component\HttpFoundation\File\UploadedFile;

abstract class ApiTestCase extends ControllerTestCase
{
    /**
     * Signs in like the SPA does: session login, authorization code with PKCE, then a bearer token on every request.
     */
    protected function login(string $reference = 'prospector-user'): void
    {
        parent::login($reference);

        $this->client->setServerParameter('HTTP_AUTHORIZATION', 'Bearer '.$this->obtainTokens()['access_token']);
    }

    /**
     * @return array{access_token: string, refresh_token: string, expires_in: int, token_type: string}
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
    protected function uploadImage(string $uri): array
    {
        $path = tempnam(sys_get_temp_dir(), 'upload');
        copy(__DIR__.'/../Fixtures/logo.png', $path);

        $this->client->request('POST', $uri, files: ['file' => new UploadedFile($path, 'logo.png', 'image/png', test: true)], server: ['HTTP_ACCEPT' => 'application/json']);

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

    /**
     * @param array<string, mixed> $problem
     *
     * @return list<string>
     */
    protected function violatedFields(array $problem): array
    {
        return array_map(static fn (array $violation): string => $violation['propertyPath'], $problem['violations'] ?? []);
    }
}
