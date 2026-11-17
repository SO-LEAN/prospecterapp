<?php

declare(strict_types=1);

namespace Tests\Core\Acceptance\Context;

use Behat\Behat\Context\Context;
use Behat\Gherkin\Node\TableNode;
use Behat\Step\Given;
use Behat\Step\Then;
use Behat\Step\When;
use Prospecter\Core\Campaigns\Application\ConsultContactTags\ConsultContactTags;
use Prospecter\Core\Campaigns\Application\ConsultOrganizationTags\ConsultOrganizationTags;
use Prospecter\Core\Prospecting\Application\Relocate\Relocate;
use Prospecter\Core\Prospecting\Domain\Model\Organization;
use Prospecter\Core\SharedKernel\Domain\Address;
use Prospecter\Core\SharedKernel\Domain\ContactDetails;
use Prospecter\Core\SharedKernel\Domain\GeoPoint;
use Prospecter\Core\SharedKernel\Domain\LegalIdentity;
use Prospecter\Core\SharedKernel\Domain\Location;
use Prospecter\Core\SharedKernel\Domain\OrganizationId;
use Tests\Core\Acceptance\Expect;

final class GeographicTagsContext implements Context
{
    use SharesTheScenario;

    private const string ADDRESS_PARTS = ', ';

    private ?int $locatedAgain = null;

    #[Given('le géocodeur connaît les communes :')]
    public function theGeocoderKnowsTheMunicipalities(TableNode $table): void
    {
        foreach ($table->getHash() as $row) {
            $this->application->geoLocation->know($row['lieu'], 0.0, 0.0, $row['commune']);
        }
    }

    /**
     * The address as written on one line: its street, then its postal code and its city, then the code of its country
     * when given; a single part is a city.
     */
    #[When('je déménage :name au :address')]
    public function iRelocateTo(string $name, string $address): void
    {
        $parts = explode(self::ADDRESS_PARTS, $address);

        if (1 === count($parts)) {
            $this->relocate($name, null, null, $address, null);

            return;
        }

        [$postalCode, $city] = explode(' ', $parts[1], 2);
        $this->relocate($name, $parts[0], $postalCode, $city, $parts[2] ?? null);
    }

    #[Given('/^"(?<name>[^"]+)" est située à (?<place>[^" ]+)$/')]
    public function isLocatedIn(string $name, string $place): void
    {
        $this->relocate($name, null, null, $place, null);
    }

    #[Given('/^l\'organisation "(?<name>[^"]+)" appartient à "(?<owner>[^"]+)", située à (?<place>[^" ]+)$/')]
    public function anOrganizationLocatedIn(string $name, string $owner, string $place): void
    {
        $this->scenario->save($name, Organization::prospectedBy(
            $this->scenario->prospectorOf($this->scenario->accountIdOf($owner)),
            new OrganizationId($this->application->newIdentity()),
            new LegalIdentity($name),
            new ContactDetails(),
            $this->application->now(),
        ));
        $this->isLocatedIn($name, $place);
    }

    /**
     * Geocoded before the municipality merged into a new one: the code is no longer the one the geocoder gives.
     */
    #[Given('/^"(?<name>[^"]+)" a été géocodée à (?<place>.+) \((?<code>\w+)\), commune fusionnée depuis \d+$/')]
    public function wasGeocodedIn(string $name, string $place, string $code): void
    {
        $organization = $this->scenario->organizationNamed($name);
        $organization->relocateTo(new Address(city: $place), new Location(new GeoPoint(0.0, 0.0), $code), by: $this->scenario->managerOf($organization), at: $this->application->now());
        $this->application->organizations->save($organization);
    }

    /**
     * Located before Prospecter kept the municipality code the geocoder gives.
     */
    #[Given(':name a été située à :place sans son code commune')]
    public function wasLocatedWithoutItsMunicipality(string $name, string $place): void
    {
        $organization = $this->scenario->organizationNamed($name);
        $organization->relocateTo(new Address(city: $place), new Location(new GeoPoint(7.75, 48.58), 'none'), by: $this->scenario->managerOf($organization), at: $this->application->now());
        new \ReflectionProperty(Organization::class, 'municipalityCode')->setValue($organization, null);
        $this->application->organizations->save($organization);
    }

    #[When('les organisations situées sans code commune sont géocodées à nouveau')]
    public function theOrganizationsAreLocatedAgain(): void
    {
        $this->locatedAgain = $this->application->locateOrganizationsAgain();
    }

    #[Then('aucune organisation n\'a été géocodée à nouveau')]
    public function noOrganizationWasLocatedAgain(): void
    {
        Expect::same(0, $this->locatedAgain);
    }

    #[Then(':name est située :tags')]
    public function isLocatedBy(string $name, string $tags): void
    {
        Expect::same($tags, implode(' ', $this->automaticTagsOf($name)));
    }

    #[Then(':name n\'a aucun tag géographique')]
    public function hasNoGeographicTag(string $name): void
    {
        Expect::same([], $this->automaticTagsOf($name));
    }

    #[Then('/^"(?<contact>[^"]+)" porte (?<tags>"#[^"]+"(?: et "[^"]+")*)$/')]
    public function carries(string $contact, string $tags): void
    {
        $kept = $this->application->consultContactTags(new ConsultContactTags($this->application->signedIn(), [$this->scenario->personIdOf($contact)]));
        $carried = $kept?->contacts[0]?->carried() ?? throw new \LogicException(sprintf('The tags of %s were refused', $contact));
        preg_match_all('/#\w+/', $tags, $expected);

        foreach ($expected[0] as $tag) {
            Expect::that(in_array($tag, $carried, true), sprintf('%s does not carry %s, only %s', $contact, $tag, implode(' ', $carried)));
        }
    }

    /**
     * @return list<string>
     */
    private function automaticTagsOf(string $name): array
    {
        $kept = $this->application->consultOrganizationTags(new ConsultOrganizationTags($this->application->signedIn(), $this->scenario->idOf($name)));

        return $kept?->automatic ?? throw new \LogicException(sprintf('The tags of %s were refused: %s', $name, $this->application->refusal()?->getMessage()));
    }

    private function relocate(string $name, ?string $street, ?string $postalCode, ?string $city, ?string $country): void
    {
        $this->application->dispatch(new Relocate($this->application->signedIn(), $this->scenario->idOf($name), $street, $postalCode, $city, $country, $this->application->now()));
    }
}
