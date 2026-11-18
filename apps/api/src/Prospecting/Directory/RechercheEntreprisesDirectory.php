<?php

namespace App\Prospecting\Directory;

use App\Shared\ServiceUnavailable;
use Prospecter\Core\Prospecting\Domain\Model\Establishment;
use Prospecter\Core\Prospecting\Domain\Port\EstablishmentDirectory;
use Prospecter\Core\SharedKernel\Domain\Siret;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

/**
 * The directory of the companies of the French State (Recherche d'entreprises): free, no key, a few requests a second.
 * It answers the companies whose name resembles, the closest first, each with its establishments matching the search;
 * the activity of an establishment is its NAF code, the directory giving no label.
 *
 * @see https://recherche-entreprises.api.gouv.fr/docs/
 */
final readonly class RechercheEntreprisesDirectory implements EstablishmentDirectory
{
    /** The administrative state of an establishment that closed (`A` while it is active). */
    private const string CLOSED = 'F';
    /** A handful of propositions to choose from, as many establishments of a company as companies. */
    private const int PROPOSITIONS = 10;
    private const int CLIENT_ERROR = 400;
    private const int TOO_MANY_REQUESTS = 429;
    private const int SERVER_ERROR = 500;
    private const int TIMEOUT = 5;

    public function __construct(
        private HttpClientInterface $httpClient,
        #[Autowire(env: 'APP_DIRECTORY_URL')]
        private string $searchUrl,
    ) {
    }

    public function named(string $name): array
    {
        return $this->search($name);
    }

    public function namedIn(string $name, string $municipalityCode): array
    {
        return $this->search($name, ['code_commune' => $municipalityCode]);
    }

    /**
     * @param array<string, string> $filters
     *
     * @return list<Establishment>
     */
    private function search(string $name, array $filters = []): array
    {
        $query = ['q' => $name, 'per_page' => self::PROPOSITIONS, 'limite_matching_etablissements' => self::PROPOSITIONS, ...$filters];

        return array_merge(...array_map(self::establishmentsOf(...), $this->companies($query)));
    }

    /**
     * @param array<string, mixed> $query
     *
     * @return list<array{nom_complet: string, matching_etablissements: list<array<string, mixed>>}>
     */
    private function companies(array $query): array
    {
        $response = $this->httpClient->request('GET', $this->searchUrl, ['query' => $query, 'timeout' => self::TIMEOUT]);
        $status = self::statusOf($response);

        if (self::isOutOfReach($status)) {
            throw ServiceUnavailable::directory(new \RuntimeException(sprintf('The directory answered %d', $status)));
        }

        return self::isRefused($status) ? [] : self::resultsOf($response);
    }

    /**
     * @return list<array{nom_complet: string, matching_etablissements: list<array<string, mixed>>}>
     */
    private static function resultsOf(ResponseInterface $response): array
    {
        try {
            return $response->toArray()['results'];
        } catch (DecodingExceptionInterface $failure) {
            throw ServiceUnavailable::directory($failure);
        }
    }

    private static function statusOf(ResponseInterface $response): int
    {
        try {
            return $response->getStatusCode();
        } catch (TransportExceptionInterface $failure) {
            throw ServiceUnavailable::directory($failure);
        }
    }

    /**
     * A search the directory refuses, a name of less than three letters, finds nothing.
     */
    private static function isRefused(int $status): bool
    {
        return $status >= self::CLIENT_ERROR;
    }

    private static function isOutOfReach(int $status): bool
    {
        return self::TOO_MANY_REQUESTS === $status || $status >= self::SERVER_ERROR;
    }

    /**
     * @param array{nom_complet: string, matching_etablissements: list<array<string, mixed>>} $company
     *
     * @return list<Establishment>
     */
    private static function establishmentsOf(array $company): array
    {
        return array_map(static fn (array $establishment): Establishment => new Establishment(
            new Siret($establishment['siret']),
            $company['nom_complet'],
            $establishment['adresse'],
            $establishment['activite_principale'],
            self::CLOSED === $establishment['etat_administratif'],
            $establishment['commune'],
        ), $company['matching_etablissements']);
    }
}
