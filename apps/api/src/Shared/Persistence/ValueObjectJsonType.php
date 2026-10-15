<?php

namespace App\Shared\Persistence;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\JsonType;

/**
 * A value object of the core stored as one JSON column: Doctrine cannot make an embeddable null, a
 * column can.
 *
 * @template T of object
 */
abstract class ValueObjectJsonType extends JsonType
{
    /**
     * @param T $value
     *
     * @return array<string, mixed>
     */
    abstract protected function toArray(object $value): array;

    /**
     * @param array<string, mixed> $data
     *
     * @return T
     */
    abstract protected function fromArray(array $data): object;

    public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): ?string
    {
        return parent::convertToDatabaseValue(null === $value ? null : $this->toArray($value), $platform);
    }

    /**
     * @return T|null
     */
    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): ?object
    {
        $data = parent::convertToPHPValue($value, $platform);

        return null === $data ? null : $this->fromArray($data);
    }
}
