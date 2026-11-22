<?php

declare(strict_types=1);

namespace App\Exchanges\Resource;

/**
 * What an AI noting an exchange is answered (ADR 0166): noted, already noted before, with the exchange noted first, or
 * erased before, with who erased it, never noted again; none an error.
 */
final readonly class NotingResource
{
    public const string NOTED = 'noted';
    public const string ALREADY_NOTED = 'alreadyNoted';
    public const string ERASED_BEFORE = 'erasedBefore';

    private function __construct(
        public string $status,
        public ?ExchangeDetailResource $exchange = null,
        public ?AuthorResource $erasedBy = null,
        public ?string $erasedAt = null,
    ) {
    }

    public static function noted(ExchangeDetailResource $exchange): self
    {
        return new self(self::NOTED, $exchange);
    }

    /**
     * Without the exchange noted first when it was noted at the same moment, unknown to the noting refused.
     */
    public static function alreadyNoted(?ExchangeDetailResource $notedFirst): self
    {
        return new self(self::ALREADY_NOTED, $notedFirst);
    }

    public static function erasedBefore(AuthorResource $by, string $at): self
    {
        return new self(self::ERASED_BEFORE, erasedBy: $by, erasedAt: $at);
    }
}
