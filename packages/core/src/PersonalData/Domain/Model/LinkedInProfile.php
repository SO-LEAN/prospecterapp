<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Domain\Model;

use Prospecter\Core\PersonalData\Domain\Exception\InvalidLinkedInProfile;

/**
 * The address of the profile of a person on LinkedIn, kept in one form however it was typed
 * (`https://www.linkedin.com/in/<identifier>`, the identifier in lower case, without what follows it), so
 * that one profile has one value.
 */
final readonly class LinkedInProfile implements \Stringable
{
    private const string PROFILE = '~^(?:https?://)?(?:[a-z]{2,3}\.)?linkedin\.com/in/(?<identifier>[^/?#\s]+)/?(?:[?#].*)?$~i';
    private const string ONE_FORM = 'https://www.linkedin.com/in/%s';

    public string $value;

    /**
     * @throws InvalidLinkedInProfile
     */
    public function __construct(string $value)
    {
        if (1 !== preg_match(self::PROFILE, trim($value), $profile)) {
            throw InvalidLinkedInProfile::of($value);
        }
        $this->value = sprintf(self::ONE_FORM, mb_strtolower($profile['identifier']));
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
