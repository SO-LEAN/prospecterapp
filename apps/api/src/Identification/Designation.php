<?php

namespace App\Identification;

/**
 * How a call designates the organization or the person it records (ADR 0177): by its clues, Prospecter identifying
 * them, or, answering a former call to confirm, the one the member retained, or another one, to create.
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
     * As a tool receives it: the id retained wins over saying it is another one.
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
