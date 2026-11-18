<?php

declare(strict_types=1);

namespace Prospecter\Core\SharedKernel\Domain;

use Prospecter\Core\SharedKernel\Domain\Exception\InvalidSiret;

/**
 * The number INSEE gives an establishment: its SIREN, then five digits, keyed by Luhn; the establishments of La Poste,
 * too many for it, are keyed by the sum of their digits, a multiple of 5.
 */
final readonly class Siret
{
    private const string SIREN_OF_LA_POSTE = '356000000';

    public string $value;

    public function __construct(string $typed)
    {
        $value = preg_replace('/\s+/', '', $typed);

        if (!self::isFourteenDigits($value) || !self::hasItsKey($value)) {
            throw InvalidSiret::of($typed);
        }
        $this->value = $value;
    }

    private static function isFourteenDigits(string $value): bool
    {
        return 1 === preg_match('/^\d{14}$/', $value);
    }

    private static function hasItsKey(string $digits): bool
    {
        return self::hasItsLuhnKey($digits) || (str_starts_with($digits, self::SIREN_OF_LA_POSTE) && 0 === array_sum(str_split($digits)) % 5);
    }

    private static function hasItsLuhnKey(string $digits): bool
    {
        $sum = 0;

        foreach (array_reverse(str_split($digits)) as $position => $digit) {
            $weighted = (int) $digit * (1 === $position % 2 ? 2 : 1);
            $sum += $weighted > 9 ? $weighted - 9 : $weighted;
        }

        return 0 === $sum % 10;
    }
}
