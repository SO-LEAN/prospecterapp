<?php

namespace Tests\App\Service;

use App\Service\GeoplateformeGeoLocator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\JsonMockResponse;
use Symfony\Component\HttpClient\Response\MockResponse;

class GeoplateformeGeoLocatorTest extends TestCase
{
    private const string URL = 'https://geocoder.test/search';

    public function testFindsTheBestMatch(): void
    {
        $client = new MockHttpClient(function (string $method, string $url): JsonMockResponse {
            $this->assertSame('GET', $method);
            $this->assertSame(self::URL.'?q=1%20rue%20de%20la%20Paix%2075002%20Paris&limit=1', $url);

            return new JsonMockResponse($this->featureCollection(0.96));
        });

        $response = new GeoplateformeGeoLocator($client, self::URL)->find('1 rue de la Paix 75002 Paris');

        $this->assertTrue($response->isSucceeded());
        $this->assertSame(2.33031, $response->getLongitude());
        $this->assertSame(48.868546, $response->getLatitude());
        $this->assertSame('1 Rue de la Paix 75002 Paris', $response->getAddress());
    }

    public function testAnUnlikelyMatchIsNotAPosition(): void
    {
        $response = new GeoplateformeGeoLocator(new MockHttpClient(new JsonMockResponse($this->featureCollection(0.3))), self::URL)->find('Alexanderplatz Berlin DE');

        $this->assertFalse($response->isSucceeded());
    }

    public function testAnAddressOutsideFranceIsNotFound(): void
    {
        $response = new GeoplateformeGeoLocator(new MockHttpClient(new JsonMockResponse(['type' => 'FeatureCollection', 'features' => []])), self::URL)->find('10 Downing Street London GB');

        $this->assertFalse($response->isSucceeded());
        $this->assertSame('10 Downing Street London GB', $response->getAddress());
    }

    public function testAnUnavailableServiceDoesNotBreakTheSave(): void
    {
        $response = new GeoplateformeGeoLocator(new MockHttpClient(new MockResponse('', ['http_code' => 503])), self::URL)->find('1 rue de la Paix 75002 Paris');

        $this->assertFalse($response->isSucceeded());
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
