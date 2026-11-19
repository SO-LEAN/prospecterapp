<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Domain\Model;

use Prospecter\Core\Prospecting\Domain\Exception\InvalidLinkedInPage;

/**
 * The page of a company on LinkedIn, kept in one form however it was typed
 * (`https://www.linkedin.com/company/<identifier>`, the identifier in lower case, without the page under it), so that
 * one page has one value.
 */
final readonly class LinkedInPage implements \Stringable
{
    private const string PAGE = '~^(?:https?://)?(?:[a-z]{2,3}\.)?linkedin\.com/company/(?<identifier>[^/?#\s]+)~i';
    private const string ONE_FORM = 'https://www.linkedin.com/company/%s';

    public string $value;

    /**
     * @throws InvalidLinkedInPage
     */
    public function __construct(string $value)
    {
        if (1 !== preg_match(self::PAGE, trim($value), $page)) {
            throw InvalidLinkedInPage::of($value);
        }
        $this->value = sprintf(self::ONE_FORM, mb_strtolower($page['identifier']));
    }

    public static function orNone(?string $value): ?self
    {
        return null === $value || '' === trim($value) ? null : new self($value);
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
