<?php

namespace Tests\App\Prospecting;

use App\Prospecting\Persistence\OrganizationSearchIndex;
use PHPUnit\Framework\Attributes\Group;
use Tests\App\Base\ApiTestCase;

/**
 * The search of the organizations is a projection of their words, kept up to date by the domain events and
 * rebuilt from the organizations when it drifted (ADR 0050).
 */
#[Group('api')]
class OrganizationSearchTest extends ApiTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->login();
    }

    public function testTheOrganizationsOfTheDemonstrationAreFound(): void
    {
        $this->assertSame(['Babbage & Co SAS'], $this->found('babb'));
    }

    public function testAnOrganizationIsFoundByTheBeginningsOfItsWordsWithoutAccents(): void
    {
        $this->requestJson('POST', '/api/organizations', ['legalIdentity' => ['corporateName' => 'Société Générale d\'Électricité']]);
        $this->requestJson('POST', '/api/organizations', ['legalIdentity' => ['corporateName' => 'Globex']]);

        $this->assertSame(['Société Générale d\'Électricité'], $this->found('gen ELEC'));
        $this->assertSame([], $this->found('ener'));
    }

    public function testARenamedOrganizationIsFoundByItsNewNameOnly(): void
    {
        $id = $this->requestJson('POST', '/api/organizations', ['legalIdentity' => ['corporateName' => 'Initech']])['id'];

        $this->requestJson('PATCH', '/api/organizations/'.$id.'/legal-identity', ['corporateName' => 'Umbrella', 'form' => null, 'type' => null]);

        $this->assertSame(['Umbrella'], $this->found('umbr'));
        $this->assertSame([], $this->found('initech'));
    }

    public function testAnOrganizationNoLongerProspectedIsNotFound(): void
    {
        $id = $this->requestJson('POST', '/api/organizations', ['legalIdentity' => ['corporateName' => 'Initech']])['id'];

        $this->requestJson('DELETE', '/api/organizations/'.$id);

        $this->assertSame([], $this->found('initech'));
    }

    public function testTheRebuildRepairsAProjectionThatDrifted(): void
    {
        $this->requestJson('POST', '/api/organizations', ['legalIdentity' => ['corporateName' => 'Initech']]);
        self::getContainer()->get('app.search.prospecting')->clear();
        $this->assertSame([], $this->found('initech'));

        $indexed = self::getContainer()->get(OrganizationSearchIndex::class)->rebuild();

        $this->assertSame(2, $indexed);
        $this->assertSame(['Initech'], $this->found('initech'));
    }

    /**
     * @return list<string> the names of the organizations found
     */
    private function found(string $search): array
    {
        return array_column($this->requestJson('GET', '/api/organizations?q='.urlencode($search))['items'], 'name');
    }
}
