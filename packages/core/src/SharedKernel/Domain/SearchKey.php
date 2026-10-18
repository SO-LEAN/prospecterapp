<?php

declare(strict_types=1);

namespace Prospecter\Core\SharedKernel\Domain;

/**
 * A text as the search compares it (ADR 0071): lower case, without accents, and only its letters and digits,
 * so that "De la FO" begins "de La Fontaine" (`delafo`, `delafontaine`).
 */
final readonly class SearchKey implements \Stringable
{
    private const string NEITHER_A_LETTER_NOR_A_DIGIT = '/[^\p{L}\p{N}]+/u';
    private const string ACCENT = '/\p{Mn}+/u';

    private function __construct(public string $value)
    {
    }

    /**
     * Null when the text has no letter nor digit: nothing to compare.
     */
    public static function of(?string $text): ?self
    {
        $value = (string) preg_replace([self::ACCENT, self::NEITHER_A_LETTER_NOR_A_DIGIT], '', (string) \Normalizer::normalize(mb_strtolower((string) $text), \Normalizer::FORM_D));

        return '' === $value ? null : new self($value);
    }

    public function beginsWith(self $search): bool
    {
        return str_starts_with($this->value, $search->value);
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
