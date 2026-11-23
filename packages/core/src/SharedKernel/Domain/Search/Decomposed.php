<?php

declare(strict_types=1);

namespace Prospecter\Core\SharedKernel\Domain\Search;

/**
 * A text whose accented letters are split into a letter and its accent (Unicode NFD), so that the accent can be
 * removed. It is empty when the text is not valid UTF-8.
 */
final class Decomposed
{
    public static function of(string $text): string
    {
        $decomposed = \Normalizer::normalize($text, \Normalizer::FORM_D);

        return is_string($decomposed) ? $decomposed : '';
    }
}
