<?php

declare(strict_types=1);

namespace Tests\App\Double;

use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Contracts\HttpClient\ResponseInterface;

/**
 * The files an AI gives by their address on the web, images or attachments (ADR 0168), as the tests see them, and the
 * names of their host, resolved without the network: on a public address, an address of a private network for a host a
 * test names so.
 */
final class TheWeb
{
    public const string HOST = 'https://www.babbage.test';
    public const string ON_A_PRIVATE_NETWORK = 'https://intranet.example';

    private const array ADDRESSES = ['www.babbage.test' => '93.184.216.34', 'intranet.example' => '10.0.0.8'];

    /** @var array<string, string> the content of each file, by its address */
    private array $files = [];

    /** @var array<string, string> the address each address redirects to */
    private array $redirects = [];

    /** @var array<string, int> the status the addresses served to those signed in only answer the others */
    private array $behindASignIn = [];

    public function publish(string $url, string $content): void
    {
        $this->files[$url] = $content;
    }

    public function redirect(string $url, string $to): void
    {
        $this->redirects[$url] = $to;
    }

    /**
     * The address answers whoever is not signed in with this status, as an e-mail of a mailbox (401) or a private
     * drive (403) does.
     */
    public function keepBehindASignIn(string $url, int $status = 401): void
    {
        $this->behindASignIn[$url] = $status;
    }

    public function serves(string $url): bool
    {
        return isset($this->files[$url]) || isset($this->redirects[$url]) || isset($this->behindASignIn[$url]);
    }

    public function answer(string $url): ResponseInterface
    {
        return match (true) {
            isset($this->behindASignIn[$url]) => new MockResponse('', ['http_code' => $this->behindASignIn[$url]]),
            isset($this->redirects[$url]) => new MockResponse('', ['http_code' => 302, 'redirect_url' => $this->redirects[$url], 'response_headers' => ['Location' => $this->redirects[$url]]]),
            default => new MockResponse($this->files[$url]),
        };
    }

    /**
     * The address a host name resolves to, as `DnsResolvingHttpClient` asks it.
     */
    public function __invoke(string $host): ?string
    {
        return self::ADDRESSES[$host] ?? null;
    }
}
