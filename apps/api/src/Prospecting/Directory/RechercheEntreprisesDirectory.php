<?php

declare(strict_types=1);

namespace App\Prospecting\Directory;

use App\Shared\ServiceUnavailable;
use App\Shared\Typed;
use Prospecter\Core\Prospecting\Domain\Model\Establishment;
use Prospecter\Core\Prospecting\Domain\Port\EstablishmentDirectory;
use Prospecter\Core\SharedKernel\Domain\Company\InvalidSiret;
use Prospecter\Core\SharedKernel\Domain\Company\Siret;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

/**
 * The French State's company directory (Recherche d'entreprises). It is free, needs no key, and takes a few requests a
 * second. It answers the companies with a similar name, the closest first. Each company comes with its establishments
 * that match the search. An establishment's activity is its NAF code, because the directory gives no label.
 *
 * @see https://recherche-entreprises.api.gouv.fr/docs/
 */
final readonly class RechercheEntreprisesDirectory implements EstablishmentDirectory
{
    /** The administrative state of a closed establishment (`A` while it is active). */
    private const string CLOSED = 'F';
    /** A handful of proposals to choose from: several establishments of one company, or several companies. */
    private const int PROPOSITIONS = 10;
    private const int CLIENT_ERROR = 400;
    private const int TOO_MANY_REQUESTS = 429;
    private const int SERVER_ERROR = 500;
    /** In seconds, both between two bytes and for the whole request. A search makes two requests. */
    private const float TIMEOUT = 5.0;
    /** For each city with arrondissements: its INSEE code, its first arrondissement's code, and their number. */
    private const array ARRONDISSEMENTS = ['75056' => [75101, 20], '69123' => [69381, 9], '13055' => [13201, 16]];

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
        return $this->search($name, ['code_commune' => implode(',', self::municipalitiesOf($municipalityCode))]);
    }

    /**
     * The directory places an establishment in Paris, Lyon or Marseille in its arrondissement. The geocoder finds such
     * a city as a whole when the address has no street, so the whole city stands for each of its arrondissements.
     *
     * @return list<string>
     */
    private static function municipalitiesOf(string $municipalityCode): array
    {
        if (!isset(self::ARRONDISSEMENTS[$municipalityCode])) {
            return [$municipalityCode];
        }

        [$first, $count] = self::ARRONDISSEMENTS[$municipalityCode];

        return array_map(strval(...), range($first, $first + $count - 1));
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
     * @return list<array<string, mixed>>
     */
    private function companies(array $query): array
    {
        $response = $this->httpClient->request('GET', $this->searchUrl, ['query' => $query, 'timeout' => self::TIMEOUT, 'max_duration' => self::TIMEOUT]);
        $status = self::statusOf($response);

        if (self::isOutOfReach($status)) {
            throw ServiceUnavailable::directory(new \RuntimeException(sprintf('The directory answered %d', $status)));
        }

        return self::isRefused($status) ? [] : self::resultsOf($response);
    }

    /**
     * @return list<array<string, mixed>> the companies found. Each company has its name (`nom_complet`) and the
     *                                    establishments that match the search (`matching_etablissements`).
     */
    private static function resultsOf(ResponseInterface $response): array
    {
        try {
            return Typed::documents($response->toArray()['results']);
        } catch (DecodingExceptionInterface|TransportExceptionInterface $failure) {
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
     * A search that the directory refuses, such as a name of less than three letters, finds nothing.
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
     * @param array<string, mixed> $company
     *
     * @return list<Establishment>
     */
    private static function establishmentsOf(array $company): array
    {
        $name = Typed::string($company['nom_complet']);

        return array_values(array_filter(array_map(
            static fn (array $establishment): ?Establishment => self::establishmentOf($name, $establishment),
            Typed::documents($company['matching_etablissements']),
        )));
    }

    /**
     * Returns null for an establishment whose SIRET fails its check digit, because an organization could not record it.
     *
     * @param array<string, mixed> $establishment
     */
    private static function establishmentOf(string $name, array $establishment): ?Establishment
    {
        try {
            $siret = new Siret(Typed::string($establishment['siret']));
        } catch (InvalidSiret) {
            return null;
        }

        return new Establishment($siret, $name, Typed::string($establishment['adresse']), Typed::stringOrNull($establishment['activite_principale']), self::CLOSED === $establishment['etat_administratif'], Typed::stringOrNull($establishment['commune']));
    }
}
