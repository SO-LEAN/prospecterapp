<?php

declare(strict_types=1);

namespace Prospecter\Core\SharedKernel\Domain;

/**
 * The words a text is found by (ADR 0050): lower case, without accents, split on anything but letters and
 * digits. A search finds a text when each of its words begins one of the words of the text.
 */
final readonly class SearchTerms
{
    private const string NOT_A_LETTER_NOR_A_DIGIT = '/[^\p{L}\p{N}]+/u';
    private const string ACCENT = '/\p{Mn}+/u';

    /**
     * @param list<string> $words
     */
    private function __construct(public array $words)
    {
    }

    public static function of(?string ...$texts): self
    {
        $words = [];

        foreach ($texts as $text) {
            foreach (preg_split(self::NOT_A_LETTER_NOR_A_DIGIT, self::normalized((string) $text), -1, PREG_SPLIT_NO_EMPTY) ?: [] as $word) {
                $words[$word] = $word;
            }
        }

        return new self(array_values($words));
    }

    public function isEmpty(): bool
    {
        return [] === $this->words;
    }

    public function findBy(self $search): bool
    {
        return array_all($search->words, fn (string $searched): bool => $this->hasAWordBeginningWith($searched));
    }

    /**
     * Every beginning of every word: what a projection indexes so that a search finds it by one lookup per
     * searched word.
     *
     * @return list<string>
     */
    public function beginnings(): array
    {
        $beginnings = [];

        foreach ($this->words as $word) {
            for ($length = 1, $wordLength = mb_strlen($word); $length <= $wordLength; ++$length) {
                $beginning = mb_substr($word, 0, $length);
                $beginnings[$beginning] = $beginning;
            }
        }

        return array_values($beginnings);
    }

    private function hasAWordBeginningWith(string $searched): bool
    {
        return array_any($this->words, fn (string $word): bool => str_starts_with($word, $searched));
    }

    private static function normalized(string $text): string
    {
        return (string) preg_replace(self::ACCENT, '', (string) \Normalizer::normalize(mb_strtolower($text), \Normalizer::FORM_D));
    }
}
