<?php

declare(strict_types=1);

namespace Tests\App\Api;

use App\Shared\CommandBus;
use Lcobucci\JWT\Encoding\ChainedFormatter;
use Lcobucci\JWT\Encoding\JoseEncoder;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Signer\Rsa\Sha256;
use Lcobucci\JWT\Token\Builder;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use Prospecter\Core\Accounts\Domain\Model\Role;
use Prospecter\Core\Connectors\Application\AskToImportMail\AskToImportMail;
use Prospecter\Core\Connectors\Application\ConfigureMailboxNotifications\ConfigureMailboxNotifications;
use Prospecter\Core\Connectors\Application\Connect\Connect;
use Tests\App\Base\ApiTestCase;
use Tests\App\Double\GoogleDouble;

/**
 * Pub/Sub pushes the notifications of Gmail to the endpoint of the account (ADR 0154), with a token Google signed for
 * the account of service of the subscription: the API trusts it without calling Google, but for its keys, which the
 * test draws and the double of Google publishes; the notification is noted and dated, nothing read from Gmail.
 */
#[Group('api')]
class GmailNotificationsApiTest extends ApiTestCase
{
    private const string PUSH_ACCOUNT = 'push@solean.iam.gserviceaccount.com';
    private const string MAILBOX = 'michel@exemple.fr';
    private string $privateKey = '';
    /** the id of the key drawn, new for each test: the keys of Google are cached by their id */
    private string $keyId = '';

    protected function setUp(): void
    {
        parent::setUp();
        $this->client->disableReboot();
        $this->aMemberOfTheFixtures('client-administrator', Role::User, Role::Admin);
        $key = openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => \OPENSSL_KEYTYPE_RSA]) ?: throw new \RuntimeException('No key drawn');
        openssl_pkey_export($key, $this->privateKey);
        $details = openssl_pkey_get_details($key) ?: throw new \RuntimeException('A key without its details');
        $this->keyId = self::newIdentity();
        $this->google()->publishesTheKey($this->keyId, self::base64Url($details['rsa']['n']), self::base64Url($details['rsa']['e']));
    }

    public function testANotificationOfAMailboxImportedIsNotedAndDated(): void
    {
        $this->aMemberImportingTheMailOf(self::MAILBOX);

        $this->notify($this->tokenOf(), self::MAILBOX, 1010);

        $this->assertResponseStatusCodeSame(204);
        $this->login();
        $this->assertSame('2026-10-15T09:00:00+00:00', $this->requestJson('GET', '/api/me/connections')['connections'][0]['lastGmailNotificationAt']);
    }

    public function testAnEMailOfAContactNotifiedIsImportedAsAnExchangeReceivedWithoutItsQuote(): void
    {
        $this->login();
        $acme = $this->requestJson('POST', '/api/organizations', ['legalIdentity' => ['corporateName' => 'ACME']])['id'];
        $ada = $this->requestJson('POST', '/api/contacts', ['name' => ['firstName' => 'Ada', 'lastName' => 'Lovelace'], 'contactDetails' => ['email' => 'ada@acme.test'], 'organizationIds' => [$acme]])['id'];
        $this->assertResponseStatusCodeSame(201, 'The contact was expected to be collected');
        $this->aMemberImportingTheMailOf(self::MAILBOX);
        $this->google()->receives('m1', 'Ada Lovelace <Ada@ACME.test>', 'Votre devis', "Bonjour Michel, c'est validé.\n\nLe mer. 14 oct. 2026 à 18:02, Michel <michel@exemple.fr> a écrit :\n> Voici le devis.\n");

        $this->notify($this->tokenOf(), self::MAILBOX, 1010);

        $this->assertResponseStatusCodeSame(204);
        // signed in once: the client never rebooted checks the tokens at the moment of its first request, a token
        // issued since in its future
        $timeline = $this->requestJson('GET', '/api/contacts/'.$ada.'/exchanges');
        $this->assertResponseIsSuccessful();
        $this->assertSame([['email', 'inbound', "Votre devis\n\nBonjour Michel, c'est validé."]], array_map(static fn (array $exchange): array => [$exchange['channel'], $exchange['direction'], $exchange['text']], $timeline['items']));
    }

    public function testAnEMailImportedOpensInGmailByTheIdOfItsMessageInTheMailbox(): void
    {
        $this->login();
        $acme = $this->requestJson('POST', '/api/organizations', ['legalIdentity' => ['corporateName' => 'ACME']])['id'];
        $ada = $this->requestJson('POST', '/api/contacts', ['name' => ['firstName' => 'Ada', 'lastName' => 'Lovelace'], 'contactDetails' => ['email' => 'ada@acme.test'], 'organizationIds' => [$acme]])['id'];
        $this->aMemberImportingTheMailOf(self::MAILBOX);
        $this->google()->receives('m1', 'ada@acme.test', 'Votre devis', 'Bonjour Michel');

        $this->notify($this->tokenOf(), self::MAILBOX, 1010);

        $imported = $this->requestJson('GET', '/api/contacts/'.$ada.'/exchanges')['items'][0]['id'];
        $this->assertSame('https://mail.google.com/mail/#all/m1', $this->requestJson('GET', '/api/exchanges/'.$imported)['_links']['openInMailbox']['href'] ?? null);
    }

    public function testANotificationOfAMailboxNobodyImportsIsAnsweredAndIgnored(): void
    {
        $this->aMemberImportingTheMailOf(self::MAILBOX);

        $this->notify($this->tokenOf(), 'inconnu@exemple.fr', 1010);

        $this->assertResponseStatusCodeSame(204);
        $this->login();
        $this->assertNull($this->requestJson('GET', '/api/me/connections')['connections'][0]['lastGmailNotificationAt']);
    }

    /**
     * @param array<string, mixed> $claims
     */
    #[DataProvider('tokensRefused')]
    public function testANotificationWhoseTokenIsNotThatOfTheSubscriptionIsRefused(array $claims, bool $signedByGoogle = true): void
    {
        $this->aMemberImportingTheMailOf(self::MAILBOX);

        $this->notify($this->tokenOf($claims, $signedByGoogle), self::MAILBOX, 1010);

        $this->assertResponseStatusCodeSame(401);
        $this->login();
        $this->assertNull($this->requestJson('GET', '/api/me/connections')['connections'][0]['lastGmailNotificationAt']);
    }

    /**
     * @return iterable<string, array{0: array<string, mixed>, 1?: bool}>
     */
    public static function tokensRefused(): iterable
    {
        yield 'another account of service' => [['email' => 'another@solean.iam.gserviceaccount.com']];
        yield 'an address not verified' => [['email_verified' => false]];
        yield 'another issuer' => [['iss' => 'https://accounts.example.com']];
        yield 'another audience' => [['aud' => 'https://api.example.com/api/accounts/another/gmail-notifications']];
        yield 'expired' => [['exp' => '2026-10-15 08:59 UTC']];
        yield 'signed by another key' => [[], false];
    }

    public function testAMessageThatIsNoNotificationOfGmailIsABadRequest(): void
    {
        $this->aMemberImportingTheMailOf(self::MAILBOX);

        $this->client->request('POST', $this->endpointPath(), server: ['CONTENT_TYPE' => 'application/json', 'HTTP_AUTHORIZATION' => 'Bearer '.$this->tokenOf()], content: json_encode(['message' => ['data' => base64_encode('no notification')]], \JSON_THROW_ON_ERROR));

        $this->assertResponseStatusCodeSame(400);
    }

    public function testANotificationWithoutTokenIsRefused(): void
    {
        $this->aMemberImportingTheMailOf(self::MAILBOX);

        $this->notify(null, self::MAILBOX, 1010);

        $this->assertResponseStatusCodeSame(401);
    }

    public function testANotificationToAnAccountWithoutTopicIsRefused(): void
    {
        $this->notify($this->tokenOf(), self::MAILBOX, 1010);

        $this->assertResponseStatusCodeSame(401);
    }

    private function aMemberImportingTheMailOf(string $mailbox): void
    {
        $at = new \DateTimeImmutable(self::THE_MOMENT_OF_THE_TESTS);
        $commands = self::getContainer()->get(CommandBus::class);
        $commands->dispatch(new ConfigureMailboxNotifications($this->memberIdOf('client-administrator'), 'google', 'projects/solean/topics/gmail', self::PUSH_ACCOUNT, $at));
        $member = $this->memberIdOf('prospector-user');
        $commands->dispatch(new AskToImportMail($member, 'google', 'a-state', 'a-verifier', $at));
        $this->google()->grantsGmail('a-code', $mailbox, 'a-refresh-token');
        $commands->dispatch(new Connect($member, 'a-state', 'a-code', $at));
    }

    private function notify(?string $token, string $mailbox, int $historyId): void
    {
        $data = base64_encode(json_encode(['emailAddress' => $mailbox, 'historyId' => $historyId], \JSON_THROW_ON_ERROR));
        $this->client->request('POST', $this->endpointPath(), server: [
            'CONTENT_TYPE' => 'application/json',
            ...(null === $token ? [] : ['HTTP_AUTHORIZATION' => 'Bearer '.$token]),
        ], content: json_encode(['message' => ['data' => $data, 'messageId' => '1', 'publishTime' => '2026-10-15T09:00:00Z'], 'subscription' => 'projects/solean/subscriptions/gmail'], \JSON_THROW_ON_ERROR));
    }

    private function endpointPath(): string
    {
        return sprintf('/api/accounts/%s/gmail-notifications', $this->accountOfTheFixtures()->value);
    }

    /**
     * A token as Pub/Sub sends it, signed by the key Google publishes unless said otherwise, valid an hour from the
     * moment of the tests.
     *
     * @param array<string, mixed> $claims
     */
    private function tokenOf(array $claims = [], bool $signedByGoogle = true): string
    {
        $claims = [
            'iss' => 'https://accounts.google.com',
            'aud' => $_SERVER['DEFAULT_URI'].$this->endpointPath(),
            'email' => self::PUSH_ACCOUNT,
            'email_verified' => true,
            'exp' => '2026-10-15 10:00 UTC',
            ...$claims,
        ];
        $builder = Builder::new(new JoseEncoder(), ChainedFormatter::default())
            ->withHeader('kid', $this->keyId)
            ->issuedBy($claims['iss'])
            ->permittedFor($claims['aud'])
            ->issuedAt(new \DateTimeImmutable(self::THE_MOMENT_OF_THE_TESTS))
            ->expiresAt(new \DateTimeImmutable($claims['exp']))
            ->withClaim('email', $claims['email'])
            ->withClaim('email_verified', $claims['email_verified']);

        return $builder->getToken(new Sha256(), InMemory::plainText($signedByGoogle ? $this->privateKey : self::anotherKey()))->toString();
    }

    private static function anotherKey(): string
    {
        $key = openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => \OPENSSL_KEYTYPE_RSA]) ?: throw new \RuntimeException('No key drawn');
        openssl_pkey_export($key, $pem);

        return $pem;
    }

    private static function base64Url(string $bytes): string
    {
        return rtrim(strtr(base64_encode($bytes), '+/', '-_'), '=');
    }

    private function google(): GoogleDouble
    {
        return self::getContainer()->get(GoogleDouble::class);
    }
}
