<?php

declare(strict_types=1);

namespace Tests\Core\Domain;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Prospecter\Core\PersonalData\Domain\Model\LinkedInProfile;
use Prospecter\Core\SharedKernel\Domain\Exception\BusinessRuleViolation;

class LinkedInProfileTest extends TestCase
{
    #[DataProvider('typings')]
    public function testAProfileIsKeptInOneFormWhateverItsTyping(string $typed): void
    {
        $this->assertSame('https://www.linkedin.com/in/ada-lovelace', new LinkedInProfile($typed)->value);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function typings(): iterable
    {
        yield 'its form' => ['https://www.linkedin.com/in/ada-lovelace'];
        yield 'a trailing slash' => ['https://www.linkedin.com/in/ada-lovelace/'];
        yield 'no scheme' => ['linkedin.com/in/ada-lovelace'];
        yield 'http' => ['http://linkedin.com/in/ada-lovelace'];
        yield 'a query and a fragment' => ['https://www.linkedin.com/in/ada-lovelace?utm_source=share#top'];
        yield 'a country subdomain' => ['https://fr.linkedin.com/in/ada-lovelace'];
        yield 'capitals' => ['HTTPS://WWW.LinkedIn.com/in/Ada-Lovelace'];
        yield 'spaces around' => ['  linkedin.com/in/ada-lovelace  '];
    }

    #[DataProvider('notProfiles')]
    public function testAnAddressThatIsNoProfileIsRefusedOnItsField(string $notAProfile): void
    {
        $this->expectExceptionObject(BusinessRuleViolation::because(sprintf('LinkedIn profile "%s" is not valid', $notAProfile), 'linkedInProfile'));

        new LinkedInProfile($notAProfile);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function notProfiles(): iterable
    {
        yield 'another site' => ['https://example.test/ada'];
        yield 'a site ending like LinkedIn' => ['https://notlinkedin.com/in/ada'];
        yield 'the page of a company' => ['https://www.linkedin.com/company/acme'];
        yield 'no identifier' => ['https://www.linkedin.com/in/'];
        yield 'a page under the profile' => ['https://www.linkedin.com/in/ada/details/skills'];
        yield 'another scheme' => ['ftp://www.linkedin.com/in/ada'];
        yield 'a name only' => ['ada-lovelace'];
    }

    public function testNoProfileIsNone(): void
    {
        $this->assertNull(LinkedInProfile::orNone(null));
        $this->assertNull(LinkedInProfile::orNone(' '));
        $this->assertSame('https://www.linkedin.com/in/ada', (string) LinkedInProfile::orNone('linkedin.com/in/ada'));
    }
}
