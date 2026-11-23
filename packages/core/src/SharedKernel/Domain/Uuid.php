<?php

declare(strict_types=1);

namespace Prospecter\Core\SharedKernel\Domain;

/**
 * An aggregate's id: a UUID that its repository generates.
 */
abstract readonly class Uuid implements \Stringable
{
    private const string FORMAT = '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/';
    private const string HASH = 'sha256';
    private const string NAME_BASED = '8';
    private const string RFC_VARIANT = '8';

    final public function __construct(public string $value)
    {
        if (!preg_match(self::FORMAT, $value)) {
            throw new \InvalidArgumentException(sprintf('"%s" is not a UUID', $value));
        }
    }

    /**
     * An id that the core derives from a name. The same name always gives the same id: a version 8 UUID built from
     * the name's hash (ADR 0154).
     */
    protected static function namedBy(string $name): static
    {
        $hash = hash(self::HASH, $name);

        return new static(sprintf('%s-%s-%s%s-%s%s-%s', substr($hash, 0, 8), substr($hash, 8, 4), self::NAME_BASED, substr($hash, 13, 3), self::RFC_VARIANT, substr($hash, 17, 3), substr($hash, 20, 12)));
    }

    public static function isValid(string $value): bool
    {
        return 1 === preg_match(self::FORMAT, $value);
    }

    public function equals(self $other): bool
    {
        return $other instanceof static && $other->value === $this->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
