<?php

declare(strict_types=1);

namespace Prospecter\Core\SharedKernel\Domain\Link;

/**
 * A tool whose addresses Prospecter recognizes. On the tool's site, Prospecter recognizes the pages that point to a
 * person or an organization, and reads in them the identifier of that person or organization.
 */
enum KnownSource: string
{
    case LinkedInProfile = 'linkedInProfile';
    case LinkedInCompany = 'linkedInCompany';
    case Malt = 'malt';
    case WelcomeToTheJungle = 'welcomeToTheJungle';

    private const string LINKEDIN = '(?:[a-z]{2,3}\.)?linkedin\.com';
    private const string IDENTIFIER = '(?<identifier>[^/?#\s]+)';

    public function designates(): Designated
    {
        return match ($this) {
            self::LinkedInProfile, self::Malt => Designated::Person,
            self::LinkedInCompany, self::WelcomeToTheJungle => Designated::Organization,
        };
    }

    /**
     * The address's identifier, when the address is one of this source's pages.
     *
     * @param string $address in lower case, without its scheme nor `www.`
     */
    public function identifierIn(string $address): ?string
    {
        return 1 === preg_match(sprintf('~^%s/%s%s~', $this->site(), $this->page(), self::IDENTIFIER), $address, $page) ? $page['identifier'] : null;
    }

    /**
     * Whether the address is on this source's site, on one of its pages or not.
     *
     * @param string $address in lower case, without its scheme nor `www.`
     */
    public function hosts(string $address): bool
    {
        return 1 === preg_match(sprintf('~^%s(?:[:/?#]|$)~', $this->site()), $address);
    }

    private function site(): string
    {
        return match ($this) {
            self::LinkedInProfile, self::LinkedInCompany => self::LINKEDIN,
            self::Malt => 'malt\.fr',
            self::WelcomeToTheJungle => 'welcometothejungle\.com',
        };
    }

    private function page(): string
    {
        return match ($this) {
            self::LinkedInProfile => 'in/',
            self::LinkedInCompany => 'company/',
            self::Malt => 'profile/',
            self::WelcomeToTheJungle => '[a-z]{2}/companies/',
        };
    }
}
