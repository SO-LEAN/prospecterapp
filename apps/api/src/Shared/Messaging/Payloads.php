<?php

declare(strict_types=1);

namespace App\Shared\Messaging;

use App\Shared\Persistence\Documents;
use App\Shared\Typed;
use Prospecter\Core\SharedKernel\Domain\Identity\Uuid;
use Symfony\Component\TypeInfo\Type;
use Symfony\Component\TypeInfo\Type\CollectionType;
use Symfony\Component\TypeInfo\Type\NullableType;
use Symfony\Component\TypeInfo\Type\ObjectType;
use Symfony\Component\TypeInfo\TypeResolver\TypeResolverInterface;

/**
 * An event as plain data, built from its constructor's parameters. Each parameter names a public property. An id
 * becomes its string, a time takes the documents' format, and a list goes element by element. The way back types
 * each value by its parameter and its docblock (`list<PersonId>`). A value that the event's former version did not
 * carry takes its default.
 */
final readonly class Payloads
{
    public function __construct(private TypeResolverInterface $types)
    {
    }

    /**
     * @return array<string, mixed>
     */
    public function of(object $event): array
    {
        $payload = [];

        foreach ($this->parametersOf($event::class) as $parameter) {
            $payload[$parameter->name] = $this->plain($event->{$parameter->name});
        }

        return $payload;
    }

    /**
     * @template T of object
     *
     * @param class-string<T>      $class
     * @param array<string, mixed> $payload
     *
     * @return T
     */
    public function event(string $class, array $payload): object
    {
        $arguments = [];

        foreach ($this->parametersOf($class) as $parameter) {
            if (self::addedSince($parameter, $payload)) {
                continue;
            }

            $arguments[$parameter->name] = $this->typed($this->types->resolve($parameter), $payload[$parameter->name] ?? null);
        }

        return new $class(...$arguments);
    }

    /**
     * A parameter that the event's former version did not carry takes its default, or null when it has none.
     *
     * @param array<string, mixed> $payload
     */
    private static function addedSince(\ReflectionParameter $parameter, array $payload): bool
    {
        return !array_key_exists($parameter->name, $payload) && $parameter->isDefaultValueAvailable();
    }

    /**
     * @return list<\ReflectionParameter>
     */
    private function parametersOf(string $class): array
    {
        return new \ReflectionMethod($class, '__construct')->getParameters();
    }

    private function plain(mixed $value): mixed
    {
        return match (true) {
            $value instanceof \DateTimeImmutable => Documents::moment($value),
            $value instanceof Uuid => $value->value,
            is_array($value) => array_map($this->plain(...), $value),
            is_object($value) => throw new \LogicException(sprintf('%s cannot travel as plain data', $value::class)),
            default => $value,
        };
    }

    private function typed(Type $type, mixed $value): mixed
    {
        return match (true) {
            null === $value => null,
            $type instanceof NullableType => $this->typed($type->getWrappedType(), $value),
            $type instanceof CollectionType => array_map(fn (mixed $element): mixed => $this->typed($type->getCollectionValueType(), $element), Typed::array($value)),
            $type instanceof ObjectType => $this->object($type->getClassName(), $value),
            default => $value,
        };
    }

    private function object(string $class, mixed $value): object
    {
        return match (true) {
            \DateTimeImmutable::class === $class => Documents::momentOf(Typed::string($value)),
            is_a($class, Uuid::class, true) => new $class($value),
            default => throw new \LogicException(sprintf('%s cannot be read from plain data', $class)),
        };
    }
}
