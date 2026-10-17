<?php

namespace App\Tests;

use App\Bucket;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * The storage as the API and the browsers use it, on the test bucket of MiniStack recreated before each test.
 * The fixture logo is 97 px wide.
 */
class StorageTest extends WebTestCase
{
    private const string PUBLIC_URL = 'http://static.test.prospecter.io/storage/';

    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->bucket()->recreate();
    }

    public function testAServiceStoresAFileAndGetsItsPublicUrl(): void
    {
        $url = $this->store();

        $this->assertResponseStatusCodeSame(201);
        $this->assertMatchesRegularExpression('#^http://static\.test\.prospecter\.io/storage/\d{4}/\d{2}/([0-9a-f]{32})/\1\.png$#', $url);
        $this->assertSame('image/png', $this->bucket()->fileAt($this->keyOf($url))?->contentType);
    }

    public function testOnlyTheServicesOfThePlatformStoreFiles(): void
    {
        $this->client->request('POST', '/files', files: ['file' => $this->upload()]);

        $this->assertResponseStatusCodeSame(401);
    }

    public function testAnImageIsServedInASizeMadeOnFirstRequestAndCachedForever(): void
    {
        $url = $this->store();

        $this->assertSame(96, $this->widthOf('small', $url));
        $this->assertTrue($this->client->getResponse()->headers->hasCacheControlDirective('immutable'));
        $this->assertSame('31536000', $this->client->getResponse()->headers->getCacheControlDirective('max-age'));
        $this->assertResponseHeaderSame('Content-Type', 'image/png');
        $this->assertNotNull($this->bucket()->fileAt('small/'.$this->keyOf($url)), 'kept in the bucket to be served from there next time');
    }

    public function testAnImageIsNeverEnlarged(): void
    {
        $this->assertSame(97, $this->widthOf('medium', $this->store()));
    }

    public function testOnlyTheListedSizesExist(): void
    {
        $this->client->request('GET', '/storage/huge/'.$this->keyOf($this->store()));

        $this->assertResponseStatusCodeSame(404);
    }

    public function testAMissingImageHasNoSize(): void
    {
        $this->client->request('GET', '/storage/small/2026/10/missing/missing.png');

        $this->assertResponseStatusCodeSame(404);
    }

    public function testAServiceRemovesAFileAndItsSizes(): void
    {
        $url = $this->store();
        $this->widthOf('small', $url);

        $this->client->request('DELETE', '/files/'.$this->keyOf($url), server: $this->asTheApi());

        $this->assertResponseStatusCodeSame(204);
        $this->assertNull($this->bucket()->fileAt($this->keyOf($url)));
        $this->assertNull($this->bucket()->fileAt('small/'.$this->keyOf($url)));
    }

    private function store(): string
    {
        $this->client->request('POST', '/files', files: ['file' => $this->upload()], server: $this->asTheApi());

        return json_decode((string) $this->client->getResponse()->getContent(), true)['url'];
    }

    private function upload(): UploadedFile
    {
        $path = (string) tempnam(sys_get_temp_dir(), 'upload');
        copy(__DIR__.'/Fixtures/logo.png', $path);

        return new UploadedFile($path, 'logo.png', 'image/png', test: true);
    }

    /**
     * @return array<string, string>
     */
    private function asTheApi(): array
    {
        return ['HTTP_X_STORAGE_TOKEN' => 'test-storage-token'];
    }

    private function keyOf(string $url): string
    {
        return substr($url, strlen(self::PUBLIC_URL));
    }

    private function widthOf(string $size, string $url): int
    {
        $this->client->request('GET', '/storage/'.$size.'/'.$this->keyOf($url));
        $this->assertResponseIsSuccessful((string) $this->client->getResponse()->getContent());

        return (int) getimagesizefromstring((string) $this->client->getResponse()->getContent())[0];
    }

    private function bucket(): Bucket
    {
        return self::getContainer()->get(Bucket::class);
    }
}
