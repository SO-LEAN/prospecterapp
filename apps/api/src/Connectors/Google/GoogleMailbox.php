<?php

namespace App\Connectors\Google;

use Monolog\Attribute\WithMonologChannel;
use Prospecter\Core\Connectors\Domain\Exception\AccessRevoked;
use Prospecter\Core\Connectors\Domain\Exception\HistoryExpired;
use Prospecter\Core\Connectors\Domain\Model\ClientCredentials;
use Prospecter\Core\Connectors\Domain\Model\MailboxChanges;
use Prospecter\Core\Connectors\Domain\Model\MailboxMail;
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
#[WithMonologChannel('mailbox')]
final readonly class GoogleMailbox implements ProviderMailbox
{
    /** what is never imported: the spam, the trash and the drafts (ADR 0154) */
    private const array LEFT_OUT = ['SPAM', 'TRASH', 'DRAFT'];
    private const int MILLISECONDS = 1000;
    private const array NOT_GRANTED = [401, 403];
    private const int NOT_FOUND = 404;
    /** what is no e-mail received, in the words of the search of Gmail */
    private const string NOT_RECEIVED = '-in:sent -in:draft -in:spam -in:trash';
    private const int PAGE = 500;
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

    /**
     * The history read page after page, the point kept that of the last record it listed whole, never the point the
     * mailbox reached meanwhile: a message whose record Gmail did not list yet is read next time, not skipped; a
     * reading stopping at the most it may list leaves the records after it to the next one.
     *
     * @see https://developers.google.com/workspace/gmail/api/reference/rest/v1/users.history/list
     */
    public function messagesAddedSince(ClientCredentials $client, RefreshToken $refreshToken, string $historyId, int $atMost): MailboxChanges
    {
        $listed = [];
        $reached = $historyId;
        $pageToken = null;
        do {
            $page = $this->historyPage($client, $refreshToken, $historyId, $atMost, $pageToken);
            foreach ($page['history'] ?? [] as $record) {
                $added = array_map(static fn (array $added): string => (string) $added['message']['id'], $record['messagesAdded'] ?? []);
                if ([] !== $listed && count($listed) + count($added) > $atMost) {
                    return $this->logged(new MailboxChanges($listed, $reached, false), $historyId);
                }

                $listed = [...$listed, ...$added];
                $reached = (string) $record['id'];
            }
            $pageToken = $page['nextPageToken'] ?? null;
        } while (null !== $pageToken);

        return $this->logged(new MailboxChanges($listed, $reached, true), $historyId);
    }

    /**
     * What a reading listed, said by its points and the ids of its messages at Gmail only: nothing they say is told.
     */
    private function logged(MailboxChanges $changes, string $from): MailboxChanges
    {
        $this->logger->info('The history of a mailbox read', ['from' => $from, 'to' => $changes->historyId, 'messages' => $changes->messageIds, 'complete' => $changes->complete]);

        return $changes;
    }

    /**
     * @return array<string, mixed>
     *
     * @throws AccessRevoked|HistoryExpired
     */
    private function historyPage(ClientCredentials $client, RefreshToken $refreshToken, string $historyId, int $atMost, ?string $pageToken): array
    {
        $response = $this->read($client, $refreshToken, '/users/me/history', ['startHistoryId' => $historyId, 'historyTypes' => 'messageAdded', 'maxResults' => $atMost, ...(null === $pageToken ? [] : ['pageToken' => $pageToken])]);
        if (self::NOT_FOUND === $response->getStatusCode()) {
            throw HistoryExpired::from($historyId);
        }

        $this->ensureGranted($response, $client);

        return $response->toArray();
    }

    /**
     * @param array<string, int|string> $query
     */
    private function read(ClientCredentials $client, RefreshToken $refreshToken, string $path, array $query = []): ResponseInterface
    {
        return $this->httpClient->request('GET', $this->endpoints->gmailUrl.$path, [
            'headers' => ['Authorization' => 'Bearer '.$this->tokens->accessTokenFor($client, $refreshToken)],
            'timeout' => self::TIMEOUT,
            'query' => $query,
        ]);
    }

    /**
     * Where the history stands is read first, so that a message arriving while they are listed is read again from the
     * history next time rather than lost. Gmail lists the newest first: the older ones beyond the most are left out,
     * and said.
     *
     * @see https://developers.google.com/workspace/gmail/api/reference/rest/v1/users.messages/list
     */
    public function messagesReceivedAfter(ClientCredentials $client, RefreshToken $refreshToken, \DateTimeImmutable $moment, int $atMost): MailboxChanges
    {
        $historyId = (string) $this->answerOf($client, $this->read($client, $refreshToken, '/users/me/profile'))['historyId'];
        $query = ['q' => sprintf('after:%d %s', $moment->getTimestamp(), self::NOT_RECEIVED), 'maxResults' => self::PAGE];
        $newestFirst = [];
        $pageToken = null;
        do {
            $page = $this->answerOf($client, $this->read($client, $refreshToken, '/users/me/messages', [...$query, ...(null === $pageToken ? [] : ['pageToken' => $pageToken])]));
            $newestFirst = [...$newestFirst, ...array_map(static fn (array $message): string => (string) $message['id'], $page['messages'] ?? [])];
            $pageToken = $page['nextPageToken'] ?? null;
        } while (null !== $pageToken);

        $this->warnOfTheLeftOut(count($newestFirst) - $atMost);

        return new MailboxChanges(array_reverse(array_slice($newestFirst, 0, $atMost)), $historyId, true);
    }

    private function warnOfTheLeftOut(int $leftOut): void
    {
        if ($leftOut > 0) {
            $this->logger->warning('The history of a mailbox expired: its oldest messages since its last reading are not imported', ['leftOut' => $leftOut]);
        }
    }

    /**
     * @return array<string, mixed>
     *
     * @throws AccessRevoked
     */
    private function answerOf(ClientCredentials $client, ResponseInterface $response): array
    {
        $this->ensureGranted($response, $client);

        return $response->toArray();
    }

    /**
     * @see https://developers.google.com/workspace/gmail/api/reference/rest/v1/users.messages/get
     */
    public function message(ClientCredentials $client, RefreshToken $refreshToken, string $messageId): ?MailboxMail
    {
        $response = $this->read($client, $refreshToken, '/users/me/messages/'.rawurlencode($messageId), ['format' => 'full']);
        if (self::NOT_FOUND === $response->getStatusCode()) {
            $this->logger->info('A message of a mailbox gone before its reading', ['message' => $messageId]);

            return null;
        }

        $mail = GmailMessage::read($this->answerOf($client, $response));
        $this->logRead($messageId, $mail);

        return $mail;
    }

    /**
     * By its id and its labels only: nothing it says is told.
     */
    private function logRead(string $messageId, ?MailboxMail $mail): void
    {
        if (null === $mail) {
            $this->logger->info('A message of a mailbox set aside: no address of its sender', ['message' => $messageId]);

            return;
        }

        $this->logger->info('A message of a mailbox read', ['message' => $messageId, 'labels' => $mail->labels]);
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
