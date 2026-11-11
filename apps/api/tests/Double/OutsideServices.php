<?php

namespace Tests\App\Double;

use Symfony\Component\HttpClient\Response\JsonMockResponse;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Contracts\HttpClient\ResponseInterface;
use Symfony\Contracts\Service\ResetInterface;

/**
 * The services outside the API, as the tests see them (`framework.http_client.mock_response_factory`): no network,
 * every request gets an empty answer (a geocoding finds nothing), unless a test makes the geocoder place an address.
 */
final class OutsideServices implements ResetInterface
{
    /** @var array<string, mixed>|null */
    private ?array $placement = null;

    public function __construct(private readonly string $geocoderUrl)
    {
    }

    public function geocoderPlaces(float $longitude, float $latitude): void
    {
        $this->placement = [
            'type' => 'FeatureCollection',
            'features' => [[
                'type' => 'Feature',
                'geometry' => ['type' => 'Point', 'coordinates' => [$longitude, $latitude]],
                'properties' => ['score' => 1.0],
            ]],
        ];
    }

    public function __invoke(string $method, string $url): ResponseInterface
    {
        if (null !== $this->placement && str_starts_with($url, $this->geocoderUrl)) {
            return new JsonMockResponse($this->placement);
        }

        return new MockResponse();
    }

    public function reset(): void
    {
        $this->placement = null;
    }
}
