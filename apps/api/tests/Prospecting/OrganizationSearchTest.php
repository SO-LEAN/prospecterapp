<?php

declare(strict_types=1);

namespace Tests\App\Prospecting;

use App\Prospecting\Persistence\DynamoOrganizationRepository;
use App\Shared\Typed;
use PHPUnit\Framework\Attributes\Group;
use Tests\App\Base\ApiTestCase;
use Tests\App\Base\ReadsResources;

/**
 * An organization is found by the beginning of its corporate name or of its email, read in the indexes of
 * the search of its table (ADR 0071).
 */
#[Group('api')]
class OrganizationSearchTest extends ApiTestCase
{
    use ReadsResources;

    protected function setUp(): void
    {
        parent::setUp();
        $this->login();
    }

    public function testTheOrganizationsOfTheDemonstrationAreFound(): void
    {
        $this->assertSame(['Babbage & Co SAS'], $this->found('babb'));
        $this->assertSame(['Babbage & Co SAS'], $this->found('contact@bab'));
    }

    public function testAnOrganizationIsFoundByTheBeginningOfItsNameWithoutAccentsSpacesNorPunctuation(): void
    {
        $this->requestJson('POST', '/api/organizations', ['legalIdentity' => ['corporateName' => 'Société Générale d\'Électricité']]);
        $this->requestJson('POST', '/api/organizations', ['legalIdentity' => ['corporateName' => 'Globex']]);

        $this->assertSame(['Société Générale d\'Électricité'], $this->found('SOCIETE gen'));
        $this->assertSame([], $this->found('generale'));
    }

    public function testTheOrganizationsFoundComeNewestFirstAfterTheCursor(): void
    {
        $ids = array_map(fn (string $name): string => self::stringAt($this->requestJson('POST', '/api/organizations', ['legalIdentity' => ['corporateName' => $name]]), 'id'), ['Acme 1', 'Acme 2', 'Acme 3']);

        $this->assertSame(['Acme 3', 'Acme 2', 'Acme 1'], $this->found('acme'));
        $this->assertSame(['Acme 2', 'Acme 1'], self::namesOf($this->requestJson('GET', '/api/organizations?q=acme&after='.$ids[2])));
    }

    public function testARenamedOrganizationIsFoundByItsNewNameOnly(): void
    {
        $id = self::stringAt($this->requestJson('POST', '/api/organizations', ['legalIdentity' => ['corporateName' => 'Initech']]), 'id');

        $this->requestJson('PATCH', '/api/organizations/'.$id.'/legal-identity', ['corporateName' => 'Umbrella', 'form' => null, 'type' => null]);

        $this->assertSame(['Umbrella'], $this->found('umbr'));
        $this->assertSame([], $this->found('initech'));
    }

    public function testAnOrganizationNoLongerProspectedIsNotFound(): void
    {
        $id = self::stringAt($this->requestJson('POST', '/api/organizations', ['legalIdentity' => ['corporateName' => 'Initech']]), 'id');

        $this->requestJson('DELETE', '/api/organizations/'.$id);

        $this->assertSame([], $this->found('initech'));
    }

    public function testTheKeysAreWrittenAgainAndTheFormerSearchForgotten(): void
    {
        $table = self::tableOf('prospecting');
        $table->batch([['PK' => 'TOKEN#former#b', 'SK' => 'subject'], ['PK' => 'TOKENS#subject', 'SK' => 'TOKENS']]);

        $written = self::service(DynamoOrganizationRepository::class)->writeTheKeysAgain();

        $this->assertSame(1, $written);
        $this->assertSame([], $table->scan('TOKEN'));
        $this->assertSame(['Babbage & Co SAS'], $this->found('babb'));
    }

    /**
     * @return list<string> the names of the organizations found
     */
    private function found(string $search): array
    {
        return self::namesOf($this->requestJson('GET', '/api/organizations?q='.urlencode($search)));
    }

    /**
     * @param array<string, mixed> $page
     *
     * @return list<string>
     */
    private static function namesOf(array $page): array
    {
        return Typed::strings(array_column(self::records(self::valueAt($page, 'items')), 'name'));
    }
}
