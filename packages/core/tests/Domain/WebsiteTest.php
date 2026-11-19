<?php

declare(strict_types=1);

namespace Tests\Core\Domain;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Prospecter\Core\Prospecting\Domain\Model\Website;
use Prospecter\Core\SharedKernel\Domain\Exception\BusinessRuleViolation;

class WebsiteTest extends TestCase
{
    #[DataProvider('typings')]
    public function testASiteIsKeptAsItsOrigin(string $typed, string $kept): void
    {
        $this->assertSame($kept, new Website($typed)->value);
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function typings(): iterable
    {
        yield 'its origin' => ['https://www.acme.fr', 'https://www.acme.fr'];
        yield 'a page of it' => ['https://www.acme.fr/contact?from=mail#form', 'https://www.acme.fr'];
        yield 'capitals' => ['HTTPS://www.Acme.fr/Contact', 'https://www.acme.fr'];
        yield 'no scheme and spaces around' => ['  acme.fr/blog  ', 'https://acme.fr'];
    }

    #[DataProvider('domains')]
    public function testASiteIsRecognizedByItsDomain(string $typed, string $domain): void
    {
        $this->assertSame($domain, new Website($typed)->domain());
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function domains(): iterable
    {
        yield 'its host' => ['https://acme.fr/blog', 'acme.fr'];
        yield 'its host without www' => ['HTTPS://www.Acme.fr/contact', 'acme.fr'];
        yield 'a subdomain other than www' => ['https://shop.acme.fr', 'shop.acme.fr'];
    }

    #[DataProvider('notSites')]
    public function testAnAddressThatIsNoSiteIsRefusedOnItsField(string $notASite): void
    {
        $this->expectExceptionObject(BusinessRuleViolation::because(sprintf('"%s" is not the address of a website', $notASite), 'website'));

        new Website($notASite);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function notSites(): iterable
    {
        yield 'a word' => ['acme'];
        yield 'another scheme' => ['ftp://acme.fr'];
        yield 'an email' => ['contact@acme.fr'];
    }

    public function testNothingTypedIsNoSite(): void
    {
        $this->assertNull(Website::orNone(' '));
    }
}
