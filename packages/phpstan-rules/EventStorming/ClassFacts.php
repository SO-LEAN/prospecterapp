<?php

declare(strict_types=1);

namespace Prospecter\PHPStan\EventStorming;

use Prospecter\Core\SharedKernel\Application\Handler\CommandHandler;
use Prospecter\Core\SharedKernel\Application\Handler\EventHandler;
use Prospecter\Core\SharedKernel\Application\Handler\Policy;
use Prospecter\Core\SharedKernel\Application\Handler\QueryHandler;

/**
 * What the analysis reads of one class: what it implements, what its __invoke takes and returns, what its
 * constructor depends on, the roles and the routes its attributes declare, what its output port presents, the
 * parameters of its constructor that may be null, whether it is a command of the console, and the methods that change
 * it.
 */
final readonly class ClassFacts
{
    private const array HANDLERS = [CommandHandler::class, QueryHandler::class, EventHandler::class, Policy::class];

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
     * @param list<string>          $changes            its public methods that return nothing, which change it
     * @param array<string, string> $offers             the route each parameter of its constructor declares it
     *                                                  offers, by name, for a links resource
     * @param list<string>          $holds              the classes its public properties hold, their lists' included
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
        public bool $console = false,
        public array $changes = [],
        public array $offers = [],
        public array $holds = [],
    ) {
    }

    public function implements(string $interface): bool
    {
        return \in_array($interface, $this->interfaces, true);
    }

    public function handlesAMessage(): bool
    {
        return array_any(self::HANDLERS, $this->implements(...));
    }

    /**
     * @return list<string>
     */
    public function firstParameter(): array
    {
        return $this->parameters[0] ?? [];
    }
}
