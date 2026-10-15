<?php

namespace Tests\App\Api;

use PHPUnit\Framework\Attributes\Group;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Tests\App\Base\ApiTestCase;

/**
 * The sizes of an image are resized from the original by the static host on first request (the fixture
 * logo is 97 px wide).
 */
#[Group('api')]
class ImageSizesTest extends ApiTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->login();
    }

    public function testTheSmallSizeIsResizedFromTheOriginal(): void
    {
        $picture = $this->uploadImage('/api/me/picture')['picture'];

        $this->assertSame(96, $this->widthOf($picture['smallUrl']));
    }

    public function testAnImageIsNeverEnlarged(): void
    {
        $picture = $this->uploadImage('/api/me/picture')['picture'];

        $this->assertSame(97, $this->widthOf($picture['mediumUrl']));
    }

    private function widthOf(string $url): int
    {
        $this->client->request('GET', $url);
        $response = $this->client->getResponse();
        $this->assertInstanceOf(BinaryFileResponse::class, $response, (string) $response->getContent());

        return (int) getimagesize($response->getFile()->getPathname())[0];
    }
}
