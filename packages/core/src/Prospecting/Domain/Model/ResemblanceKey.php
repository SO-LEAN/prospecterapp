<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Domain\Model;

use Prospecter\Core\SharedKernel\Domain\Search\SearchKey;

/**
 * What two organizations that resemble each other share. They may be the same organization, entered twice.
 */
final readonly class ResemblanceKey implements \Stringable
{
    private function __construct(public string $value)
    {
    }

    /**
     * Null when the name holds nothing but its legal form, or when there is no city, because there is nothing to
     * compare.
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
