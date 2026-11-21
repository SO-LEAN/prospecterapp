<?php

declare(strict_types=1);

namespace Tests\Core\Support;

use PHPUnit\Framework\TestCase;
use Tests\Core\Double\State;

final class StateTest extends TestCase
{
    private const string AT = '2026-11-21 10:00';

    public function testAnObjectIsReadAsItsClass(): void
    {
        $at = new \DateTimeImmutable(self::AT);

        $this->assertSame($at, new State(['at' => $at])->object('at', \DateTimeImmutable::class));
    }

    public function testAnObjectOfAnotherClassIsRefused(): void
    {
        $this->expectExceptionObject(new \UnexpectedValueException('at is not a DateTimeImmutable: DateTime'));

        new State(['at' => new \DateTime(self::AT)])->object('at', \DateTimeImmutable::class);
    }

    public function testAPropertyTheAggregateDoesNotHaveIsRefused(): void
    {
        $this->expectExceptionObject(new \UnexpectedValueException('The aggregate has no property "on"'));

        new State(['at' => null])->objectOrNull('on', \DateTimeImmutable::class);
    }

    public function testAListIsReadAsTheClassOfEachItem(): void
    {
        $at = new \DateTimeImmutable(self::AT);

        $this->assertSame([$at], new State(['moments' => [$at]])->listOf('moments', \DateTimeImmutable::class));
    }

    public function testAListHoldingAnotherClassIsRefused(): void
    {
        $this->expectExceptionObject(new \UnexpectedValueException('moments is not a list of DateTimeImmutable: array'));

        new State(['moments' => [new \DateTime(self::AT)]])->listOf('moments', \DateTimeImmutable::class);
    }

    public function testAFlagIsReadAsABoolean(): void
    {
        $this->assertTrue(new State(['done' => true])->bool('done'));
    }

    public function testAFlagHoldingAnythingElseIsRefused(): void
    {
        $this->expectExceptionObject(new \UnexpectedValueException('done is not a bool: int'));

        new State(['done' => 1])->bool('done');
    }

    public function testATextIsReadAsAString(): void
    {
        $this->assertSame('Envoyer mon CV', new State(['text' => 'Envoyer mon CV'])->string('text'));
    }

    public function testATextHoldingAnythingElseIsRefused(): void
    {
        $this->expectExceptionObject(new \UnexpectedValueException('text is not a string: null'));

        new State(['text' => null])->string('text');
    }

    public function testAnObjectMayBeMissing(): void
    {
        $this->assertNull(new State(['at' => null])->objectOrNull('at', \DateTimeImmutable::class));
    }
}
