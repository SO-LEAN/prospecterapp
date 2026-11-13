<?php

namespace App\Shared\Messaging;

/**
 * The classes of the events that may travel, by source and detail-type: the detail-type names the version of
 * the contract, so that an event may change without breaking those reading the former one.
 */
abstract readonly class EventTypes
{
    /** @var array<string, array<string, class-string>> the classes by detail-type, by source */
    protected const array TYPES = [];

    /**
     * @var array<string, array<string, class-string>> the former versions of a contract, still read while an outbox
     *                                                 or a queue of failures may hold them, never written
     */
    protected const array FORMER_TYPES = [];

    /**
     * @return array{string, string}|null the source and the detail-type of an event of the class, null when not listed here
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
