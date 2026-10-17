<?php

namespace App\Shared;

use Aws\Credentials\Credentials;
use Aws\Signature\SignatureV4;
use GuzzleHttp\Psr7\Request;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * On AWS, the internal entry of the storage is a function URL answering only the requests signed by a role allowed
 * to call it (ADR 0068): the api signs each request with the credentials of its function.
 */
final readonly class SigV4StorageAuthentication implements StorageAuthentication
{
    private const string FUNCTION_URL_SERVICE = 'lambda';

    /**
     * @param array{key: string, secret: string, token: ?string} $credentials
     */
    public function __construct(
        #[Autowire(param: 'app.aws_credentials')]
        private array $credentials,
        #[Autowire(env: 'AWS_REGION')]
        private string $region,
    ) {
    }

    public function headersFor(string $method, string $url, array $headers, string $body): array
    {
        $signed = new SignatureV4(self::FUNCTION_URL_SERVICE, $this->region)->signRequest(new Request($method, $url, $headers, $body), $this->roleCredentials());

        return array_map(static fn (array $values): string => implode(', ', $values), $signed->getHeaders());
    }

    private function roleCredentials(): Credentials
    {
        return new Credentials($this->credentials['key'], $this->credentials['secret'], $this->credentials['token']);
    }
}
