<?php

namespace App\Shared\Persistence;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\JsonType;
use Prospecter\Core\SharedKernel\Domain\PersonId;

/**
 * A list of person identities (the contacts of an organization), kept as a JSON array of strings.
 */
final class PersonIdsType extends JsonType
{
    public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): string
    {
        return parent::convertToDatabaseValue(array_map(static fn (PersonId $id): string => $id->value, $value ?? []), $platform);
    }

    /**
     * @return list<PersonId>
     */
    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): array
    {
        return array_map(static fn (string $id): PersonId => new PersonId($id), parent::convertToPHPValue($value, $platform) ?? []);
    }
}
