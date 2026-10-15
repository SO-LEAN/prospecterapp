<?php

namespace App\Accounts\Persistence;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\JsonType;
use Prospecter\Core\Accounts\Domain\Role;

final class RolesType extends JsonType
{
    public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): string
    {
        return (string) parent::convertToDatabaseValue(array_map(static fn (Role $role): string => $role->value, $value), $platform);
    }

    /**
     * @return list<Role>
     */
    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): array
    {
        return array_map(Role::from(...), parent::convertToPHPValue($value, $platform) ?? []);
    }
}
