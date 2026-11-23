<?php

declare(strict_types=1);

namespace App\Identification;

/**
 * How a call designates the organization or the person it records (ADR 0177). Usually the call gives clues, and
 * Prospecter identifies them. A call that answers an earlier one to confirm gives the candidate the member picked,
 * or says it is another one, to create.
 */
final readonly class Designation
{
    private function __construct(public ?string $retained, private bool $anotherOne)
    {
    }

    public static function byItsClues(): self
    {
        return new self(null, false);
    }

    public static function retained(string $id): self
    {
        return new self($id, false);
    }

    public static function anotherOne(): self
    {
        return new self(null, true);
    }

    /**
     * Builds the designation as a tool receives it. A picked id wins over saying it is another one.
     */
    public static function of(?string $retained, bool $anotherOne): self
    {
        return match (true) {
            null !== $retained => self::retained($retained),
            $anotherOne => self::anotherOne(),
            default => self::byItsClues(),
        };
    }

    public function isAnotherOne(): bool
    {
        return $this->anotherOne;
    }
}
