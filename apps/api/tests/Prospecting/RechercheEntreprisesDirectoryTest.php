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
