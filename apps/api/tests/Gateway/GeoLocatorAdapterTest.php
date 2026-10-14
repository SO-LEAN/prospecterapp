<?php

namespace Tests\App\Gateway;

use App\Gateway\GeoLocatorAdapter;
use App\Service\GeoLocator;
use App\Service\GeoLocator\GeoPointResponse;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

#[Group('unit')]
class GeoLocatorAdapterTest extends TestCase
{
    public function testAFailedLookupIsReportedAsFailed(): void
    {
        $locator = $this->createStub(GeoLocator::class);
        $locator->method('find')->willReturn(new GeoPointResponse('nowhere', 0, 0, false));

        $response = new GeoLocatorAdapter($locator)->find('nowhere');

        $this->assertFalse($response->isSucceeded(), 'a failed lookup must not place the organization at 0,0');
    }

    public function testASuccessfulLookupKeepsTheCoordinates(): void
    {
        $locator = $this->createStub(GeoLocator::class);
        $locator->method('find')->willReturn(new GeoPointResponse('Strasbourg', 7.75, 48.58));

        $response = new GeoLocatorAdapter($locator)->find('Strasbourg');

        $this->assertTrue($response->isSucceeded());
        $this->assertSame([7.75, 48.58], [$response->getLongitude(), $response->getLatitude()]);
    }
}
