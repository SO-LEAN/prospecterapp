<?php

declare(strict_types=1);

namespace Tests\Core\Architecture;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Prospecter\Core\SharedKernel\Application\Handler\Policy;

/**
 * A policy only turns an event into a command (ADR 0202). It is a final readonly class of its context's
 * `Application/Policy`, with no dependency, so that it is tested alone, without a double.
 */
class PoliciesDependOnNothingTest extends TestCase
{
    /**
     * @param class-string $class
     */
    #[DataProvider('classesOfThePolicyFolders')]
    public function testAClassOfAPolicyFolderIsAPolicy(string $class): void
    {
        $this->assertTrue(is_a($class, Policy::class, true), $class.' is no policy');
    }

    /**
     * @param class-string $class
     */
    #[DataProvider('classesOfThePolicyFolders')]
    public function testAPolicyIsFinalReadonlyAndTakesNothingToBuild(string $class): void
    {
        $policy = new \ReflectionClass($class);

        $this->assertTrue($policy->isFinal() && $policy->isReadOnly(), $class.' is not final readonly');
        $this->assertNull($policy->getConstructor(), $class.' has a constructor');
    }

    /**
     * @return iterable<string, array{class-string}>
     */
    public static function classesOfThePolicyFolders(): iterable
    {
        $root = dirname(__DIR__, 2).'/src/';

        foreach (glob($root.'*/Application/Policy/*.php') ?: [] as $file) {
            $class = 'Prospecter\\Core\\'.str_replace(['/', '.php'], ['\\', ''], substr($file, strlen($root)));

            if (class_exists($class)) {
                yield $class => [$class];
            }
        }
    }
}
