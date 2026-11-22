<?php

declare(strict_types=1);

namespace App\Tests;

use App\Typed;
use PHPUnit\Framework\TestCase;

final class TypedTest extends TestCase
{
    public function testAStringableIsTakenAsAString(): void
    {
        $this->assertSame('body', Typed::string(new class implements \Stringable {
            public function __toString(): string
            {
                return 'body';
            }
        }));
    }

    public function testAnIntegerIsNoString(): void
    {
        $this->expectException(\UnexpectedValueException::class);

        Typed::string(12);
    }

    public function testANumericStringIsNoInteger(): void
    {
        $this->expectException(\UnexpectedValueException::class);

        Typed::int('12');
    }

    public function testTheStringsOfAMapAreAList(): void
    {
        $this->assertSame(['a', 'b'], Typed::strings([3 => 'a', 7 => 'b']));
    }

    public function testAStringIsNoArray(): void
    {
        $this->expectException(\UnexpectedValueException::class);

        Typed::array('contents');
    }

    public function testAnIntegerIsTakenAsItIs(): void
    {
        $this->assertSame(12, Typed::int(12));
    }

    public function testNamedStringsKeepTheirNames(): void
    {
        $this->assertSame(['key' => 'a/b', 'policy' => 'p'], Typed::namedStrings(['key' => 'a/b', 'policy' => 'p']));
    }

    public function testNamedStringsRefuseAValueThatIsNoString(): void
    {
        $this->expectException(\UnexpectedValueException::class);

        Typed::namedStrings(['size' => 12]);
    }
}
