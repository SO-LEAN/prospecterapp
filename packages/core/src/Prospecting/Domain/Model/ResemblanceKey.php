<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Domain\Model;

use Prospecter\Core\SharedKernel\Domain\SearchKey;

/**
 * What two organizations resembling each other share: perhaps the same organization, entered twice.
 */
final readonly class ResemblanceKey implements \Stringable
{
    /**
     * The legal forms of the French companies, as a name ends or begins with them, its points aside.
     */
    private const array LEGAL_FORMS = ['sa', 'sas', 'sasu', 'sarl', 'sarlu', 'eurl', 'snc', 'sci', 'scop', 'sca', 'scs', 'scp', 'scm', 'selarl', 'selas', 'selasu', 'selafa', 'gie', 'ei', 'eirl', 'sem', 'spl'];

    private function __construct(public string $value)
    {
    }

    /**
     * Null without a name beside its legal form, or without a city: nothing to compare.
     */
    #[\NoDiscard]
    public static function of(?string $corporateName, ?string $city): ?self
    {
        $name = self::nameWithoutItsLegalForm((string) $corporateName);
        $place = SearchKey::of($city);

        return '' === $name || null === $place ? null : new self(sprintf('%s#%s', $name, $place));
    }

    private static function nameWithoutItsLegalForm(string $corporateName): string
    {
        $words = array_map(static fn (string $word): string => (string) SearchKey::of($word), preg_split('/\s+/u', $corporateName) ?: []);

        return implode('', array_diff($words, self::LEGAL_FORMS));
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
