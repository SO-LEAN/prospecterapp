<?php

declare(strict_types=1);

namespace Prospecter\Core\SharedKernel\Domain;

use libphonenumber\NumberParseException;
use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberUtil;
use Prospecter\Core\SharedKernel\Domain\Exception\InvalidPhoneNumber;

/**
 * A phone number as the world dials it (ADR 0072): kept in E.164 (`+33388112233`), read from the national
 * format of France or from the international one, written in the international format (`+33 3 88 11 22 33`).
 * One number has one value however it was typed, so that a call is recognized by its number.
 */
final readonly class PhoneNumber implements \Stringable
{
    private const string DIALED_FROM = 'FR';

    private function __construct(public string $value)
    {
    }

    /**
     * @throws InvalidPhoneNumber
     */
    public static function of(string $number): self
    {
        $numbers = PhoneNumberUtil::getInstance();

        try {
            $parsed = $numbers->parse($number, self::DIALED_FROM);
        } catch (NumberParseException) {
            throw InvalidPhoneNumber::of($number);
        }

        if (!$numbers->isValidNumber($parsed)) {
            throw InvalidPhoneNumber::of($number);
        }

        return new self($numbers->format($parsed, PhoneNumberFormat::E164));
    }

    public static function orNone(?string $number): ?self
    {
        return null === $number || '' === trim($number) ? null : self::of($number);
    }

    /**
     * A number kept before the numbers were checked, and no number, is written as it was typed.
     */
    public function international(): string
    {
        $numbers = PhoneNumberUtil::getInstance();

        try {
            return $numbers->format($numbers->parse($this->value, self::DIALED_FROM), PhoneNumberFormat::INTERNATIONAL);
        } catch (NumberParseException) {
            return $this->value;
        }
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
