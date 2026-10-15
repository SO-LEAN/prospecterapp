<?php

namespace App\Tests;

use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * The storage as the API and the browsers use it. The fixture logo is 97 px wide.
 */
class StorageTest extends WebTestCase
{
    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();
    }

    protected function tearDown(): void
    {
        new Filesystem()->remove(self::getContainer()->getParameter('storage.directory'));
        parent::tearDown();
    }

    public function testAServiceStoresAFileAndGetsItsPublicUrl(): void
    {
        $url = $this->store();

        $this->assertResponseStatusCodeSame(201);
        $this->assertMatchesRegularExpression('#^http://static\.test\.prospecter\.io/storage/\d{4}/\d{2}/([0-9a-f]{32})/\1\.png$#', $url);
        $this->assertFileExists($this->pathOf($url));
    }

    public function testOnlyTheServicesOfThePlatformStoreFiles(): void
    {
        $this->client->request('POST', '/files', files: ['file' => $this->upload()]);

        $this->assertResponseStatusCodeSame(401);
    }

    public function testAnImageIsServedInASizeMadeOnFirstRequest(): void
    {
        $url = $this->store();

        $this->assertSame(96, $this->widthOf($this->inSize('small', $url)));
        $this->assertFileExists($this->pathOf($this->inSize('small', $url)), 'kept for nginx to serve it next time');
    }

    public function testAnImageIsNeverEnlarged(): void
    {
        $this->assertSame(97, $this->widthOf($this->inSize('medium', $this->store())));
    }

    public function testOnlyTheListedSizesExist(): void
    {
        $this->client->request('GET', $this->inSize('huge', $this->store()));

        $this->assertResponseStatusCodeSame(404);
    }

    public function testAServiceRemovesAFileAndItsSizes(): void
    {
        $url = $this->store();
        $this->widthOf($this->inSize('small', $url));

        $this->client->request('DELETE', '/files/'.$this->relativePathOf($url), server: $this->asTheApi());

        $this->assertResponseStatusCodeSame(204);
        $this->assertFileDoesNotExist($this->pathOf($url));
        $this->assertFileDoesNotExist($this->pathOf($this->inSize('small', $url)));
    }

    public function testAPathCannotLeaveTheStorage(): void
    {
        $this->client->request('GET', '/storage/small/../../.env');

        $this->assertResponseStatusCodeSame(404);
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
        return ['HTTP_AUTHORIZATION' => 'Bearer test-storage-token'];
    }

    private function inSize(string $size, string $url): string
    {
        return '/storage/'.$size.'/'.$this->relativePathOf($url);
    }

    private function relativePathOf(string $url): string
    {
        return substr($url, strlen('http://static.test.prospecter.io/storage/'));
    }

    private function pathOf(string $url): string
    {
        return self::getContainer()->getParameter('storage.directory').'/'.ltrim(str_replace(['http://static.test.prospecter.io/storage', '/storage'], '', $url), '/');
    }

    private function widthOf(string $path): int
    {
        $this->client->request('GET', $path);
        $response = $this->client->getResponse();
        $this->assertInstanceOf(BinaryFileResponse::class, $response, (string) $response->getContent());

        return (int) getimagesize($response->getFile()->getPathname())[0];
    }
}
