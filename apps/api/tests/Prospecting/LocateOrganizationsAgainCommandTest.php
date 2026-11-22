<?php

declare(strict_types=1);

namespace Tests\App\Prospecting;

use App\Campaigns\Persistence\CampaignsKeys;
use App\Demo\DemoData;
use App\Prospecting\Persistence\OrganizationKeys;
use App\Shared\Typed;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Tests\App\Base\ApiTestCase;
use Tests\App\Base\ReadsResources;
use Tests\App\Double\OutsideServices;

/**
 * `app:organizations:locate-again` geocodes again the address of the organizations located before their municipality
 * was kept, so that they carry their geographic tags (ADR 0142); run again, it has nothing left to do.
 */
class LocateOrganizationsAgainCommandTest extends ApiTestCase
{
    use ReadsResources;

    protected function setUp(): void
    {
        parent::setUp();
        $this->login();
    }

    public function testAnOrganizationLocatedBeforeGetsItsMunicipalityOnce(): void
    {
        self::service(OutsideServices::class)->geocoderPlaces(7.7456, 48.5834);
        $organization = self::stringAt($this->requestJson('POST', '/api/organizations', ['legalIdentity' => ['corporateName' => 'ACME'], 'address' => ['city' => 'Strasbourg']]), 'id');
        $this->forgetTheMunicipalityOf($organization);
        $this->assertSame([], $this->requestJson('GET', '/api/organizations/'.$organization.'/tags')['automatic']);

        $this->assertStringContainsString('1 organization(s) located again, 0 whose address the geocoder no longer finds', $this->locateAgain());

        $this->assertSame(['#STRASBOURG', '#67', '#GRAND_EST'], $this->requestJson('GET', '/api/organizations/'.$organization.'/tags')['automatic']);
        $this->assertStringContainsString('0 organization(s) located again', $this->locateAgain());
    }

    public function testAnOrganizationWhoseAddressTheGeocoderNoLongerFindsIsCountedApart(): void
    {
        self::service(OutsideServices::class)->geocoderPlaces(7.7456, 48.5834);
        $organization = self::stringAt($this->requestJson('POST', '/api/organizations', ['legalIdentity' => ['corporateName' => 'ACME'], 'address' => ['city' => 'Strasbourg']]), 'id');
        $this->forgetTheMunicipalityOf($organization);

        $this->assertStringContainsString('0 organization(s) located again, 1 whose address the geocoder no longer finds', $this->locateAgain(placed: false));

        $this->assertSame([], $this->requestJson('GET', '/api/organizations/'.$organization.'/tags')['automatic']);
    }

    /**
     * The command runs in the kernel of the last request, the geocoder told again where to place the address, or
     * finding nothing.
     */
    private function locateAgain(bool $placed = true): string
    {
        $geocoder = self::service(OutsideServices::class);
        $geocoder->reset();

        if ($placed) {
            $geocoder->geocoderPlaces(7.7456, 48.5834);
        }
        $tester = new CommandTester(new Application($this->client->getKernel())->find('app:organizations:locate-again'));
        $tester->execute([]);
        $tester->assertCommandIsSuccessful();

        return Typed::string(preg_replace('/\s+/', ' ', $tester->getDisplay()));
    }

    /**
     * Its items as written before the organizations kept their municipality: in Prospecting, and its fact in Campaigns.
     */
    private function forgetTheMunicipalityOf(string $organization): void
    {
        $item = self::storedItem(self::tableOf('prospecting'), OrganizationKeys::organization($organization), OrganizationKeys::ORGANIZATION);
        $document = Typed::document($item['organization']);
        unset($document['municipalityCode']);
        self::tableOf('prospecting')->batch([['organization' => $document] + $item]);

        $fact = self::storedItem(self::tableOf('campaigns'), CampaignsKeys::account(DemoData::SOLEAN_IT), CampaignsKeys::organizationFact($organization));
        unset($fact['municipalityCode'], $fact['country']);
        self::tableOf('campaigns')->batch([$fact]);
    }
}
