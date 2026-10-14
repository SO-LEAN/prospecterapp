<?php

namespace App\Service;

use App\Service\GeoLocator\GeoPointResponse;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Geocoding by the French State (IGN Géoplateforme, Base Adresse Nationale): free, no key, addresses in France only.
 *
 * @see https://geoservices.ign.fr/documentation/services/services-geoplateforme/geocodage
 */
final readonly class GeoplateformeGeoLocator implements GeoLocator
{
    /** Below this relevance the best match is another place (the service always answers with its closest address). */
    private const float MIN_SCORE = 0.5;

    public function __construct(
        private HttpClientInterface $httpClient,
        #[Autowire(env: 'APP_GEOCODER_URL')]
        private string $searchUrl,
    ) {
    }

    public function find(string $address): GeoPointResponse
    {
        try {
            $features = $this->httpClient->request('GET', $this->searchUrl, [
                'query' => ['q' => $address, 'limit' => 1],
                'timeout' => 5,
            ])->toArray()['features'] ?? [];
        } catch (ExceptionInterface) {
            return new GeoPointResponse($address, 0, 0, false);
        }

        $best = $features[0] ?? null;

        if (null === $best || ($best['properties']['score'] ?? 0) < self::MIN_SCORE) {
            return new GeoPointResponse($address, 0, 0, false);
        }

        [$longitude, $latitude] = $best['geometry']['coordinates'];

        return new GeoPointResponse($best['properties']['label'] ?? $address, (float) $longitude, (float) $latitude);
    }
}
