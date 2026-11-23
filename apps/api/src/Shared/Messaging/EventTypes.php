<?php

declare(strict_types=1);

namespace App\Shared\Messaging;

/**
 * The classes of the events that may travel, keyed by source and detail-type. The detail-type names the contract's
 * version, so that an event may change without breaking the readers of its former version.
 */
abstract readonly class EventTypes
{
    /** @var array<string, array<string, class-string>> the classes, keyed by source, then by detail-type */
    protected const array TYPES = [];

    /**
     * @var array<string, array<string, class-string>> a contract's former versions. They are still read while an
     *                                                 outbox or the failures queue may hold them, and never written.
     */
    protected const array FORMER_TYPES = [];

    /**
     * @return array{string, string}|null the source and detail-type of the class's events, or null when the class is not listed here
     */
    public function typeOf(string $class): ?array
    {
        foreach (static::TYPES as $source => $types) {
            $detailType = array_search($class, $types, true);

            if (false !== $detailType) {
                return [$source, $detailType];
            }
        }

        return null;
    }

    /**
     * @return class-string|null
     */
    public function classOf(string $source, string $detailType): ?string
    {
        return static::TYPES[$source][$detailType] ?? static::FORMER_TYPES[$source][$detailType] ?? null;
    }
}
