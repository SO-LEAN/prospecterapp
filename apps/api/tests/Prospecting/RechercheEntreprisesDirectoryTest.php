<?php

namespace Tests\App\Prospecting;

use App\Prospecting\Directory\RechercheEntreprisesDirectory;
use App\Shared\ServiceUnavailable;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Prospecter\Core\Prospecting\Domain\Model\Establishment;
use Prospecter\Core\SharedKernel\Domain\Siret;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\JsonMockResponse;
use Symfony\Component\HttpClient\Response\MockResponse;

/**
 * The directory of the companies of the French State (recherche-entreprises.api.gouv.fr) as it answers, learnt on the
 * service itself: the companies whose name resembles, each with the establishments matching the search.
 */
class RechercheEntreprisesDirectoryTest extends TestCase
{
    private const string SEARCH_URL = 'https://directory.test/search';

    public function testAnEstablishmentIsListedWithTheNameOfItsCompany(): void
    {
        $directory = new RechercheEntreprisesDirectory(new MockHttpClient(new JsonMockResponse(self::companies(self::company('ACME', self::establishment('55210055400021', '17 RUE DOCTEUR BOUCHUT 69003 LYON', '69383', closed: true))))), self::SEARCH_URL);

        $this->assertEquals([new Establishment(new Siret('55210055400021'), 'ACME', '17 RUE DOCTEUR BOUCHUT 69003 LYON', '62.02A', true, '69383')], $directory->named('ACME'));
    }

    public function testAnEstablishmentWhoseSiretFailsItsKeyIsLeftOut(): void
    {
        $directory = new RechercheEntreprisesDirectory(new MockHttpClient(new JsonMockResponse(self::companies(self::company(
            'ACME',
            self::establishment('55210055400014', '1 RUE DE LA PAIX 75002 PARIS', '75102'),
            self::establishment('55210055400021', '17 RUE DOCTEUR BOUCHUT 69003 LYON', '69383'),
        )))), self::SEARCH_URL);

        $this->assertSame(['55210055400021'], array_map(static fn (Establishment $establishment): string => $establishment->siret->value, $directory->named('ACME')));
    }

    public function testTheSearchAsksTenCompaniesWithTenEstablishmentsEach(): void
    {
        $asked = [];
        $directory = new RechercheEntreprisesDirectory(new MockHttpClient(static function (string $method, string $url) use (&$asked): JsonMockResponse {
            $asked[] = $url;

            return new JsonMockResponse(self::companies());
        }), self::SEARCH_URL);

        $directory->named('ACME');

        $this->assertSame([self::SEARCH_URL.'?q=ACME&per_page=10&limite_matching_etablissements=10'], $asked);
    }

    public function testARequestLastsFiveSecondsAtMost(): void
    {
        $durations = [];
        $directory = new RechercheEntreprisesDirectory(new MockHttpClient(static function (string $method, string $url, array $options) use (&$durations): JsonMockResponse {
            $durations[] = $options['max_duration'];

            return new JsonMockResponse(self::companies());
        }), self::SEARCH_URL);

        $directory->named('ACME');

        $this->assertSame([5.0], $durations);
    }

    public function testTheSearchInAMunicipalityFiltersByItsCode(): void
    {
        $asked = [];
        $directory = new RechercheEntreprisesDirectory(new MockHttpClient(static function (string $method, string $url) use (&$asked): JsonMockResponse {
            $asked[] = $url;

            return new JsonMockResponse(self::companies(self::company('ACME', self::establishment('55210055400021', '17 RUE DOCTEUR BOUCHUT 69003 LYON', '69383'))));
        }), self::SEARCH_URL);

        $found = $directory->namedIn('ACME', '69383');

        $this->assertSame([self::SEARCH_URL.'?q=ACME&per_page=10&limite_matching_etablissements=10&code_commune=69383'], $asked);
        $this->assertCount(1, $found);
    }

    public function testASearchTheDirectoryRefusesFindsNothing(): void
    {
        $directory = new RechercheEntreprisesDirectory(new MockHttpClient(new JsonMockResponse(['erreur' => 'Veuillez indiquer au moins 3 caractères.'], ['http_code' => 400])), self::SEARCH_URL);

        $this->assertSame([], $directory->named('AB'));
    }

    #[DataProvider('citiesOfArrondissements')]
    public function testACityOfArrondissementsIsSearchedInEachOfThemAnyOtherAsItIs(string $city, string $arrondissements): void
    {
        $asked = [];
        $directory = new RechercheEntreprisesDirectory(new MockHttpClient(static function (string $method, string $url) use (&$asked): JsonMockResponse {
            $asked[] = urldecode((string) parse_url($url, PHP_URL_QUERY));

            return new JsonMockResponse(self::companies());
        }), self::SEARCH_URL);

        $directory->namedIn('ACME', $city);

        $this->assertSame(['q=ACME&per_page=10&limite_matching_etablissements=10&code_commune='.$arrondissements], $asked);
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function citiesOfArrondissements(): iterable
    {
        yield 'Paris' => ['75056', '75101,75102,75103,75104,75105,75106,75107,75108,75109,75110,75111,75112,75113,75114,75115,75116,75117,75118,75119,75120'];
        yield 'Lyon' => ['69123', '69381,69382,69383,69384,69385,69386,69387,69388,69389'];
        yield 'a municipality of Corsica, the city of no arrondissement' => ['2A004', '2A004'];
        yield 'Marseille' => ['13055', '13201,13202,13203,13204,13205,13206,13207,13208,13209,13210,13211,13212,13213,13214,13215,13216'];
    }

    #[DataProvider('outOfReach')]
    public function testADirectoryOutOfReachMakesTheSearchUnavailable(MockResponse $answer): void
    {
        $directory = new RechercheEntreprisesDirectory(new MockHttpClient($answer), self::SEARCH_URL);

        try {
            $directory->named('ACME');
            $this->fail('The directory was expected out of reach');
        } catch (ServiceUnavailable $unavailable) {
            $this->assertSame('directoryUnreachable', $unavailable->reason);
        }
    }

    /**
     * @return iterable<string, array{MockResponse}>
     */
    public static function outOfReach(): iterable
    {
        yield 'failing' => [new MockResponse('', ['http_code' => 503])];
        yield 'limiting the requests' => [new MockResponse('', ['http_code' => 429])];
        yield 'not answering' => [new MockResponse('', ['error' => 'The directory does not answer'])];
        yield 'answering no JSON' => [new MockResponse('<html>Maintenance</html>')];
        yield 'breaking off its answer' => [new MockResponse((static function (): \Generator {
            yield '{"results": [';

            throw new \RuntimeException('The connection broke off');
        })())];
    }

    /**
     * @param array<string, mixed> ...$companies
     *
     * @return array<string, mixed>
     */
    private static function companies(array ...$companies): array
    {
        return ['results' => $companies, 'total_results' => count($companies), 'page' => 1, 'per_page' => 10, 'total_pages' => 1];
    }

    /**
     * @param array<string, mixed> ...$establishments
     *
     * @return array<string, mixed>
     */
    private static function company(string $name, array ...$establishments): array
    {
        return ['siren' => substr($establishments[0]['siret'], 0, 9), 'nom_complet' => $name, 'matching_etablissements' => $establishments];
    }

    /**
     * @return array<string, mixed>
     */
    private static function establishment(string $siret, string $address, string $municipalityCode, bool $closed = false): array
    {
        return ['siret' => $siret, 'adresse' => $address, 'commune' => $municipalityCode, 'activite_principale' => '62.02A', 'etat_administratif' => $closed ? 'F' : 'A'];
    }
}
