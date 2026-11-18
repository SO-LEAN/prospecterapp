<?php

namespace App\Connectors\Google;

use Prospecter\Core\Connectors\Domain\Exception\AccessRevoked;
use Prospecter\Core\Connectors\Domain\Model\ClientCredentials;
use Prospecter\Core\Connectors\Domain\Model\MailboxWatch;
use Prospecter\Core\Connectors\Domain\Model\RefreshToken;
use Prospecter\Core\Connectors\Domain\Port\ProviderMailbox;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

/**
 * The mailbox of a member at Gmail, watched through the topic of their account (ADR 0154).
 *
 * @see https://developers.google.com/workspace/gmail/api/guides/push
 */
final readonly class GoogleMailbox implements ProviderMailbox
{
    /** what is never imported: the spam, the trash and the drafts (ADR 0154) */
    private const array LEFT_OUT = ['SPAM', 'TRASH', 'DRAFT'];
    private const int MILLISECONDS = 1000;
    private const array NOT_GRANTED = [401, 403];
    private const int NOT_DONE = 300;
    private const float TIMEOUT = 10;

    public function __construct(
        private HttpClientInterface $httpClient,
        private GoogleEndpoints $endpoints,
        private GoogleTokens $tokens,
        private LoggerInterface $logger,
    ) {
    }

    public function watch(ClientCredentials $client, RefreshToken $refreshToken, string $topic): MailboxWatch
    {
        $response = $this->call($client, $refreshToken, '/users/me/watch', [
            'topicName' => $topic,
            'labelIds' => self::LEFT_OUT,
            'labelFilterBehavior' => 'EXCLUDE',
        ]);
        $this->ensureGranted($response, $client);
        $watch = $response->toArray();

        return new MailboxWatch((string) $watch['historyId'], self::momentOf((string) $watch['expiration']));
    }

    /**
     * A token refused, or a token that no longer grants the reading of the mailbox, is the access revoked; any other
     * failure is thrown as it is, to be tried again.
     *
     * @throws AccessRevoked
     */
    private function ensureGranted(ResponseInterface $response, ClientCredentials $client): void
    {
        if (in_array($response->getStatusCode(), self::NOT_GRANTED, true)) {
            throw AccessRevoked::by($client->provider);
        }
    }

    /**
     * Gmail answers the end of the watch in milliseconds since the epoch.
     */
    private static function momentOf(string $milliseconds): \DateTimeImmutable
    {
        return new \DateTimeImmutable('@'.intdiv((int) $milliseconds, self::MILLISECONDS));
    }

    /**
     * @param array<string, mixed>|null $body
     */
    private function call(ClientCredentials $client, RefreshToken $refreshToken, string $path, ?array $body = null): ResponseInterface
    {
        return $this->httpClient->request('POST', $this->endpoints->gmailUrl.$path, [
            'headers' => ['Authorization' => 'Bearer '.$this->tokens->accessTokenFor($client, $refreshToken)],
            'timeout' => self::TIMEOUT,
            ...(null === $body ? [] : ['json' => $body]),
        ]);
    }

    public function stop(ClientCredentials $client, RefreshToken $refreshToken): void
    {
        try {
            $this->warnUnlessDone($this->call($client, $refreshToken, '/users/me/stop')->getStatusCode());
        } catch (ExceptionInterface|AccessRevoked|\RuntimeException $failure) {
            $this->logger->warning('A watch of Gmail could not be stopped: it expires within its week', ['exception' => $failure]);
        }
    }

    private function warnUnlessDone(int $status): void
    {
        if ($status >= self::NOT_DONE) {
            $this->logger->warning('Gmail refused to stop a watch: it expires within its week', ['status' => $status]);
        }
    }
}
