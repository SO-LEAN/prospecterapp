<?php

namespace App\Tests;

use App\Bucket;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * The private files as the API and the browsers use them (ADR 0077): the API asks a form, the browser sends the file
 * to the bucket with it, the API keeps the upload, then asks links reading it for a moment. On the test bucket of
 * MiniStack, recreated before each test, the browser reaching it directly.
 */
class PrivateFilesTest extends WebTestCase
{
    private const string CONTENTS = "Cahier des charges\n";

    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->privateBucket()->recreate();
    }

    public function testTheBrowserSendsAFileWithTheFormOfAnUpload(): void
    {
        $upload = $this->prepareUpload();

        $this->assertSame(204, $this->send($upload, self::CONTENTS));
        $this->assertSame(['size' => strlen(self::CONTENTS), 'mediaType' => 'application/pdf'], $this->uploaded($upload['upload']));
        $this->assertSame(10 * 1024 * 1024, $upload['heaviest']);
    }

    public function testTheFormRefusesAFileOverTenMegabytes(): void
    {
        $this->assertSame(400, $this->send($this->prepareUpload(), str_repeat('x', 10 * 1024 * 1024 + 1)));
    }

    public function testOnlyTheServicesOfThePlatformAskForAnUpload(): void
    {
        $this->client->request('POST', '/uploads');

        $this->assertResponseStatusCodeSame(401);
    }

    public function testAnUploadNeverSentIsUnknown(): void
    {
        $this->client->request('GET', '/uploads/'.str_repeat('0', 32), server: $this->asTheApi());

        $this->assertResponseStatusCodeSame(404);
    }

    public function testAKeptFileIsReadByASignedLinkUnderItsName(): void
    {
        $file = $this->keptFile();

        $link = $this->downloadLinkOf($file, 'Cahier des charges été.pdf');

        [$status, $headers, $contents] = $this->get($link);
        $this->assertSame(200, $status);
        $this->assertSame(self::CONTENTS, $contents);
        $this->assertStringContainsString('attachment; filename="Cahier des charges _t_.pdf"; filename*=utf-8\'\'Cahier%20des%20charges%20%C3%A9t%C3%A9.pdf', $headers);
    }

    public function testAnUploadIsKeptOnce(): void
    {
        $file = $this->keptFile();

        $this->client->request('POST', '/private-files/'.$file, server: $this->asTheApi());

        $this->assertResponseStatusCodeSame(404);
    }

    public function testALinkIsWorthNothingOnceAltered(): void
    {
        $link = $this->downloadLinkOf($this->keptFile(), 'devis.pdf');

        $this->assertSame(403, $this->get((string) preg_replace('/X-Amz-Signature=\w+/', 'X-Amz-Signature='.str_repeat('0', 64), $link))[0]);
    }

    public function testARemovedFileIsNoLongerRead(): void
    {
        $file = $this->keptFile();
        $link = $this->downloadLinkOf($file, 'devis.pdf');

        $this->client->request('DELETE', '/private-files/'.$file, server: $this->asTheApi());

        $this->assertResponseStatusCodeSame(204);
        $this->assertSame(404, $this->get($link)[0]);
    }

    public function testOnlyTheServicesOfThePlatformRemoveAFile(): void
    {
        $this->client->request('DELETE', '/private-files/'.$this->keptFile());

        $this->assertResponseStatusCodeSame(401);
    }

    /**
     * @return array{upload: string, url: string, fields: array<string, string>, heaviest: int}
     */
    private function prepareUpload(): array
    {
        $this->client->request('POST', '/uploads', server: $this->asTheApi());
        $this->assertResponseStatusCodeSame(201);

        return json_decode((string) $this->client->getResponse()->getContent(), true);
    }

    /**
     * @param array{url: string, fields: array<string, string>} $upload
     */
    private function send(array $upload, string $contents): int
    {
        $curl = curl_init($upload['url']);
        curl_setopt_array($curl, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => [...$upload['fields'], 'Content-Type' => 'application/pdf', 'file' => new \CURLStringFile($contents, 'upload', 'application/pdf')],
            CURLOPT_RETURNTRANSFER => true,
        ]);
        curl_exec($curl);

        return curl_getinfo($curl, CURLINFO_HTTP_CODE);
    }

    /**
     * @return array{size: int, mediaType: string}
     */
    private function uploaded(string $upload): array
    {
        $this->client->request('GET', '/uploads/'.$upload, server: $this->asTheApi());
        $this->assertResponseIsSuccessful();

        return json_decode((string) $this->client->getResponse()->getContent(), true);
    }

    private function keptFile(): string
    {
        $upload = $this->prepareUpload();
        $this->send($upload, self::CONTENTS);
        $this->client->request('POST', '/private-files/'.$upload['upload'], server: $this->asTheApi());
        $this->assertResponseStatusCodeSame(204);

        return $upload['upload'];
    }

    private function downloadLinkOf(string $file, string $name): string
    {
        $this->client->request('GET', '/private-files/'.$file.'/download-link', ['name' => $name], server: $this->asTheApi());
        $this->assertResponseIsSuccessful();

        return json_decode((string) $this->client->getResponse()->getContent(), true)['url'];
    }

    /**
     * @return array{int, string, string} the status, the headers and the contents
     */
    private function get(string $url): array
    {
        $curl = curl_init($url);
        curl_setopt_array($curl, [CURLOPT_RETURNTRANSFER => true, CURLOPT_HEADER => true]);
        $response = (string) curl_exec($curl);
        $headerSize = curl_getinfo($curl, CURLINFO_HEADER_SIZE);

        return [curl_getinfo($curl, CURLINFO_HTTP_CODE), substr($response, 0, $headerSize), substr($response, $headerSize)];
    }

    /**
     * @return array<string, string>
     */
    private function asTheApi(): array
    {
        return ['HTTP_X_STORAGE_TOKEN' => 'test-storage-token'];
    }

    private function privateBucket(): Bucket
    {
        return self::getContainer()->get('app.private_bucket');
    }
}
