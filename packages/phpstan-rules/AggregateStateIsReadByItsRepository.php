<?php

declare(strict_types=1);

namespace Prospecter\PHPStan;

use PHPStan\Reflection\PropertyReflection;
use PHPStan\Rules\Properties\ReadWritePropertiesExtension;

/**
 * An aggregate keeps the state that its rules do not read. Its repository writes that state by reflection, its
 * retrievers read it as read models, and the aggregate no longer exposes it through getters (ADR 0028, 0048, 0088).
 * So the rule treats every property of an aggregate (a class that releases its events) as read, whatever the
 * aggregate itself reads.
 */
final class AggregateStateIsReadByItsRepository implements ReadWritePropertiesExtension
{
    private const string AGGREGATE = 'Prospecter\\Core\\SharedKernel\\Domain\\ReleasesEvents';

    public function isAlwaysRead(PropertyReflection $property, string $propertyName): bool
    {
        return $property->getDeclaringClass()->implementsInterface(self::AGGREGATE);
    }

    public function isAlwaysWritten(PropertyReflection $property, string $propertyName): bool
    {
        return false;
    }

    public function isInitialized(PropertyReflection $property, string $propertyName): bool
    {
        return false;
    }
}
