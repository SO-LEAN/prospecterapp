<?php

namespace Tests\App\Connectors;

use App\Connectors\Google\GoogleEndpoints;
use App\Connectors\Google\GoogleMailbox;
use App\Connectors\Google\GoogleTokens;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Prospecter\Core\Connectors\Domain\Exception\AccessRevoked;
use Prospecter\Core\Connectors\Domain\Exception\HistoryExpired;
use Prospecter\Core\Connectors\Domain\Model\ClientCredentials;
use Prospecter\Core\Connectors\Domain\Model\MailboxAttachment;
use Prospecter\Core\Connectors\Domain\Model\MailboxChanges;
use Prospecter\Core\Connectors\Domain\Model\MailboxMail;
use Prospecter\Core\Connectors\Domain\Model\MailboxWatch;
use Prospecter\Core\Connectors\Domain\Model\Provider;
use Prospecter\Core\Connectors\Domain\Model\RefreshToken;
use Prospecter\Core\SharedKernel\Domain\Email;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\JsonMockResponse;
use Symfony\Component\HttpClient\Response\MockResponse;
use Tests\App\Double\InMemoryLogger;
use Tests\App\Double\InMemoryPrivateStorage;

/**
 * The mailbox of a member at Gmail, watched through the topic of their account (ADR 0154): each call with an access
 * token refreshed through the client of the account.
 */
class GoogleMailboxTest extends TestCase
{
    private const string TOKEN_URL = 'https://oauth2.google.test/token';
    private const string GMAIL_URL = 'https://gmail.googleapis.test/gmail/v1';

    /** @var list<array{string, string, array<string, mixed>|null}> the calls to Gmail, with their body */
    private array $calls = [];
    private InMemoryLogger $logger;
    private InMemoryPrivateStorage $privateStorage;

    protected function setUp(): void
    {
        $this->logger = new InMemoryLogger();
        $this->privateStorage = new InMemoryPrivateStorage();
        InMemoryPrivateStorage::forgetEverything();
    }

    public function testTheMailboxIsWatchedWithoutItsSpamItsTrashNorItsDrafts(): void
    {
        $watch = $this->mailbox(new JsonMockResponse(['historyId' => '1000', 'expiration' => '1795600800000']))->watch(self::client(), new RefreshToken('a-token'), 'projects/solean/topics/gmail');

        $this->assertEquals(new MailboxWatch('1000', new \DateTimeImmutable('2026-11-25 10:00:00 UTC')), $watch);
        $this->assertSame([['POST', self::GMAIL_URL.'/users/me/watch', [
            'topicName' => 'projects/solean/topics/gmail',
            'labelIds' => ['SPAM', 'TRASH', 'DRAFT'],
            'labelFilterBehavior' => 'EXCLUDE',
        ]]], $this->calls);
    }

    #[DataProvider('accessRefused')]
    public function testAMailboxGmailNoLongerGrantsIsAnAccessRevoked(int $status): void
    {
        $this->expectException(AccessRevoked::class);

        $this->mailbox(new MockResponse('', ['http_code' => $status]))->watch(self::client(), new RefreshToken('a-token'), 'projects/solean/topics/gmail');
    }

    /**
     * @return iterable<string, array{int}>
     */
    public static function accessRefused(): iterable
    {
        yield 'the token refused' => [401];
        yield 'the reading of the mailbox no longer granted' => [403];
    }

    public function testAWatchIsStopped(): void
    {
        $this->mailbox(new MockResponse('', ['http_code' => 204]))->stop(self::client(), new RefreshToken('a-token'));

        $this->assertSame([['POST', self::GMAIL_URL.'/users/me/stop', null]], $this->calls);
    }

    #[DataProvider('stopsFailing')]
    public function testAStopFailingIsIgnored(MockResponse $answer): void
    {
        $this->mailbox($answer)->stop(self::client(), new RefreshToken('a-token'));

        $this->assertCount(1, $this->calls, 'The stop was expected to be asked, its failure ignored');
    }

    /**
     * @return iterable<string, array{MockResponse}>
     */
    public static function stopsFailing(): iterable
    {
        yield 'refused' => [new MockResponse('', ['http_code' => 401])];
        yield 'failing' => [new MockResponse('', ['http_code' => 503])];
        yield 'out of reach' => [new MockResponse('', ['error' => 'Gmail does not answer'])];
    }

    public function testTheMessagesAddedSinceAPointOfTheHistoryAreListedTheOldestFirstPageAfterPage(): void
    {
        $mailbox = $this->mailbox(
            new JsonMockResponse(['history' => [['id' => '1001', 'messagesAdded' => [['message' => ['id' => 'm1']]]], ['id' => '1002', 'messages' => [['id' => 'm0']]]], 'historyId' => '1003', 'nextPageToken' => 'p2']),
            new JsonMockResponse(['history' => [['id' => '1003', 'messagesAdded' => [['message' => ['id' => 'm2']], ['message' => ['id' => 'm3']]]]], 'historyId' => '1003']),
        );

        $changes = $mailbox->messagesAddedSince(self::client(), new RefreshToken('a-token'), '1000', 500);

        $this->assertEquals(new MailboxChanges(['m1', 'm2', 'm3'], '1003', true), $changes);
        $this->assertSame([
            ['GET', self::GMAIL_URL.'/users/me/history?startHistoryId=1000&historyTypes=messageAdded&maxResults=500', null],
            ['GET', self::GMAIL_URL.'/users/me/history?startHistoryId=1000&historyTypes=messageAdded&maxResults=500&pageToken=p2', null],
        ], $this->calls);
    }

    public function testAReadingStopsAtTheMostItMayListAtTheLastRecordListedWhole(): void
    {
        $mailbox = $this->mailbox(new JsonMockResponse(['history' => [
            ['id' => '1001', 'messagesAdded' => [['message' => ['id' => 'm1']]]],
            ['id' => '1002', 'messagesAdded' => [['message' => ['id' => 'm2']], ['message' => ['id' => 'm3']]]],
        ], 'historyId' => '1002']));

        $changes = $mailbox->messagesAddedSince(self::client(), new RefreshToken('a-token'), '1000', 2);

        $this->assertEquals(new MailboxChanges(['m1'], '1001', false), $changes);
    }

    public function testAReadingToItsEndKeepsTheLastRecordListedNotThePointTheMailboxReachedSince(): void
    {
        $mailbox = $this->mailbox(new JsonMockResponse(['history' => [['id' => '1001', 'messagesAdded' => [['message' => ['id' => 'm1']]]]], 'historyId' => '1005']));

        $changes = $mailbox->messagesAddedSince(self::client(), new RefreshToken('a-token'), '1000', 500);

        $this->assertEquals(new MailboxChanges(['m1'], '1001', true), $changes);
    }

    public function testAReadingOfTheHistoryIsLoggedWithItsPointsAndTheIdsOfTheMessagesItListed(): void
    {
        $mailbox = $this->mailbox(new JsonMockResponse(['history' => [['id' => '1001', 'messagesAdded' => [['message' => ['id' => 'm1']], ['message' => ['id' => 'm2']]]]], 'historyId' => '1005']));

        $mailbox->messagesAddedSince(self::client(), new RefreshToken('a-token'), '1000', 500);

        $this->assertSame([['level' => 'info', 'message' => 'The history of a mailbox read', 'context' => ['from' => '1000', 'to' => '1001', 'messages' => ['m1', 'm2'], 'complete' => true]]], $this->logger->records);
    }

    public function testAHistoryGmailNoLongerKeepsIsExpired(): void
    {
        $this->expectException(HistoryExpired::class);

        $this->mailbox(new MockResponse('', ['http_code' => 404]))->messagesAddedSince(self::client(), new RefreshToken('a-token'), '1000', 500);
    }

    public function testTheMessagesReceivedOrSentAfterAMomentAreTheNewestTheOldestFirstFromWhereTheHistoryStoodBefore(): void
    {
        $mailbox = $this->mailbox(
            new JsonMockResponse(['emailAddress' => 'michel@exemple.fr', 'historyId' => '2000']),
            new JsonMockResponse(['messages' => [['id' => 'm4'], ['id' => 'm3']], 'nextPageToken' => 'p2']),
            new JsonMockResponse(['messages' => [['id' => 'm2'], ['id' => 'm1']]]),
        );

        $changes = $mailbox->messagesExchangedAfter(self::client(), new RefreshToken('a-token'), new \DateTimeImmutable('2026-11-18 09:12:00 UTC'), 3);

        $this->assertEquals(new MailboxChanges(['m2', 'm3', 'm4'], '2000', true), $changes);
        $this->assertSame([
            ['GET', self::GMAIL_URL.'/users/me/profile', null],
            ['GET', self::GMAIL_URL.'/users/me/messages?q=after:1794993120%20-in:draft%20-in:spam%20-in:trash&maxResults=500', null],
            ['GET', self::GMAIL_URL.'/users/me/messages?q=after:1794993120%20-in:draft%20-in:spam%20-in:trash&maxResults=500&pageToken=p2', null],
        ], $this->calls);
    }

    public function testTheMessagesLeftOutOfAReadingByTheDateAreLogged(): void
    {
        $mailbox = $this->mailbox(
            new JsonMockResponse(['historyId' => '2000']),
            new JsonMockResponse(['messages' => [['id' => 'm3'], ['id' => 'm2'], ['id' => 'm1']]]),
        );

        $mailbox->messagesExchangedAfter(self::client(), new RefreshToken('a-token'), new \DateTimeImmutable('2026-11-18 09:12:00 UTC'), 1);

        $this->assertSame([['level' => 'warning', 'message' => 'The history of a mailbox expired: its oldest messages since its last reading are not imported', 'context' => ['leftOut' => 2]]], $this->logger->records);
    }

    public function testTheMessagesExchangedWithAnAddressAfterAMomentAreSearchedTheNewestFirstAtMostSoMany(): void
    {
        $mailbox = $this->mailbox(new JsonMockResponse(['messages' => [['id' => 'm3'], ['id' => 'm2']]]));

        $messageIds = $mailbox->messagesExchangedWith(self::client(), new RefreshToken('a-token'), new Email('ada@acme.fr'), new \DateTimeImmutable('2025-11-18 09:12:00 UTC'), 200);

        $this->assertSame(['m3', 'm2'], $messageIds);
        $this->assertSame([
            ['GET', self::GMAIL_URL.'/users/me/messages?q=%7Bfrom:ada@acme.fr%20to:ada@acme.fr%7D%20after:1763457120%20-in:draft%20-in:spam%20-in:trash&maxResults=200', null],
        ], $this->calls);
    }

    #[DataProvider('accessRefused')]
    public function testASearchGmailNoLongerGrantsIsAnAccessRevoked(int $status): void
    {
        $this->expectException(AccessRevoked::class);

        $this->mailbox(new MockResponse('', ['http_code' => $status]))->messagesExchangedWith(self::client(), new RefreshToken('a-token'), new Email('ada@acme.fr'), new \DateTimeImmutable('2025-11-18 09:12:00 UTC'), 200);
    }

    public function testAMessageIsReadWhole(): void
    {
        $mailbox = $this->mailbox(new JsonMockResponse(['id' => 'm1', 'labelIds' => ['INBOX'], 'internalDate' => '1794993120000', 'payload' => [
            'mimeType' => 'text/plain',
            'headers' => [['name' => 'From', 'value' => 'ada@acme.fr'], ['name' => 'Subject', 'value' => 'Votre devis']],
            'body' => ['data' => 'Qm9uam91cg'],
        ]]));

        $mail = $mailbox->message(self::client(), new RefreshToken('a-token'), 'm1');

        $this->assertEquals(new MailboxMail('m1', null, new Email('ada@acme.fr'), [], ['INBOX'], new \DateTimeImmutable('2026-11-18 09:12:00 UTC'), 'Votre devis', 'Bonjour'), $mail);
        $this->assertSame([['GET', self::GMAIL_URL.'/users/me/messages/m1?format=full', null]], $this->calls);
    }

    public function testAMessageReadIsLoggedWithItsIdAndItsLabelsOnly(): void
    {
        $mailbox = $this->mailbox(new JsonMockResponse(['id' => 'm1', 'labelIds' => ['INBOX', 'UNREAD'], 'internalDate' => '1794993120000', 'payload' => [
            'mimeType' => 'text/plain',
            'headers' => [['name' => 'From', 'value' => 'ada@acme.fr'], ['name' => 'Subject', 'value' => 'Votre devis']],
            'body' => ['data' => 'Qm9uam91cg'],
        ]]));

        $mailbox->message(self::client(), new RefreshToken('a-token'), 'm1');

        $this->assertSame([['level' => 'info', 'message' => 'A message of a mailbox read', 'context' => ['message' => 'm1', 'labels' => ['INBOX', 'UNREAD']]]], $this->logger->records);
    }

    public function testAMessageWithoutTheAddressOfItsSenderIsLoggedSetAside(): void
    {
        $mailbox = $this->mailbox(new JsonMockResponse(['id' => 'm1', 'labelIds' => ['INBOX'], 'internalDate' => '1794993120000', 'payload' => [
            'mimeType' => 'text/plain',
            'headers' => [['name' => 'Subject', 'value' => 'Votre devis']],
            'body' => ['data' => 'Qm9uam91cg'],
        ]]));

        $mailbox->message(self::client(), new RefreshToken('a-token'), 'm1');

        $this->assertSame([['level' => 'info', 'message' => 'A message of a mailbox set aside: no address of its sender', 'context' => ['message' => 'm1']]], $this->logger->records);
    }

    public function testAMessageDeletedMeanwhileIsNone(): void
    {
        $this->assertNull($this->mailbox(new MockResponse('', ['http_code' => 404]))->message(self::client(), new RefreshToken('a-token'), 'm1'));
    }

    public function testAMessageDeletedMeanwhileIsLoggedAsGone(): void
    {
        $this->mailbox(new MockResponse('', ['http_code' => 404]))->message(self::client(), new RefreshToken('a-token'), 'm1');

        $this->assertSame([['level' => 'info', 'message' => 'A message of a mailbox gone before its reading', 'context' => ['message' => 'm1']]], $this->logger->records);
    }

    public function testAnAttachmentIsReadOnceAndSentToThePrivateStorageForEachCorrespondent(): void
    {
        $mailbox = $this->mailbox(new JsonMockResponse(['size' => 11, 'data' => self::base64url('le devis...')]));

        $uploads = $mailbox->depositAttachment(self::client(), new RefreshToken('a-token'), 'm1', new MailboxAttachment('a1', 'devis.pdf', 11), 2);

        $this->assertSame([['GET', self::GMAIL_URL.'/users/me/messages/m1/attachments/a1', null]], $this->calls);
        $this->assertSame([11, 11], array_map(fn (string $upload): int => $this->privateStorage->uploaded($upload)->size, $uploads));
        $this->assertNotSame($uploads[0], $uploads[1]);
    }

    private static function base64url(string $contents): string
    {
        return rtrim(strtr(base64_encode($contents), '+/', '-_'), '=');
    }

    /**
     * Google answers the refresh, then each call to Gmail in turn.
     */
    private function mailbox(MockResponse ...$answers): GoogleMailbox
    {
        $google = new MockHttpClient(function (string $method, string $url, array $options) use (&$answers): MockResponse {
            if (self::TOKEN_URL === $url) {
                return new JsonMockResponse(['access_token' => 'an-access-token']);
            }

            $this->assertContains('Authorization: Bearer an-access-token', $options['headers']);
            $this->calls[] = [$method, $url, isset($options['body']) && '' !== $options['body'] ? json_decode($options['body'], true) : null];

            return array_shift($answers) ?? throw new \LogicException(sprintf('No answer left for %s %s', $method, $url));
        });
        $endpoints = self::endpoints();

        return new GoogleMailbox($google, $endpoints, new GoogleTokens($google, $endpoints), $this->privateStorage, $this->privateStorage, $this->logger);
    }

    public static function endpoints(): GoogleEndpoints
    {
        return new GoogleEndpoints('https://front.test/settings/connectors/google', 'https://accounts.google.test/auth', self::TOKEN_URL, 'https://oauth2.google.test/revoke', 'https://www.googleapis.test/calendar/v3', self::GMAIL_URL);
    }

    private static function client(): ClientCredentials
    {
        return new ClientCredentials(Provider::Google, 'an-id', 'a-secret');
    }
}
