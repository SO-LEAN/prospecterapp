<?php

namespace App\Shared\Messaging;

use App\Shared\Persistence\Documents;
use Prospecter\Core\SharedKernel\Domain\Uuid;
use Symfony\Component\TypeInfo\Type;
use Symfony\Component\TypeInfo\Type\CollectionType;
use Symfony\Component\TypeInfo\Type\NullableType;
use Symfony\Component\TypeInfo\Type\ObjectType;
use Symfony\Component\TypeInfo\TypeResolver\TypeResolverInterface;

/**
 * An event as plain data, by the parameters of its constructor, each the name of a public property: an identity
 * as its string, a moment in the format of the documents, a list element by element; and back, each value typed
 * by its parameter and its docblock (`list<PersonId>`).
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
            $arguments[$parameter->name] = $this->typed($this->types->resolve($parameter), $payload[$parameter->name] ?? null);
        }

        return new $class(...$arguments);
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
            $type instanceof CollectionType => array_map(fn (mixed $element): mixed => $this->typed($type->getCollectionValueType(), $element), $value),
            $type instanceof ObjectType => $this->object($type->getClassName(), $value),
            default => $value,
        };
    }

    private function object(string $class, mixed $value): object
    {
        return match (true) {
            \DateTimeImmutable::class === $class => Documents::momentOf($value),
            is_a($class, Uuid::class, true) => new $class($value),
            default => throw new \LogicException(sprintf('%s cannot be read from plain data', $class)),
        };
    }
}
