<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Domain\Model;

use Prospecter\Core\SharedKernel\Domain\SearchKey;

/**
 * What two names of the same organization share: the name without case, accents, punctuation, spaces or legal form.
 */
final readonly class CorporateNameKey implements \Stringable
{
    /**
     * French companies' legal forms. A word of the name that is one of them, ignoring its dots, does not count,
     * wherever it stands.
     */
    private const array LEGAL_FORMS = ['sa', 'sas', 'sasu', 'sarl', 'sarlu', 'eurl', 'snc', 'sci', 'scop', 'sca', 'scs', 'scp', 'scm', 'selarl', 'selas', 'selasu', 'selafa', 'gie', 'ei', 'eirl', 'sem', 'spl'];

    private function __construct(public string $value)
    {
    }

    /**
     * Null when the name holds nothing but its legal form, because there is nothing to compare.
     */
    #[\NoDiscard]
    public static function of(?string $corporateName): ?self
    {
        $words = array_map(static fn (string $word): string => (string) SearchKey::of($word), preg_split('/\s+/u', (string) $corporateName) ?: []);
        $name = implode('', array_diff($words, self::LEGAL_FORMS));

        return '' === $name ? null : new self($name);
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
