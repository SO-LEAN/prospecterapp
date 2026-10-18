<?php

declare(strict_types=1);

namespace Tests\Core\Domain;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Prospecter\Core\SharedKernel\Domain\Exception\BusinessRuleViolation;
use Prospecter\Core\SharedKernel\Domain\PhoneNumber;

class PhoneNumberTest extends TestCase
{
    #[DataProvider('typings')]
    public function testANumberHasOneValueHoweverItWasTyped(string $typed, string $value, string $international): void
    {
        $number = PhoneNumber::of($typed);

        $this->assertSame($value, $number->value);
        $this->assertSame($international, $number->international());
    }

    /**
     * @return iterable<string, array{string, string, string}>
     */
    public static function typings(): iterable
    {
        yield 'French, national, with spaces' => ['03 88 11 22 33', '+33388112233', '+33 3 88 11 22 33'];
        yield 'French, with dots' => ['06.12.34.56.78', '+33612345678', '+33 6 12 34 56 78'];
        yield 'French, international' => ['+33 3 88 11 22 33', '+33388112233', '+33 3 88 11 22 33'];
        yield 'French, dialed from abroad' => ['0033388112233', '+33388112233', '+33 3 88 11 22 33'];
        yield 'German, international' => ['+49 30 1234567', '+49301234567', '+49 30 1234567'];
    }

    #[DataProvider('invalids')]
    public function testAnInvalidNumberIsRefused(string $typed): void
    {
        $this->expectException(BusinessRuleViolation::class);

        PhoneNumber::of($typed);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function invalids(): iterable
    {
        yield 'too short' => ['12'];
        yield 'letters' => ['call me'];
        yield 'a French number missing a digit' => ['03 88 11 22 3'];
    }

    public function testNothingTypedIsNoNumber(): void
    {
        $this->assertNull(PhoneNumber::orNone(null));
        $this->assertNull(PhoneNumber::orNone(' '));
    }
}
