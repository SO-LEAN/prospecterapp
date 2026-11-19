<?php

declare(strict_types=1);

namespace Tests\Core\Domain;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Prospecter\Core\SharedKernel\Domain\Exception\InvalidSiret;
use Prospecter\Core\SharedKernel\Domain\Siret;

class SiretTest extends TestCase
{
    public function testASiretIsItsFourteenDigitsHoweverItWasTyped(): void
    {
        $this->assertSame('55210055400013', new Siret('552 100 554 00013')->value);
    }

    #[DataProvider('establishments')]
    public function testTheSiretOfAnEstablishmentIsAccepted(string $siret): void
    {
        $this->assertSame($siret, new Siret($siret)->value);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function establishments(): iterable
    {
        yield 'two of its digits doubled above 9' => ['55210055400013'];
        yield 'three of its digits doubled above 9' => ['30613890002664'];
        yield 'one of its digits doubled above 9' => ['44264635200082'];
    }

    public function testARefusedSiretNamesWhatWasTyped(): void
    {
        try {
            new Siret('552 100 554 00014');
            $this->fail('The SIRET was expected refused');
        } catch (InvalidSiret $refusal) {
            $this->assertSame(['siret' => '552 100 554 00014'], $refusal->context);
        }
    }

    public function testASiretWhoseKeyIsWrongIsRefused(): void
    {
        $this->expectException(InvalidSiret::class);

        new Siret('55210055400014');
    }

    public function testAnEstablishmentOfLaPosteHasTheSumOfItsDigitsAsItsKey(): void
    {
        $this->assertSame('35600000049837', new Siret('35600000049837')->value);
    }

    public function testOnlyLaPosteHasTheSumOfItsDigitsAsItsKey(): void
    {
        $this->expectException(InvalidSiret::class);

        new Siret('55210055400017');
    }

    #[DataProvider('notFourteenDigits')]
    public function testATextOfOtherThanFourteenDigitsIsRefused(string $typed): void
    {
        $this->expectException(InvalidSiret::class);

        new Siret($typed);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function notFourteenDigits(): iterable
    {
        yield 'a SIREN, nine digits' => ['552100554'];
        yield 'fifteen digits' => ['552100554000130'];
        yield 'fifteen digits, a valid key with the zero before them' => ['055210055400013'];
        yield 'letters' => ['ABCDEFGHIJKLMN'];
        yield 'nothing' => ['   '];
    }
}
