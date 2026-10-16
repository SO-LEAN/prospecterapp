<?php

declare(strict_types=1);

namespace Tests\Core\Architecture;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * A class of a domain is final: an aggregate or a value object is not extended, and nothing of the
 * persistence needs to (ADR 0048, the DynamoDB adapters restore an aggregate without a proxy). Only what the
 * domain extends itself is open: the abstract bases of the events, the kinds of refusal.
 */
class DomainClassesAreFinalTest extends TestCase
{
    #[DataProvider('domainClasses')]
    public function testAClassOfTheDomainIsFinalUnlessTheDomainExtendsIt(string $class): void
    {
        $extended = array_filter(array_column(iterator_to_array(self::domainClasses()), 0), static fn (string $other): bool => is_subclass_of($other, $class));

        $this->assertTrue(new \ReflectionClass($class)->isFinal() || [] !== $extended, sprintf('%s is not final, and nothing of the domain extends it', $class));
    }

    /**
     * @return iterable<string, array{class-string}>
     */
    public static function domainClasses(): iterable
    {
        $root = dirname(__DIR__, 2).'/src/';

        foreach (glob($root.'*/Domain', GLOB_ONLYDIR) ?: [] as $domain) {
            foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($domain, \FilesystemIterator::SKIP_DOTS)) as $file) {
                $class = 'Prospecter\Core\\'.str_replace(['/', '.php'], ['\\', ''], substr($file->getPathname(), strlen($root)));

                if (class_exists($class)) {
                    yield $class => [$class];
                }
            }
        }
    }
}
