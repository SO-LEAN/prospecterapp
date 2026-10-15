<?php

namespace Tests\App\Gateway;

use App\Gateway\GeoplateformeGeoLocation;
use PHPUnit\Framework\TestCase;
use Prospecter\Core\Domain\Organization\GeoPoint;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\JsonMockResponse;
use Symfony\Component\HttpClient\Response\MockResponse;

class GeoplateformeGeoLocationTest extends TestCase
{
    private const string URL = 'https://geocoder.test/search';

    public function testFindsTheBestMatch(): void
    {
        $client = new MockHttpClient(function (string $method, string $url): JsonMockResponse {
            $this->assertSame('GET', $method);
            $this->assertSame(self::URL.'?q=1%20rue%20de%20la%20Paix%2075002%20Paris&limit=1', $url);

            return new JsonMockResponse($this->featureCollection(0.96));
        });

        $geoPoint = new GeoplateformeGeoLocation($client, self::URL)->find('1 rue de la Paix 75002 Paris');

        $this->assertEquals(new GeoPoint(2.33031, 48.868546), $geoPoint);
    }

    public function testAnUnlikelyMatchIsNotAPosition(): void
    {
        $this->assertNull(new GeoplateformeGeoLocation(new MockHttpClient(new JsonMockResponse($this->featureCollection(0.3))), self::URL)->find('Alexanderplatz Berlin DE'));
    }

    public function testAnAddressOutsideFranceIsNotFound(): void
    {
        $this->assertNull(new GeoplateformeGeoLocation(new MockHttpClient(new JsonMockResponse(['type' => 'FeatureCollection', 'features' => []])), self::URL)->find('10 Downing Street London GB'));
    }

    public function testAnUnavailableServiceDoesNotBreakTheSave(): void
    {
        $this->assertNull(new GeoplateformeGeoLocation(new MockHttpClient(new MockResponse('', ['http_code' => 503])), self::URL)->find('1 rue de la Paix 75002 Paris'));
    }

    /**
     * @return array<string, mixed>
     */
    private function featureCollection(float $score): array
    {
        return [
            'type' => 'FeatureCollection',
            'features' => [[
                'type' => 'Feature',
                'geometry' => ['type' => 'Point', 'coordinates' => [2.33031, 48.868546]],
                'properties' => ['label' => '1 Rue de la Paix 75002 Paris', 'score' => $score],
            ]],
        ];
    }
}
