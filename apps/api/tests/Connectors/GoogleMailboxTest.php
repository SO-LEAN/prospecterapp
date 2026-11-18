<?php

namespace Tests\App\Connectors;

use App\Connectors\Google\GoogleEndpoints;
use App\Connectors\Google\GoogleMailbox;
use App\Connectors\Google\GoogleTokens;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Prospecter\Core\Connectors\Domain\Exception\AccessRevoked;
use Prospecter\Core\Connectors\Domain\Model\ClientCredentials;
use Prospecter\Core\Connectors\Domain\Model\MailboxWatch;
use Prospecter\Core\Connectors\Domain\Model\Provider;
use Prospecter\Core\Connectors\Domain\Model\RefreshToken;
use Psr\Log\NullLogger;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\JsonMockResponse;
use Symfony\Component\HttpClient\Response\MockResponse;

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

        return new GoogleMailbox($google, $endpoints, new GoogleTokens($google, $endpoints), new NullLogger());
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
