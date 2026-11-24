<?php

declare(strict_types=1);

namespace Prospecter\PHPStan\EventStorming;

/**
 * What the analysis reads of one class: what it implements, what its __invoke takes and returns, what its
 * constructor depends on, the roles and the routes its attributes declare, what its output port presents, and the
 * parameters of its constructor that may be null.
 */
final readonly class ClassFacts
{
    /**
     * @param list<string>          $interfaces
     * @param list<list<string>>    $parameters         the classes of each parameter of __invoke
     * @param list<string>          $returns            the classes __invoke returns
     * @param list<string>          $dependencies       the classes its constructor takes
     * @param list<string>          $roles
     * @param list<string>          $presents           the classes its output port presents, for a query handler
     * @param list<string>          $routes             the names of the routes it answers, for a controller
     * @param array<string, string> $optionalParameters the description of each parameter of its constructor that
     *                                                  may be null, by name
     * @param list<string>          $outputPorts        the classes its __invoke presents its answer to
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
        public array $routes = [],
        public array $optionalParameters = [],
        public array $outputPorts = [],
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
