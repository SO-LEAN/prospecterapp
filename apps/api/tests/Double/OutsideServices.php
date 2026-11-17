<?php

namespace Tests\App\Double;

use Symfony\Component\HttpClient\Response\JsonMockResponse;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Contracts\HttpClient\ResponseInterface;
use Symfony\Contracts\Service\ResetInterface;

/**
 * The services outside the API, as the tests see them (`framework.http_client.mock_response_factory`): no network,
 * every request gets an empty answer (a geocoding finds nothing), unless a test makes the geocoder place an address,
 * but Google, answered by its double.
 */
final class OutsideServices implements ResetInterface
{
    /** The INSEE code of the municipality of Strasbourg, where the geocoder places an address unless told another. */
    public const string STRASBOURG = '67482';

    /** @var array<string, mixed>|null */
    private ?array $placement = null;

    public function __construct(
        private readonly string $geocoderUrl,
        private readonly GoogleDouble $google,
    ) {
    }

    public function geocoderPlaces(float $longitude, float $latitude, string $municipalityCode = self::STRASBOURG): void
    {
        $this->placement = [
            'type' => 'FeatureCollection',
            'features' => [[
                'type' => 'Feature',
                'geometry' => ['type' => 'Point', 'coordinates' => [$longitude, $latitude]],
                'properties' => ['score' => 1.0, 'citycode' => $municipalityCode],
            ]],
        ];
    }

    /**
     * @param array<string, mixed> $options
     */
    public function __invoke(string $method, string $url, array $options = []): ResponseInterface
    {
        return match (true) {
            null !== $this->placement && str_starts_with($url, $this->geocoderUrl) => new JsonMockResponse($this->placement),
            $this->google->serves($url) => $this->google->answer($method, $url, $options),
            default => new MockResponse(),
        };
    }

    public function reset(): void
    {
        $this->placement = null;
    }
}
