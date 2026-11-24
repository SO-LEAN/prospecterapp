<?php

declare(strict_types=1);

namespace Prospecter\PHPStan\EventStorming;

/**
 * Where a class of the core sits: its context, its module of Application, its short name.
 */
final readonly class ClassName
{
    private const string CORE = 'Prospecter\Core\\';

    private const string APPLICATION = 'Application';

    public function __construct(public string $value)
    {
    }

    public function short(): string
    {
        $parts = explode('\\', $this->value);

        return end($parts);
    }

    public function context(): ?string
    {
        return $this->segmentsInTheCore()[0] ?? null;
    }

    public function isOf(string $context): bool
    {
        return $context === $this->context();
    }

    public function layer(): ?string
    {
        return $this->segmentsInTheCore()[1] ?? null;
    }

    public function isAnAggregate(): bool
    {
        return ['Domain', 'Model'] === \array_slice($this->segmentsInTheCore(), 1, 2);
    }

    public function isAPort(): bool
    {
        return ['Domain', 'Port'] === \array_slice($this->segmentsInTheCore(), 1, 2);
    }

    public function isAPublishedEvent(): bool
    {
        return ['Contract', 'Event'] === \array_slice($this->segmentsInTheCore(), 1, 2);
    }

    public function isAnEvent(): bool
    {
        return \in_array(\array_slice($this->segmentsInTheCore(), 1, 2), [['Domain', 'Event'], ['Contract', 'Event']], true);
    }

    /**
     * A use case at the application's root sits in Application\<UseCase>; one of a module sits in
     * Application\<Module>\<UseCase>.
     */
    public function module(): ?string
    {
        $segments = $this->segmentsInTheCore();
        $underApplication = self::APPLICATION === ($segments[1] ?? null) ? \array_slice($segments, 2, -1) : [];

        return \count($underApplication) >= 2 ? $underApplication[0] : null;
    }

    /**
     * @return list<string>
     */
    private function segmentsInTheCore(): array
    {
        return str_starts_with($this->value, self::CORE) ? explode('\\', substr($this->value, \strlen(self::CORE))) : [];
    }
}
