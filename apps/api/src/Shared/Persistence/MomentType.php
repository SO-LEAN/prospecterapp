<?php

namespace App\Shared\Persistence;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\DateTimeImmutableType;

/**
 * A moment kept as the same instant whatever the time zone of the server: written and read in UTC, since
 * SQLite stores no offset.
 */
final class MomentType extends DateTimeImmutableType
{
    private const string FORMAT = 'Y-m-d H:i:s.u';

    public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): ?string
    {
        return $value instanceof \DateTimeImmutable ? $value->setTimezone(self::utc())->format(self::FORMAT) : null;
    }

    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): ?\DateTimeImmutable
    {
        return null === $value ? null : new \DateTimeImmutable((string) $value, self::utc());
    }

    private static function utc(): \DateTimeZone
    {
        return new \DateTimeZone('UTC');
    }
}
