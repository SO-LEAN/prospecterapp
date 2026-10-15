<?php

namespace App\Shared\Persistence;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\GuidType;
use Prospecter\Core\SharedKernel\Domain\Uuid;

/**
 * An identity of the core stored as a guid column.
 */
abstract class IdentityType extends GuidType
{
    /**
     * @return class-string<Uuid>
     */
    abstract protected function identityClass(): string;

    public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): ?string
    {
        return $value instanceof Uuid ? $value->value : $value;
    }

    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): ?Uuid
    {
        return null === $value ? null : new ($this->identityClass())((string) $value);
    }
}
