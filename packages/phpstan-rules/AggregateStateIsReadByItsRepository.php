<?php

declare(strict_types=1);

namespace Prospecter\PHPStan;

use PHPStan\Reflection\PropertyReflection;
use PHPStan\Rules\Properties\ReadWritePropertiesExtension;

/**
 * An aggregate keeps the state its rules do not read: its repository writes it by reflection, its retrievers
 * read it as read models, and the aggregate no longer exposes it by getters (ADR 0028, 0048, 0088). A property of an
 * aggregate (a class releasing its events) is read, whatever the aggregate reads of it.
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
