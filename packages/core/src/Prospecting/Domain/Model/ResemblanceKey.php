<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Domain\Model;

use Prospecter\Core\SharedKernel\Domain\SearchKey;

/**
 * What two organizations resembling each other share: perhaps the same organization, entered twice.
 */
final readonly class ResemblanceKey implements \Stringable
{
    private function __construct(public string $value)
    {
    }

    /**
     * Null without a name beside its legal form, or without a city: nothing to compare.
     */
    #[\NoDiscard]
    public static function of(?string $corporateName, ?string $city): ?self
    {
        $name = CorporateNameKey::of($corporateName);
        $place = SearchKey::of($city);

        return null === $name || null === $place ? null : new self(sprintf('%s#%s', $name, $place));
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
