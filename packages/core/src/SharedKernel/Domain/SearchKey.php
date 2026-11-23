<?php

declare(strict_types=1);

namespace Prospecter\Core\SharedKernel\Domain;

/**
 * A text in the form the search compares (ADR 0071): lower case, without accents, with only its letters and
 * digits. So "de La Fontaine" begins with "De la FO" (`delafontaine`, `delafo`).
 */
final readonly class SearchKey implements \Stringable
{
    private const string IGNORED_BY_THE_SEARCH = '/[^\p{L}\p{N}]+/u';

    private function __construct(public string $value)
    {
    }

    /**
     * Null when the text has no letter and no digit: there is nothing to compare.
     */
    #[\NoDiscard]
    public static function of(?string $text): ?self
    {
        $value = (string) $text
            |> mb_strtolower(...)
            |> Decomposed::of(...)
            |> (static fn (string $decomposed): string => (string) preg_replace(self::IGNORED_BY_THE_SEARCH, '', $decomposed));

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
