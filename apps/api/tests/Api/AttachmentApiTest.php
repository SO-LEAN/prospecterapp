<?php

namespace Tests\App\Api;

use PHPUnit\Framework\Attributes\Group;
use Prospecter\Core\Accounts\Domain\Role;
use Tests\App\Base\ApiTestCase;
use Tests\App\Double\InMemoryPrivateStorage;

/**
 * The files joined to an exchange (ADR 0077): the browser sends each with the form of an upload, then joins it; the
 * detail lists them, each read by a link signed for a moment.
 */
#[Group('api')]
class AttachmentApiTest extends ApiTestCase
{
    private const int MEGABYTE = 1024 * 1024;

    protected function setUp(): void
    {
        parent::setUp();
        InMemoryPrivateStorage::forgetEverything();
        $this->login();
    }

    public function testAProspectorGetsAFormToSendAFile(): void
    {
        $upload = $this->requestJson('POST', '/api/uploads');

        $this->assertResponseStatusCodeSame(201);
        $this->assertSame(['upload', 'url', 'fields', 'heaviest'], array_keys($upload));
        $this->assertSame(10 * self::MEGABYTE, $upload['heaviest']);
    }

    public function testAJoinedFileIsListedInTheDetailAndCountedInTheTimeline(): void
    {
        $contact = $this->aContact();
        $exchange = $this->anExchangeWith($contact);

        $detail = $this->attach($exchange, $this->sent(2048, 'application/pdf'), 'Cahier des charges.pdf');

        $this->assertResponseStatusCodeSame(201);
        $attachment = $detail['attachments'][0];
        $this->assertSame(['Cahier des charges.pdf', 2048, 'application/pdf'], [$attachment['name'], $attachment['size'], $attachment['mediaType']]);
        $this->assertSame(['href' => '/api/exchanges/'.$exchange.'/attachments/'.$attachment['id'], 'method' => 'GET'], $attachment['_links']['download']);
        $this->assertSame(['href' => '/api/exchanges/'.$exchange.'/attachments/'.$attachment['id'], 'method' => 'DELETE'], $attachment['_links']['removeAttachment']);
        $this->assertSame(1, $this->requestJson('GET', '/api/contacts/'.$contact.'/exchanges')['items'][0]['attachmentCount']);
    }

    public function testAJoinedFileIsReadByALinkSignedForAMoment(): void
    {
        $exchange = $this->anExchangeWith($this->aContact());
        $attachment = $this->attach($exchange, $this->sent(2048, 'application/pdf'), 'devis.pdf')['attachments'][0];

        $download = $this->requestJson('GET', $attachment['_links']['download']['href']);

        $this->assertResponseIsSuccessful();
        $this->assertSame('devis.pdf', $download['name']);
        $this->assertStringStartsWith('https://private.test/', $download['url']);
    }

    public function testAFileOverTenMegabytesIsRefused(): void
    {
        $problem = $this->attach($this->anExchangeWith($this->aContact()), $this->sent(10 * self::MEGABYTE + 1, 'video/mp4'), 'video.mp4');

        $this->assertResponseStatusCodeSame(422);
        $this->assertSame('attachmentTooHeavy', $problem['reason']);
    }

    public function testAnUploadNeverSentIsNotFound(): void
    {
        $problem = $this->attach($this->anExchangeWith($this->aContact()), bin2hex(random_bytes(16)), 'devis.pdf');

        $this->assertResponseStatusCodeSame(404);
        $this->assertSame('unknownUpload', $problem['reason']);
    }

    public function testARemovedFileIsForgotten(): void
    {
        $exchange = $this->anExchangeWith($this->aContact());
        $upload = $this->sent(2048, 'application/pdf');
        $attachment = $this->attach($exchange, $upload, 'devis.pdf')['attachments'][0];

        $detail = $this->requestJson('DELETE', $attachment['_links']['removeAttachment']['href']);

        $this->assertResponseIsSuccessful();
        $this->assertSame([], $detail['attachments']);
        $this->assertFalse($this->privateStorage()->holds($upload), 'removed from the storage');
        $this->requestJson('GET', $attachment['_links']['download']['href']);
        $this->assertResponseStatusCodeSame(404);
    }

    public function testAnErasedExchangeTakesItsFilesAlong(): void
    {
        $exchange = $this->anExchangeWith($this->aContact());
        $upload = $this->sent(2048, 'application/pdf');
        $this->attach($exchange, $upload, 'devis.pdf');

        $this->requestJson('DELETE', '/api/exchanges/'.$exchange);

        $this->assertFalse($this->privateStorage()->holds($upload), 'removed from the storage');
    }

    public function testWithoutTheRightNoFileIsJoinedNorRead(): void
    {
        $exchange = $this->anExchangeWith($this->aContact());
        $download = $this->attach($exchange, $this->sent(2048, 'application/pdf'), 'devis.pdf')['attachments'][0]['_links']['download']['href'];
        $this->login($this->aMemberOfTheFixtures('prospector-without-the-right', Role::User, Role::Prospector));

        $this->attach($exchange, $this->sent(2048, 'application/pdf'), 'devis.pdf');
        $this->assertResponseStatusCodeSame(403);
        $this->requestJson('GET', $download);
        $this->assertResponseStatusCodeSame(403);
    }

    /**
     * A file the browser sent with the form of an upload.
     */
    private function sent(int $size, string $mediaType): string
    {
        $upload = $this->requestJson('POST', '/api/uploads')['upload'];
        $this->privateStorage()->send($upload, $size, $mediaType);

        return $upload;
    }

    /**
     * @return array<string, mixed>
     */
    private function attach(string $exchange, string $upload, string $name): array
    {
        return $this->requestJson('POST', '/api/exchanges/'.$exchange.'/attachments', ['upload' => $upload, 'name' => $name]);
    }

    private function anExchangeWith(string $contact): string
    {
        return $this->requestJson('POST', '/api/contacts/'.$contact.'/exchanges', ['channel' => 'email', 'direction' => 'inbound'])['items'][0]['id'];
    }

    private function aContact(): string
    {
        $organization = $this->requestJson('POST', '/api/organizations', ['legalIdentity' => ['corporateName' => 'ACME']])['id'];

        return $this->requestJson('POST', '/api/contacts', ['name' => ['firstName' => 'Ada'], 'organizationIds' => [$organization]])['id'];
    }

    private function privateStorage(): InMemoryPrivateStorage
    {
        return self::getContainer()->get(InMemoryPrivateStorage::class);
    }
}
