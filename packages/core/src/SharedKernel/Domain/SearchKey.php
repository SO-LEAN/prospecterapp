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
    #[\NoDiscard]
    public static function of(?string $text): ?self
    {
        $value = (string) $text
            |> mb_strtolower(...)
            |> (static fn (string $lower): string => (string) \Normalizer::normalize($lower, \Normalizer::FORM_D))
            |> (static fn (string $decomposed): string => (string) preg_replace([self::ACCENT, self::NEITHER_A_LETTER_NOR_A_DIGIT], '', $decomposed));

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
