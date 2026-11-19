<?php

declare(strict_types=1);

namespace Tests\Core\Domain;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Prospecter\Core\Prospecting\Domain\Model\LinkedInPage;
use Prospecter\Core\SharedKernel\Domain\Exception\BusinessRuleViolation;

class LinkedInPageTest extends TestCase
{
    #[DataProvider('typings')]
    public function testAPageIsKeptInOneFormWhateverItsTyping(string $typed): void
    {
        $this->assertSame('https://www.linkedin.com/company/acme-corp', new LinkedInPage($typed)->value);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function typings(): iterable
    {
        yield 'its form' => ['https://www.linkedin.com/company/acme-corp'];
        yield 'a page under it' => ['https://www.linkedin.com/company/acme-corp/about/'];
        yield 'capitals' => ['HTTPS://WWW.LinkedIn.com/company/ACME-Corp'];
        yield 'http and a country subdomain' => ['http://fr.linkedin.com/company/acme-corp'];
        yield 'no scheme and spaces around' => ['  linkedin.com/company/acme-corp  '];
    }

    #[DataProvider('notPages')]
    public function testAnAddressThatIsNoPageOfACompanyIsRefusedOnItsField(string $notAPage): void
    {
        $this->expectExceptionObject(BusinessRuleViolation::because(sprintf('"%s" is not the LinkedIn page of a company', $notAPage), 'linkedInPage'));

        new LinkedInPage($notAPage);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function notPages(): iterable
    {
        yield 'the profile of a person' => ['https://www.linkedin.com/in/ada-lovelace'];
        yield 'a site ending like LinkedIn' => ['https://notlinkedin.com/company/acme'];
        yield 'the page of a school' => ['https://www.linkedin.com/school/acme'];
        yield 'no identifier' => ['https://www.linkedin.com/company/'];
    }

    public function testNothingTypedIsNoPage(): void
    {
        $this->assertNull(LinkedInPage::orNone(' '));
    }
}
