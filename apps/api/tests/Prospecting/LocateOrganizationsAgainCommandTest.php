<?php

namespace Tests\App\Prospecting;

use App\Campaigns\Persistence\CampaignsKeys;
use App\Demo\DemoData;
use App\Prospecting\Persistence\OrganizationKeys;
use App\Shared\Persistence\Table;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Tests\App\Base\ApiTestCase;
use Tests\App\Double\OutsideServices;

/**
 * `app:organizations:locate-again` geocodes again the address of the organizations located before their municipality
 * was kept, so that they carry their geographic tags (ADR 0142); run again, it has nothing left to do.
 */
class LocateOrganizationsAgainCommandTest extends ApiTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->login();
    }

    public function testAnOrganizationLocatedBeforeGetsItsMunicipalityOnce(): void
    {
        self::getContainer()->get(OutsideServices::class)->geocoderPlaces(7.7456, 48.5834);
        $organization = $this->requestJson('POST', '/api/organizations', ['legalIdentity' => ['corporateName' => 'ACME'], 'address' => ['city' => 'Strasbourg']])['id'];
        $this->forgetTheMunicipalityOf($organization);
        $this->assertSame([], $this->requestJson('GET', '/api/organizations/'.$organization.'/tags')['automatic']);

        $this->assertStringContainsString('1 organization(s) located again', $this->locateAgain());

        $this->assertSame(['#STRASBOURG', '#67', '#GRAND_EST'], $this->requestJson('GET', '/api/organizations/'.$organization.'/tags')['automatic']);
        $this->assertStringContainsString('0 organization(s) located again', $this->locateAgain());
    }

    /**
     * The command runs in the kernel of the last request, the geocoder told again where to place the address.
     */
    private function locateAgain(): string
    {
        self::getContainer()->get(OutsideServices::class)->geocoderPlaces(7.7456, 48.5834);
        $tester = new CommandTester(new Application(self::$kernel)->find('app:organizations:locate-again'));
        $tester->execute([]);
        $tester->assertCommandIsSuccessful();

        return preg_replace('/\s+/', ' ', $tester->getDisplay());
    }

    /**
     * Its items as written before the organizations kept their municipality: in Prospecting, and its fact in Campaigns.
     */
    private function forgetTheMunicipalityOf(string $organization): void
    {
        $item = $this->table('prospecting')->item(OrganizationKeys::organization($organization), OrganizationKeys::ORGANIZATION);
        $this->assertNotNull($item);
        unset($item['organization']['municipalityCode']);
        $this->table('prospecting')->batch([$item]);

        $fact = $this->table('campaigns')->item(CampaignsKeys::account(DemoData::SOLEAN_IT), CampaignsKeys::organizationFact($organization));
        $this->assertNotNull($fact);
        unset($fact['municipalityCode'], $fact['country']);
        $this->table('campaigns')->batch([$fact]);
    }

    private function table(string $context): Table
    {
        $table = self::getContainer()->get('app.table.'.$context);
        $this->assertInstanceOf(Table::class, $table);

        return $table;
    }
}
