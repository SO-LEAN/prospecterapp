<?php

namespace App\Shared;

use Prospecter\Core\SharedKernel\Application\Port\GeoLocation;
use Prospecter\Core\SharedKernel\Domain\GeoPoint;
use Prospecter\Core\SharedKernel\Domain\Location;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Geocoding by the French State (IGN Géoplateforme, Base Adresse Nationale): free, no key, addresses in France only.
 * Each result names the INSEE code of its municipality (`citycode`, ADR 0142), that of the arrondissement in Paris, Lyon
 * and Marseille.
 *
 * @see https://geoservices.ign.fr/documentation/services/services-geoplateforme/geocodage
 */
final readonly class GeoplateformeGeoLocation implements GeoLocation
{
    /** Below this relevance the best match is another place (the service always answers with its closest address). */
    private const float MIN_SCORE = 0.5;

    public function __construct(
        private HttpClientInterface $httpClient,
        #[Autowire(env: 'APP_GEOCODER_URL')]
        private string $searchUrl,
    ) {
    }

    public function find(string $address): ?Location
    {
        $bestMatch = $this->bestMatch($address);

        if (null === $bestMatch || ($bestMatch['properties']['score'] ?? 0) < self::MIN_SCORE || !isset($bestMatch['properties']['citycode'])) {
            return null;
        }

        [$longitude, $latitude] = $bestMatch['geometry']['coordinates'];

        return new Location(new GeoPoint((float) $longitude, (float) $latitude), $bestMatch['properties']['citycode']);
    }

    /**
     * @return array{geometry: array{coordinates: array{float, float}}, properties: array{score?: float, citycode?: string}}|null
     */
    private function bestMatch(string $address): ?array
    {
        try {
            return $this->httpClient->request('GET', $this->searchUrl, [
                'query' => ['q' => $address, 'limit' => 1],
                'timeout' => 5,
            ])->toArray()['features'][0] ?? null;
        } catch (ExceptionInterface) {
            return null;
        }
    }
}
