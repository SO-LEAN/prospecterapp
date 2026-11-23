<?php

declare(strict_types=1);

namespace Tests\Core\Architecture;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Prospecter\Core\Accounts\Domain\Model\Role;

/**
 * A string enum value that is stored or exposed is its case name in lower camelCase (ADR 0190): `VideoCall` becomes
 * `videoCall`. The roles keep Symfony's `ROLE_*`.
 */
class BackedEnumValuesTest extends TestCase
{
    private const array SYMFONY_CASING = [Role::class];

    #[DataProvider('backedEnumCases')]
    public function testAValueIsItsCaseNameInLowerCamelCase(\BackedEnum $case): void
    {
        $this->assertSame(lcfirst($case->name), $case->value);
    }

    /**
     * @return iterable<string, array{\BackedEnum}>
     */
    public static function backedEnumCases(): iterable
    {
        foreach (self::backedEnums() as $enum) {
            foreach ($enum::cases() as $case) {
                yield $enum.'::'.$case->name => [$case];
            }
        }
    }

    /**
     * @return list<class-string<\BackedEnum>>
     */
    private static function backedEnums(): array
    {
        $root = dirname(__DIR__, 2).'/src/';
        $enums = [];

        foreach (FilesUnder::the($root) as $path) {
            $class = 'Prospecter\Core\\'.str_replace(['/', '.php'], ['\\', ''], substr($path, strlen($root)));

            if (self::isBackedByText($class) && !in_array($class, self::SYMFONY_CASING, true)) {
                $enums[] = $class;
            }
        }

        sort($enums);

        return $enums;
    }

    /**
     * @phpstan-assert-if-true class-string<\BackedEnum> $class
     */
    private static function isBackedByText(string $class): bool
    {
        return enum_exists($class) && 'string' === (string) new \ReflectionEnum($class)->getBackingType();
    }
}
