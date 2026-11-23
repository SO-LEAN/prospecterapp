<?php

declare(strict_types=1);

namespace Tests\App\Architecture;

use App\Shared\Typed;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/**
 * The tests of the API re-testing a rule its core proves form the group core-rule, which a branch's check does not
 * run. The group does not grow: a new rule is tested in the core. A test leaving the group lowers CEILING.
 */
class CoreRuleGroupDoesNotGrowTest extends TestCase
{
    private const string CORE_RULE = 'core-rule';

    private const int CEILING = 33;

    public function testTheGroupKeepsItsCeiling(): void
    {
        $this->assertSame(self::CEILING, count($this->methodsInTheGroup()), 'A new test of the API does not re-test a rule of the core; a test leaving the group lowers CEILING');
    }

    public function testNoClassIsInTheGroupAsAWhole(): void
    {
        $classesInTheGroup = array_filter($this->testClasses(), self::isInTheGroup(...));

        $this->assertSame([], array_values(array_map(static fn (\ReflectionClass $class): string => $class->getName(), $classesInTheGroup)), 'The group core-rule is on test methods only: a class keeps its nominal cases out of it');
    }

    /**
     * @return list<string>
     */
    private function methodsInTheGroup(): array
    {
        $methods = [];

        foreach ($this->testClasses() as $class) {
            foreach ($class->getMethods() as $method) {
                if ($method->getDeclaringClass()->getName() === $class->getName() && self::isInTheGroup($method)) {
                    $methods[] = $class->getName().'::'.$method->getName();
                }
            }
        }

        return $methods;
    }

    /**
     * @param \ReflectionClass<object>|\ReflectionMethod $testOrClass
     */
    private static function isInTheGroup(\ReflectionClass|\ReflectionMethod $testOrClass): bool
    {
        return array_any(
            $testOrClass->getAttributes(Group::class),
            static fn (\ReflectionAttribute $group): bool => self::CORE_RULE === $group->newInstance()->name(),
        );
    }

    /**
     * @return list<\ReflectionClass<object>>
     */
    private function testClasses(): array
    {
        $root = dirname(__DIR__).'/';
        $classes = [];

        foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS)) as $file) {
            $path = Typed::object($file, \SplFileInfo::class)->getPathname();
            $class = 'Tests\App\\'.str_replace(['/', '.php'], ['\\', ''], substr($path, strlen($root)));

            if (str_ends_with($path, 'Test.php') && class_exists($class)) {
                $classes[] = new \ReflectionClass($class);
            }
        }

        return $classes;
    }
}
