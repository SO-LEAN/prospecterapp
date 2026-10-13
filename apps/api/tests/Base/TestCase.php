<?php

declare(strict_types=1);

namespace Tests\App\Base;

use PHPUnit\Framework\TestCase as PhpUnitTestCase;
use Prophecy\PhpUnit\ProphecyTrait;

abstract class TestCase extends PhpUnitTestCase
{
    use ProphecyTrait;

    private array $prophecies = [];

    private object $object;

    protected function setup(): void
    {
        $this->object = $this->buildTarget();
        $this->initialize();
    }

    protected function target()
    {
        return $this->object;
    }

    /**
     * Arguments injected in object to be tested.
     */
    protected function setupArgs(): array
    {
        return [];
    }

    /**
     * More stuff on target ($this->target()).
     */
    protected function initialize(): void
    {
    }

    protected function prophesy(string $class)
    {
        $this->prophecies[$class] ??= $this->prophesize($class);

        return $this->prophecies[$class];
    }

    protected function getTargetClassName(): string
    {
        return preg_replace('/(^Tests\\\)|(Test$)/', '', static::class);
    }

    private function buildTarget()
    {
        $class = $this->getTargetClassName();

        return $this->instantiateTarget($class);
    }

    private function instantiateTarget(string $class)
    {
        if (0 === count($this->setupArgs())) {
            return new $class();
        }

        $r = new \ReflectionClass($class);

        return $r->newInstanceArgs($this->setupArgs());
    }
}
