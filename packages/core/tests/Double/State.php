<?php

declare(strict_types=1);

namespace Tests\Core\Double;

/**
 * The private state of an aggregate, each property read as the type the reader expects of it.
 */
final readonly class State
{
    /**
     * @param array<mixed> $properties by their names
     */
    public function __construct(private array $properties)
    {
    }

    /**
     * @template T of object
     *
     * @param class-string<T> $class
     *
     * @return T
     */
    public function object(string $property, string $class): object
    {
        $value = $this->valueOf($property);

        return $value instanceof $class ? $value : throw $this->notA($class, $property, $value);
    }

    /**
     * @template T of object
     *
     * @param class-string<T> $class
     *
     * @return T|null
     */
    public function objectOrNull(string $property, string $class): ?object
    {
        return null === $this->valueOf($property) ? null : $this->object($property, $class);
    }

    /**
     * @template T of object
     *
     * @param class-string<T> $class
     *
     * @return list<T>
     */
    public function listOf(string $property, string $class): array
    {
        $items = $this->valueOf($property);

        if (!is_array($items)) {
            throw $this->notA(sprintf('list of %s', $class), $property, $items);
        }

        return array_values(array_map(fn (mixed $item): object => $item instanceof $class ? $item : throw $this->notA(sprintf('list of %s', $class), $property, $items), $items));
    }

    public function bool(string $property): bool
    {
        $value = $this->valueOf($property);

        return is_bool($value) ? $value : throw $this->notA('bool', $property, $value);
    }

    public function string(string $property): string
    {
        $value = $this->valueOf($property);

        return is_string($value) ? $value : throw $this->notA('string', $property, $value);
    }

    public function stringOrNull(string $property): ?string
    {
        return null === $this->valueOf($property) ? null : $this->string($property);
    }

    /**
     * As it is, for an assertion comparing it.
     */
    public function value(string $property): mixed
    {
        return $this->valueOf($property);
    }

    private function valueOf(string $property): mixed
    {
        return array_key_exists($property, $this->properties) ? $this->properties[$property] : throw new \UnexpectedValueException(sprintf('The aggregate has no property "%s"', $property));
    }

    private function notA(string $type, string $property, mixed $value): \UnexpectedValueException
    {
        return new \UnexpectedValueException(sprintf('%s is not a %s: %s', $property, $type, get_debug_type($value)));
    }
}
