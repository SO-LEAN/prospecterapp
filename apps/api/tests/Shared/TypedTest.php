<?php

declare(strict_types=1);

namespace Tests\App\Shared;

use App\Shared\Typed;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

#[Group('unit')]
final class TypedTest extends TestCase
{
    public function testAnIntegerIsNoString(): void
    {
        $this->expectException(\UnexpectedValueException::class);

        Typed::string(12);
    }

    public function testNullIsNoStringButNone(): void
    {
        $this->assertNull(Typed::stringOrNull(null));
    }

    public function testANumericStringIsNoInteger(): void
    {
        $this->expectException(\UnexpectedValueException::class);

        Typed::int('12');
    }

    public function testAnIntegerIsTakenAsItIs(): void
    {
        $this->assertSame(12, Typed::int(12));
    }

    public function testAFloatIsTakenAsItIs(): void
    {
        $this->assertSame(2.5, Typed::float(2.5));
    }

    public function testABooleanIsTakenAsItIs(): void
    {
        $this->assertFalse(Typed::bool(false));
    }

    public function testNullIsNoIntegerButNone(): void
    {
        $this->assertNull(Typed::intOrNull(null));
    }

    public function testAWholeNumberIsTakenAsAFloat(): void
    {
        $this->assertSame(2.0, Typed::float(2));
    }

    public function testANumericStringIsNoFloat(): void
    {
        $this->expectException(\UnexpectedValueException::class);

        Typed::float('2.5');
    }

    public function testAStringIsNoBoolean(): void
    {
        $this->expectException(\UnexpectedValueException::class);

        Typed::bool('true');
    }

    public function testAStringIsNoArray(): void
    {
        $this->expectException(\UnexpectedValueException::class);

        Typed::array('contents');
    }

    public function testTheStringsOfAMapAreAList(): void
    {
        $this->assertSame(['a', 'b'], Typed::strings([3 => 'a', 7 => 'b']));
    }

    public function testADocumentKeepsItsFieldsByName(): void
    {
        $this->assertSame(['name' => 'Ada', 'age' => 36], Typed::document(['name' => 'Ada', 'age' => 36]));
    }

    public function testAStringIsNoDocumentOfAList(): void
    {
        $this->expectException(\UnexpectedValueException::class);

        Typed::documents([['name' => 'Ada'], 'Grace']);
    }

    public function testNamedStringsRefuseAValueThatIsNoString(): void
    {
        $this->expectException(\UnexpectedValueException::class);

        Typed::namedStrings(['size' => 12]);
    }

    public function testAnObjectOfAnotherClassIsRefused(): void
    {
        $this->expectException(\UnexpectedValueException::class);

        Typed::object(new \stdClass(), \DateTimeImmutable::class);
    }

    public function testNullIsNoObjectButNone(): void
    {
        $this->assertNull(Typed::objectOrNull(null, \DateTimeImmutable::class));
    }

    public function testAnObjectOfItsClassIsTakenAsItIs(): void
    {
        $moment = new \DateTimeImmutable('2026-11-22');

        $this->assertSame($moment, Typed::object($moment, \DateTimeImmutable::class));
    }

    public function testTheObjectsOfAMapAreAList(): void
    {
        $moment = new \DateTimeImmutable('2026-11-22');

        $this->assertSame([$moment], Typed::objects([3 => $moment], \DateTimeImmutable::class));
    }
}
