<?php

declare(strict_types=1);

namespace App\Connectors\Google;

use App\Shared\Typed;
use App\Shared\UploadForms;
use App\Shared\UploadSender;
use Monolog\Attribute\WithMonologChannel;
use Prospecter\Core\Connectors\Domain\Exception\AccessRevoked;
use Prospecter\Core\Connectors\Domain\Exception\HistoryExpired;
use Prospecter\Core\Connectors\Domain\Model\ClientCredentials;
use Prospecter\Core\Connectors\Domain\Model\MailboxAttachment;
use Prospecter\Core\Connectors\Domain\Model\MailboxChanges;
use Prospecter\Core\Connectors\Domain\Model\MailboxMail;
use Prospecter\Core\Connectors\Domain\Model\MailboxWatch;
use Prospecter\Core\Connectors\Domain\Model\RefreshToken;
use Prospecter\Core\Connectors\Domain\Port\ProviderMailbox;
use Prospecter\Core\SharedKernel\Domain\Contact\Email;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

/**
 * A member's Gmail mailbox, watched through their account's topic (ADR 0154).
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
    /** what is neither a received nor a sent e-mail, in Gmail's search syntax */
    private const string NOT_EXCHANGED = '-in:draft -in:spam -in:trash';
    private const int PAGE = 500;
    private const int NOT_DONE = 300;
    private const float TIMEOUT = 10;

    public function __construct(
        private HttpClientInterface $httpClient,
        private GoogleEndpoints $endpoints,
        private GoogleTokens $tokens,
        private UploadForms $uploadForms,
        private UploadSender $uploads,
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

        return new MailboxWatch(Typed::string($watch['historyId']), self::momentOf(Typed::string($watch['expiration'])));
    }

    /**
     * A refused token, or a token that no longer grants access to the mailbox, means that the access is revoked. Any
     * other failure is thrown as it is, so that it is tried again.
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
     * Gmail returns the watch's end in milliseconds since the epoch.
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
     * Reads the history page after page. It keeps the point of the last record that it listed whole. It never keeps the
     * point that the mailbox reached in the meantime. So a message whose record Gmail did not list yet is read next
     * time, not skipped. A reading that stops at its maximum leaves the later records to the next reading.
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
            foreach (Typed::documents($page['history'] ?? []) as $record) {
                $added = self::idsOf(array_column(Typed::documents($record['messagesAdded'] ?? []), 'message'));
                if ([] !== $listed && count($listed) + count($added) > $atMost) {
                    return $this->logged(new MailboxChanges($listed, $reached, false), $historyId);
                }

                $listed = [...$listed, ...$added];
                $reached = Typed::string($record['id']);
            }
            $pageToken = Typed::stringOrNull($page['nextPageToken'] ?? null);
        } while (null !== $pageToken);

        return $this->logged(new MailboxChanges($listed, $reached, true), $historyId);
    }

    /**
     * Logs what a reading listed, by its points and its messages' Gmail ids only. The log tells nothing that the
     * messages say.
     */
    private function logged(MailboxChanges $changes, string $from): MailboxChanges
    {
        $this->logger->info('Read a mailbox\'s history', ['from' => $from, 'to' => $changes->historyId, 'messages' => $changes->messageIds, 'complete' => $changes->complete]);

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

        return Typed::document($response->toArray());
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
     * Reads the history's current point first. So a message that arrives during the listing is read from the
     * history next time, rather than lost. Gmail lists the newest messages first. The older ones beyond the maximum
     * are left out, and the log says so.
     *
     * @see https://developers.google.com/workspace/gmail/api/reference/rest/v1/users.messages/list
     */
    public function messagesExchangedAfter(ClientCredentials $client, RefreshToken $refreshToken, \DateTimeImmutable $moment, int $atMost): MailboxChanges
    {
        $historyId = Typed::string($this->answerOf($client, $this->read($client, $refreshToken, '/users/me/profile'))['historyId']);
        $query = ['q' => sprintf('after:%d %s', $moment->getTimestamp(), self::NOT_EXCHANGED), 'maxResults' => self::PAGE];
        $newestFirst = [];
        $pageToken = null;
        do {
            $page = $this->answerOf($client, $this->read($client, $refreshToken, '/users/me/messages', [...$query, ...(null === $pageToken ? [] : ['pageToken' => $pageToken])]));
            $newestFirst = [...$newestFirst, ...self::idsOf($page['messages'] ?? [])];
            $pageToken = Typed::stringOrNull($page['nextPageToken'] ?? null);
        } while (null !== $pageToken);

        $this->warnOfTheLeftOut(count($newestFirst) - $atMost);

        return new MailboxChanges(array_reverse(array_slice($newestFirst, 0, $atMost)), $historyId, true);
    }

    /**
     * Gmail searches for the address as the sender or as a recipient (`{from: to:}`, either of them), copies included.
     * The import keeps only the sender and the recipients in « To ». The newest messages come first, on one page.
     *
     * @see https://support.google.com/mail/answer/7190
     */
    public function messagesExchangedWith(ClientCredentials $client, RefreshToken $refreshToken, Email $address, \DateTimeImmutable $moment, int $atMost): array
    {
        $query = ['q' => sprintf('{from:%1$s to:%1$s} after:%2$d %3$s', $address->value, $moment->getTimestamp(), self::NOT_EXCHANGED), 'maxResults' => $atMost];
        $page = $this->answerOf($client, $this->read($client, $refreshToken, '/users/me/messages', $query));

        return self::idsOf($page['messages'] ?? []);
    }

    /**
     * The listed messages' Gmail ids.
     *
     * @return list<string>
     */
    private static function idsOf(mixed $messages): array
    {
        return Typed::strings(array_column(Typed::documents($messages), 'id'));
    }

    private function warnOfTheLeftOut(int $leftOut): void
    {
        if ($leftOut > 0) {
            $this->logger->warning('A mailbox\'s history expired. Its oldest messages since its last reading are not imported', ['leftOut' => $leftOut]);
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

        return Typed::document($response->toArray());
    }

    /**
     * Reads the attachment once from Gmail. Then sends it as an upload form, as the API sends the AIs' files
     * (ADR 0172). It sends it once for each copy. The file goes through the function, never through the bus
     * (ADR 0182).
     *
     * @see https://developers.google.com/workspace/gmail/api/reference/rest/v1/users.messages.attachments/get
     */
    public function depositAttachment(ClientCredentials $client, RefreshToken $refreshToken, string $messageId, MailboxAttachment $attachment, int $copies): array
    {
        $path = sprintf('/users/me/messages/%s/attachments/%s', rawurlencode($messageId), rawurlencode($attachment->providerId));
        $file = $this->fileOf($this->answerOf($client, $this->read($client, $refreshToken, $path)));

        try {
            return array_map(fn (): string => $this->sent($file), range(1, $copies));
        } finally {
            unlink($file->getPathname());
        }
    }

    /**
     * @param array<string, mixed> $answer
     */
    private function fileOf(array $answer): File
    {
        $path = (string) tempnam(sys_get_temp_dir(), 'attachment');
        file_put_contents($path, GmailMessage::decoded(Typed::string($answer['data'] ?? '')));

        return new File($path);
    }

    private function sent(File $file): string
    {
        $form = $this->uploadForms->prepareUpload();
        $this->uploads->upload($form, $file);

        return $form->upload;
    }

    /**
     * @see https://developers.google.com/workspace/gmail/api/reference/rest/v1/users.messages/get
     */
    public function message(ClientCredentials $client, RefreshToken $refreshToken, string $messageId): ?MailboxMail
    {
        $response = $this->read($client, $refreshToken, '/users/me/messages/'.rawurlencode($messageId), ['format' => 'full']);
        if (self::NOT_FOUND === $response->getStatusCode()) {
            $this->logger->info('A mailbox message was gone before it was read', ['message' => $messageId]);

            return null;
        }

        $mail = GmailMessage::read($this->answerOf($client, $response));
        $this->logRead($messageId, $mail);

        return $mail;
    }

    /**
     * Logs the message by its id and its labels only. The log tells nothing that the message says.
     */
    private function logRead(string $messageId, ?MailboxMail $mail): void
    {
        if (null === $mail) {
            $this->logger->info('Set aside a mailbox message without a sender address', ['message' => $messageId]);

            return;
        }

        $this->logger->info('Read a mailbox message', ['message' => $messageId, 'labels' => $mail->labels]);
    }

    public function stop(ClientCredentials $client, RefreshToken $refreshToken): void
    {
        try {
            $this->warnUnlessDone($this->call($client, $refreshToken, '/users/me/stop')->getStatusCode());
        } catch (ExceptionInterface|AccessRevoked|\RuntimeException $failure) {
            $this->logger->warning('A Gmail watch could not be stopped. It expires within its week', ['exception' => $failure]);
        }
    }

    private function warnUnlessDone(int $status): void
    {
        if ($status >= self::NOT_DONE) {
            $this->logger->warning('Gmail refused to stop a watch. It expires within its week', ['status' => $status]);
        }
    }
}
