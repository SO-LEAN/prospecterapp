<?php

declare(strict_types=1);

namespace App\Shared;

use Prospecter\Core\SharedKernel\Application\Port\GeoLocation;
use Prospecter\Core\SharedKernel\Domain\GeoPoint;
use Prospecter\Core\SharedKernel\Domain\Location;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Geocodes addresses with the French State's service (IGN Géoplateforme, Base Adresse Nationale). The service is free,
 * needs no key and covers addresses in France only. Each result gives its municipality's INSEE code (`citycode`, ADR
 * 0142). In Paris, Lyon and Marseille, it gives the arrondissement's code.
 *
 * @see https://geoservices.ign.fr/documentation/services/services-geoplateforme/geocodage
 */
final readonly class GeoplateformeGeoLocation implements GeoLocation
{
    /** Below this relevance, the best match is another place. The service always answers with its closest address. */
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

        if (null === $bestMatch) {
            return null;
        }

        $properties = Typed::document($bestMatch['properties'] ?? []);

        if (Typed::float($properties['score'] ?? 0) < self::MIN_SCORE || !isset($properties['citycode'])) {
            return null;
        }

        [$longitude, $latitude] = Typed::array(Typed::document($bestMatch['geometry'] ?? null)['coordinates'] ?? null);

        return new Location(new GeoPoint(Typed::float($longitude), Typed::float($latitude)), Typed::string($properties['citycode']));
    }

    /**
     * @return array<string, mixed>|null the service's first feature, with its geometry and its properties
     */
    private function bestMatch(string $address): ?array
    {
        try {
            $features = $this->httpClient->request('GET', $this->searchUrl, [
                'query' => ['q' => $address, 'limit' => 1],
                'timeout' => 5,
            ])->toArray()['features'] ?? [];
        } catch (ExceptionInterface) {
            return null;
        }

        $first = Typed::array($features)[0] ?? null;

        return null === $first ? null : Typed::document($first);
    }
}
