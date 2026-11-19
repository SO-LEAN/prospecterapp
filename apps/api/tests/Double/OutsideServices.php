<?php

namespace Tests\App\Double;

use Symfony\Component\HttpClient\Response\JsonMockResponse;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Contracts\HttpClient\ResponseInterface;
use Symfony\Contracts\Service\ResetInterface;

/**
 * The services outside the API, as the tests see them (`framework.http_client.mock_response_factory`): no network,
 * every request gets an empty answer (a geocoding finds nothing, the directory fails), unless a test makes the
 * geocoder place an address or the directory list companies, but Google, answered by its double, and the metadata
 * documents of the AIs.
 */
final class OutsideServices implements ResetInterface
{
    /** The INSEE code of the municipality of Strasbourg, where the geocoder places an address unless told another. */
    public const string STRASBOURG = '67482';

    /** @var array<string, mixed>|null */
    private ?array $placement = null;
    /** @var list<array<string, mixed>>|null */
    private ?array $companies = null;

    public function __construct(
        private readonly string $geocoderUrl,
        private readonly string $directoryUrl,
        private readonly GoogleDouble $google,
        private readonly ClientsOfTheAIs $clientsOfTheAIs,
    ) {
    }

    /**
     * The companies the directory answers every search with, as it lists them.
     *
     * @param array<string, mixed> ...$companies
     */
    public function directoryLists(array ...$companies): void
    {
        $this->companies = array_values($companies);
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
            null !== $this->companies && str_starts_with($url, $this->directoryUrl) => new JsonMockResponse(['results' => $this->companies]),
            $this->google->serves($url) => $this->google->answer($method, $url, $options),
            $this->clientsOfTheAIs->serves($url) => $this->clientsOfTheAIs->answer($url),
            default => new MockResponse(),
        };
    }

    public function reset(): void
    {
        $this->placement = null;
        $this->companies = null;
    }
}
