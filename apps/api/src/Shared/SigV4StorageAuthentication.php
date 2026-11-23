<?php

declare(strict_types=1);

namespace App\Shared;

use Aws\Credentials\Credentials;
use Aws\Signature\SignatureV4;
use GuzzleHttp\Psr7\Request;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * On AWS, the storage's internal entry is a function URL. It answers only the requests signed by a role allowed to call
 * it (ADR 0068). The API signs each request with its function's credentials.
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

        return Typed::namedStrings(array_map(static fn (array $values): string => implode(', ', $values), $signed->getHeaders()));
    }

    private function roleCredentials(): Credentials
    {
        return new Credentials($this->credentials['key'], $this->credentials['secret'], $this->credentials['token']);
    }
}
