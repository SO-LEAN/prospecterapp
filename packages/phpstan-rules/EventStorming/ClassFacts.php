<?php

declare(strict_types=1);

namespace Prospecter\PHPStan\EventStorming;

/**
 * What the analysis reads of one class: what it implements, what its __invoke takes and returns, what its
 * constructor depends on, the roles its attributes require, and what its output port presents.
 */
final readonly class ClassFacts
{
    /**
     * @param list<string>       $interfaces
     * @param list<list<string>> $parameters   the classes of each parameter of __invoke
     * @param list<string>       $returns      the classes __invoke returns
     * @param list<string>       $dependencies the classes its constructor takes
     * @param list<string>       $roles
     * @param list<string>       $presents     the classes its output port presents, for a query handler
     */
    public function __construct(
        public string $name,
        public array $interfaces = [],
        public array $parameters = [],
        public array $returns = [],
        public bool $mayReturnNothing = false,
        public array $dependencies = [],
        public array $roles = [],
        public array $presents = [],
    ) {
    }

    public function implements(string $interface): bool
    {
        return \in_array($interface, $this->interfaces, true);
    }

    /**
     * @return list<string>
     */
    public function firstParameter(): array
    {
        return $this->parameters[0] ?? [];
    }
}
