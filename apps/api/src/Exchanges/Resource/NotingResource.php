<?php

declare(strict_types=1);

namespace App\Exchanges\Resource;

/**
 * What an AI that logs an exchange gets as an answer (ADR 0166). The exchange is either logged, already logged before
 * with the exchange logged first, or erased before with who erased it. An erased exchange is never logged again. None
 * of these answers is an error.
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
     * The exchange logged first is missing when it was logged at the same moment. The refused logging does not know it.
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
