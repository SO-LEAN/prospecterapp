<?php

declare(strict_types=1);

namespace Tests\Core\Domain;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Prospecter\Core\SharedKernel\Domain\Designated;
use Prospecter\Core\SharedKernel\Domain\Exception\NotAWebAddress;
use Prospecter\Core\SharedKernel\Domain\Exception\ReferenceOfAnotherKind;
use Prospecter\Core\SharedKernel\Domain\Exception\UnrecognizedPage;
use Prospecter\Core\SharedKernel\Domain\ExternalReference;
use Prospecter\Core\SharedKernel\Domain\KnownSource;

class ExternalReferenceTest extends TestCase
{
    #[DataProvider('knownSources')]
    public function testAnAddressOfAKnownSourceIsKeptAsItsSourceAndItsIdentifier(string $address, Designated $designated, string $source, string $identifier): void
    {
        $reference = ExternalReference::designating($address, $designated);

        $this->assertSame([$source, $identifier], [$reference->source, $reference->identifier]);
    }

    /**
     * @return iterable<string, array{string, Designated, string, string}>
     */
    public static function knownSources(): iterable
    {
        yield 'Welcome to the Jungle' => ['HTTPS://www.Welcometothejungle.com/fr/companies/ACME/jobs?page=2', Designated::Organization, 'welcomeToTheJungle', 'acme'];
        yield 'a LinkedIn profile' => ['fr.linkedin.com/in/Ada-Lovelace/', Designated::Person, 'linkedInProfile', 'ada-lovelace'];
        yield 'a LinkedIn page of a company' => ['https://www.linkedin.com/company/ACME-Corp/about/', Designated::Organization, 'linkedInCompany', 'acme-corp'];
        yield 'a Malt profile' => ['https://www.malt.fr/profile/AdaLovelace?q=1', Designated::Person, 'malt', 'adalovelace'];
        yield 'an unknown tool, its host the source, its address the identifier' => ['https://www.annuaire-xyz.com/fiche/Acme-SAS?ref=mail#top', Designated::Organization, 'annuaire-xyz.com', 'annuaire-xyz.com/fiche/acme-sas'];
    }

    #[DataProvider('pastings')]
    public function testTheAddressPastedIsKeptToBeOpened(string $pasted, string $kept): void
    {
        $this->assertSame($kept, ExternalReference::designating($pasted, Designated::Organization)->address);
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function pastings(): iterable
    {
        yield 'as pasted' => ['https://www.annuaire-xyz.com/fiche/Acme-SAS?ref=mail', 'https://www.annuaire-xyz.com/fiche/Acme-SAS?ref=mail'];
        yield 'spaces around' => ['  http://annuaire-xyz.com/Acme  ', 'http://annuaire-xyz.com/Acme'];
        yield 'without its scheme' => ['www.annuaire-xyz.com/Acme', 'https://www.annuaire-xyz.com/Acme'];
    }

    #[DataProvider('ofAnotherKind')]
    public function testAnAddressOfAKnownSourceDesignatingAnotherKindIsRefused(string $address, Designated $designated, KnownSource $source): void
    {
        $this->expectExceptionObject(ReferenceOfAnotherKind::from($source));

        ExternalReference::designating($address, $designated);
    }

    /**
     * @return iterable<string, array{string, Designated, KnownSource}>
     */
    public static function ofAnotherKind(): iterable
    {
        yield 'a Malt profile on an organization' => ['https://www.malt.fr/profile/adalovelace', Designated::Organization, KnownSource::Malt];
        yield 'a LinkedIn profile on an organization' => ['https://www.linkedin.com/in/ada-lovelace', Designated::Organization, KnownSource::LinkedInProfile];
        yield 'a company page on a person' => ['https://www.linkedin.com/company/acme', Designated::Person, KnownSource::LinkedInCompany];
        yield 'a Welcome to the Jungle page on a person' => ['https://www.welcometothejungle.com/fr/companies/acme', Designated::Person, KnownSource::WelcomeToTheJungle];
    }

    #[DataProvider('unrecognizedPages')]
    public function testAnAddressOfTheSiteOfAKnownSourceThatIsNoneOfItsPagesIsRefused(string $address, string $host): void
    {
        $this->expectExceptionObject(UnrecognizedPage::on($host));

        ExternalReference::designating($address, Designated::Organization);
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function unrecognizedPages(): iterable
    {
        yield 'the page of a school on LinkedIn' => ['https://www.linkedin.com/school/acme', 'linkedin.com'];
        yield 'a showcase page on LinkedIn' => ['fr.linkedin.com/showcase/acme-cloud', 'fr.linkedin.com'];
        yield 'a company page without identifier' => ['https://www.linkedin.com/company/', 'linkedin.com'];
        yield 'a search on Malt' => ['https://www.malt.fr/s?q=php', 'malt.fr'];
        yield 'the jobs of Welcome to the Jungle' => ['https://www.welcometothejungle.com/fr/jobs', 'welcometothejungle.com'];
    }

    #[DataProvider('notWebAddresses')]
    public function testWhatIsNoAddressOfAWebPageIsRefusedOnTheLinks(string $notAnAddress): void
    {
        $this->expectExceptionObject(NotAWebAddress::of($notAnAddress));

        ExternalReference::designating($notAnAddress, Designated::Organization);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function notWebAddresses(): iterable
    {
        yield 'words' => ['ACME sur LinkedIn'];
        yield 'another scheme' => ['ftp://acme.fr/catalogue'];
        yield 'a host without a dot' => ['https://intranet/acme'];
        yield 'nothing' => ['  '];
    }

    public function testAReferenceStoredIsRecognizedAgainByItsAddressUnderASourceKnownSince(): void
    {
        $stored = ExternalReference::restored('linkedin.com', 'linkedin.com/company/acme', 'https://www.linkedin.com/company/acme');

        $this->assertSame('linkedInCompany#acme', $stored->recognizedAgain(Designated::Organization)->key());
    }

    public function testAReferenceStoredThatTodayRefusesStaysAsItWas(): void
    {
        $stored = ExternalReference::restored('linkedin.com', 'linkedin.com/in/ada', 'https://www.linkedin.com/in/ada');

        $this->assertSame($stored, $stored->recognizedAgain(Designated::Organization));
    }
}
