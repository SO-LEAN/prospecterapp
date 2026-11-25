<?php

declare(strict_types=1);

namespace Prospecter\PHPStan\EventStorming;

use Prospecter\Core\SharedKernel\Application\Handler\CommandHandler;
use Prospecter\Core\SharedKernel\Application\Handler\EventHandler;
use Prospecter\Core\SharedKernel\Application\Handler\Policy;
use Prospecter\Core\SharedKernel\Application\Handler\QueryHandler;

/**
 * Tells one context's event storming from the facts the analysis read in its code.
 */
final readonly class ContextStorming
{
    private const string COMMAND_HANDLER = CommandHandler::class;

    private const string EVENT_HANDLER = EventHandler::class;

    private const string QUERY_HANDLER = QueryHandler::class;

    private const string POLICY = Policy::class;

    private const string CONTROLLERS = '\\Controller\\';

    private const string TOOLS = '\\Mcp\\';

    private const string ANYONE = 'public';

    private const string AI = 'mcp';

    private const string OPERATOR = 'operator';

    private const string SCHEDULE = 'schedule';

    /** The short name of the delivery's interface of a daily task: the generator names no class of the delivery. */
    private const string SCHEDULED_TASK = 'ScheduledTask';

    private const string SOURCE = 'prospecter.';

    private const string PUBLISHED = 'Published to the other contexts';

    private const string PUBLISHED_SLUG = 'published';

    private const string OFFERED_ONLY = '/Offered only ([^.]+)/';

    private const string ENTRY = '__invoke';

    /**
     * @param array<string, string> $titles        each use case's feature title, by its short name, or by its context and short name (Booking/OpenBooking)
     * @param array<array<mixed>>   $subscriptions the subscriptions of the delivery, by their name
     */
    public function __construct(private Facts $facts, private array $titles = [], private array $subscriptions = [])
    {
    }

    public function model(string $context): Model
    {
        $board = new Board();
        $this->stickTheCommands($board, $context);
        $this->stickThePolicies($board, $context);
        $this->stickTheReactions($board, $context);
        $this->stickTheSubscribers($board, $context);
        $this->stickTheReadModels($board, $context);
        $this->stickTheProjections($board, $context);
        $this->stickTheActors($board, $context);
        $this->stickTheOffers($board, $context);

        return $board->model($context);
    }

    private function stickTheCommands(Board $board, string $context): void
    {
        foreach ($this->handlersOf($context, self::COMMAND_HANDLER) as $handler) {
            foreach ($handler->firstParameter() as $command) {
                $sticky = $this->useCaseOf(StickyType::Command, $command, $context);
                $board->stick($sticky);
                $this->tellWhatItChanges($board, $sticky, $handler->name, $context);
                $this->tellWhatItAsks($board, $sticky, $handler, $context);
            }
        }
    }

    private function tellWhatItChanges(Board $board, Sticky $useCase, string $handler, string $context): void
    {
        foreach ($this->reachedFrom($handler, self::ENTRY, $context, Layer::Application) as $intention) {
            if (new ClassName($intention->class)->isAnEvent()) {
                $this->stickTheEvent($board, $useCase, $intention, $context);
                continue;
            }
            $events = $this->reachedFrom($intention->class, $intention->method, $context, Layer::Domain);
            if ([] === $events && !$this->changes($intention)) {
                continue;
            }
            $aggregate = $this->stickyOf(StickyType::Aggregate, $intention->class, $context);
            $board->stick($aggregate);
            $board->link(new Link($useCase->id, $aggregate->id, $intention->method, $intention->certain));
            foreach ($events as $event) {
                $this->stickTheEvent($board, $aggregate, $event, $context, $intention->method);
            }
        }
    }

    /**
     * A method of an aggregate that returns nothing changes it, by command-query separation, whether it records an
     * event or not.
     */
    private function changes(Reached $intention): bool
    {
        return \in_array($intention->method, $this->facts->find($intention->class)->changes ?? [], true);
    }

    /**
     * Follows the calls from a method through the classes of the context the layer goes through, up to what that
     * layer reaches: the intentions of the aggregates from the application, the events from the domain.
     *
     * @param array<string, true> $visited
     *
     * @return list<Reached>
     */
    private function reachedFrom(string $class, string $method, string $context, Layer $layer, bool $certain = true, array $visited = []): array
    {
        $visited[$class.'::'.$method] = true;
        $reached = [];
        foreach ($this->facts->callsFrom($class, $method) as $call) {
            if (isset($visited[$call->target.'::'.$call->targetMethod])) {
                continue;
            }
            $certainOnTheWay = $certain && !$call->conditional;
            $target = new ClassName($call->target);
            if ($layer->reaches($target, $call)) {
                $reached[] = new Reached($call->target, $call->targetMethod, $certainOnTheWay);
            } elseif ($target->isOf($context) && $layer->goesThrough($target)) {
                $reached = [...$reached, ...$this->reachedFrom($call->target, $call->targetMethod, $context, $layer, $certainOnTheWay, $visited)];
            }
        }

        return $reached;
    }

    private function stickTheEvent(Board $board, Sticky $cause, Reached $event, string $context, ?string $via = null): void
    {
        $sticky = $this->stickyOf(StickyType::Event, $event->class, $context);
        $board->stick($sticky);
        $board->link(new Link($cause->id, $sticky->id, $via, $event->certain));
    }

    /**
     * The external systems a use case asks, through the ports its handler or the context's services it takes depend
     * on.
     *
     * @param array<string, true> $visited
     */
    private function tellWhatItAsks(Board $board, Sticky $useCase, ClassFacts $class, string $context, array $visited = []): void
    {
        $visited[$class->name] = true;
        foreach ($class->dependencies as $dependency) {
            $name = new ClassName($dependency);
            $facts = $this->facts->find($dependency);
            if ($name->isAPort() && $name->isOf($context)) {
                $system = $this->externalSystemOf($dependency, $context);
                $board->stick($system);
                $board->link(new Link($useCase->id, $system->id));
            } elseif ($name->isOf($context) && null !== $facts && !isset($visited[$dependency])) {
                $this->tellWhatItAsks($board, $useCase, $facts, $context, $visited);
            }
        }
    }

    /**
     * An external system is named after its port, and answered by the contexts its adapters read.
     */
    private function externalSystemOf(string $port, string $context): Sticky
    {
        $adapters = array_values(array_filter($this->facts->classes(), static fn (ClassFacts $class): bool => $class->implements($port)));
        $read = array_unique(array_filter(array_map(
            static fn (string $dependency): ?string => new ClassName($dependency)->context(),
            array_merge(...array_map(static fn (ClassFacts $adapter): array => $adapter->dependencies, $adapters)),
        )));
        sort($read);
        $name = new ClassName($port);
        $detail = implode(', ', array_map(static fn (ClassFacts $adapter): string => new ClassName($adapter->name)->short(), $adapters));

        $answers = Elsewhere::sorted(array_merge(...array_map($this->servicesTakenBy(...), $adapters)));

        return new Sticky(StickyType::ExternalSystem->value.':'.$port, self::slugOf(StickyType::ExternalSystem, $name, $context), StickyType::ExternalSystem, Wording::ofIntention($name->short()), null, $name->short(), $port, (string) $name->context(), null, '' === $detail ? null : $detail, $read, $answers);
    }

    /**
     * The published services an adapter takes, each a read model on the wall of its context: the query its handler
     * answers.
     *
     * @return list<Elsewhere>
     */
    private function servicesTakenBy(ClassFacts $adapter): array
    {
        $handlers = array_merge(...array_map($this->handlersImplementing(...), $adapter->dependencies));

        return array_merge(...array_map(self::servicesAnsweredBy(...), $handlers));
    }

    /**
     * @return list<Elsewhere> the read model of each query a handler of a published service answers
     */
    private static function servicesAnsweredBy(ClassFacts $handler): array
    {
        return array_map(
            static function (string $query) use ($handler): Elsewhere {
                $name = new ClassName($query);

                return new Elsewhere($name->context(), StickyType::ReadModel->value.':'.$name->short(), Wording::ofReadModel($name->short(), self::presentedBy($handler)));
            },
            $handler->firstParameter(),
        );
    }

    /**
     * @return list<ClassFacts>
     */
    private function handlersImplementing(string $service): array
    {
        return array_values(array_filter($this->facts->classes(), static fn (ClassFacts $class): bool => $class->implements(self::QUERY_HANDLER) && $class->implements($service)));
    }

    private static function presentedBy(ClassFacts $handler): ?string
    {
        $presented = implode(', ', array_map(static fn (string $class): string => new ClassName($class)->short(), $handler->presents));

        return '' === $presented ? null : $presented;
    }

    private function stickThePolicies(Board $board, string $context): void
    {
        foreach ($this->handlersOf($context, self::POLICY) as $policy) {
            $sticky = $this->reactionOf($board, $policy, $context);
            foreach ($policy->returns as $command) {
                $sent = $this->useCaseOf(StickyType::Command, $command, $context);
                $board->stick($sent);
                $board->link(new Link($sticky->id, $sent->id, certain: !$policy->mayReturnNothing));
            }
        }
    }

    private function reactionOf(Board $board, ClassFacts $reaction, string $context): Sticky
    {
        $sticky = $this->stickyOf(StickyType::Policy, $reaction->name, $context);
        $board->stick($sticky);
        foreach ($reaction->firstParameter() as $event) {
            $heard = $this->stickyOf(StickyType::Event, $event, $context);
            $board->stick($heard);
            $board->link(new Link($heard->id, $sticky->id));
        }

        return $sticky;
    }

    private function stickTheReactions(Board $board, string $context): void
    {
        foreach ($this->handlersOf($context, self::EVENT_HANDLER) as $handler) {
            $this->tellWhatItChanges($board, $this->reactionOf($board, $handler, $context), $handler->name, $context);
        }
    }

    /**
     * The other contexts hear a published event through the subscriptions to its context's source.
     */
    private function stickTheSubscribers(Board $board, string $context): void
    {
        $source = self::SOURCE.strtolower((string) preg_replace('/(?<!^)[A-Z]/', '-$0', $context));
        foreach ($board->stickiesOf(StickyType::Event) as $event) {
            if (!new ClassName((string) $event->class)->isAPublishedEvent() || $event->context !== $context) {
                continue;
            }
            foreach ($this->subscriptions as $subscription) {
                $reaction = $subscription['policy'] ?? $subscription['handler'] ?? null;
                if (\is_string($reaction) && self::listensTo($subscription, $source, $event->name)) {
                    $subscriber = $this->stickyOf(StickyType::Policy, $reaction, $context);
                    $board->stick($subscriber);
                    $board->link(new Link($event->id, $subscriber->id));
                }
            }
        }
    }

    /**
     * @param array<mixed> $subscription
     */
    private static function listensTo(array $subscription, string $source, string $event): bool
    {
        $sources = \is_array($subscription['sources'] ?? null) ? $subscription['sources'] : [];
        $events = \is_array($subscription['events'] ?? null) ? $subscription['events'] : [];

        return \in_array($source, $sources, true)
            && [] !== array_filter($events, static fn (mixed $name): bool => \is_string($name) && explode('.', $name)[0] === $event);
    }

    private function stickTheReadModels(Board $board, string $context): void
    {
        foreach ($this->handlersOf($context, self::QUERY_HANDLER) as $handler) {
            foreach ($handler->firstParameter() as $query) {
                $sticky = $this->useCaseOf(StickyType::ReadModel, $query, $context, self::presentedBy($handler), new ClassName($handler->name)->module(), $this->readersOf($handler));
                $board->stick($sticky);
                $this->tellWhatItAsks($board, $sticky, $handler, $context);
            }
        }
    }

    /**
     * A projection records no event. Its command keeps another context's facts, which its module's views read. It leads
     * to each of these views.
     */
    private function stickTheProjections(Board $board, string $context): void
    {
        foreach ($this->handlersOf($context, self::COMMAND_HANDLER) as $handler) {
            if (!$this->keepsFacts($handler->name, self::ENTRY, $context)) {
                continue;
            }
            foreach ($handler->firstParameter() as $command) {
                foreach (self::viewsOfTheModuleOf($board, $command) as $view) {
                    $board->link(new Link(StickyType::Command->value.':'.$command, $view->id));
                }
            }
        }
    }

    /**
     * Whether a method reaches, through the classes of the context, the repository of the facts it keeps.
     *
     * @param array<string, true> $visited
     */
    private function keepsFacts(string $class, string $method, string $context, array $visited = []): bool
    {
        $visited[$class.'::'.$method] = true;

        return array_any($this->facts->callsFrom($class, $method), fn (Call $call): bool => $this->leadsToFacts($call, $context, $visited));
    }

    /**
     * @param array<string, true> $visited
     */
    private function leadsToFacts(Call $call, string $context, array $visited): bool
    {
        $target = new ClassName($call->target);
        if ($target->isAFactRepository()) {
            return true;
        }

        return $target->isOf($context) && !isset($visited[$call->target.'::'.$call->targetMethod]) && $this->keepsFacts($call->target, $call->targetMethod, $context, $visited);
    }

    /**
     * @return list<Sticky>
     */
    private static function viewsOfTheModuleOf(Board $board, string $command): array
    {
        $module = new ClassName($command)->module();

        return array_values(array_filter($board->stickiesOf(StickyType::ReadModel), static fn (Sticky $view): bool => $module === $view->module));
    }

    /**
     * What takes the published services a query handler implements: the port of each adapter, on the wall of its
     * context, or the class itself when it implements no port.
     *
     * @return list<Elsewhere>
     */
    private function readersOf(ClassFacts $handler): array
    {
        $services = array_filter($handler->interfaces, static fn (string $interface): bool => new ClassName($interface)->isAPublishedService());
        $readers = array_filter($this->facts->classes(), static fn (ClassFacts $class): bool => [] !== array_intersect($services, $class->dependencies));

        return Elsewhere::sorted(array_values(array_map(self::readerOf(...), $readers)));
    }

    private static function readerOf(ClassFacts $reader): Elsewhere
    {
        $port = array_find($reader->interfaces, static fn (string $interface): bool => new ClassName($interface)->isAPort());
        if (null === $port) {
            return new Elsewhere(null, null, new ClassName($reader->name)->short());
        }
        $name = new ClassName($port);

        return new Elsewhere($name->context(), StickyType::ExternalSystem->value.':'.$name->short(), Wording::ofIntention($name->short()));
    }

    /**
     * An actor sends a use case through an entrance of the delivery: the role a controller requires, anyone when it
     * requires none, the member's AI through a tool of the MCP server, the platform operator through a command of the
     * console, and the daily schedule through a daily task.
     */
    private function stickTheActors(Board $board, string $context): void
    {
        foreach ($this->facts->classes() as $entrance) {
            $actors = self::actorsEntering($entrance);
            $calls = array_values(array_filter($this->facts->calls, static fn (Call $call): bool => $call->caller === $entrance->name));
            foreach ([] === $actors ? [] : $this->useCasesSentBy($board, $calls) as $useCase) {
                foreach ($actors as $role) {
                    $actor = new Sticky(StickyType::Actor->value.':'.$role, StickyType::Actor->value.':'.$role, StickyType::Actor, Wording::ofActor($role), null, $role, null, $context, null);
                    $board->stick($actor);
                    $board->link(new Link($actor->id, $useCase));
                }
            }
        }
    }

    /**
     * A view offers the commands its links resources declare: each class its presenter holds, through the types of
     * their public properties and of their lists, that declares the route a parameter offers leads to the commands
     * that route's controller sends. The link is offered under a condition when its parameter may be null.
     */
    private function stickTheOffers(Board $board, string $context): void
    {
        foreach ($this->handlersOf($context, self::QUERY_HANDLER) as $handler) {
            foreach ($handler->firstParameter() as $query) {
                foreach ($this->classesHeldByThePresentersOf($handler) as $links) {
                    foreach ($links->offers as $parameter => $route) {
                        $condition = $links->optionalParameters[$parameter] ?? null;
                        foreach ($this->commandsRoutedBy($board, [$route]) as $command) {
                            $board->link(new Link(StickyType::ReadModel->value.':'.$query, $command, $parameter, null === $condition, self::whenOf($condition)));
                        }
                    }
                }
            }
        }
    }

    /**
     * The condition a links resource gives a link in the description of its parameter: the words after
     * "Offered only", up to the end of the sentence (docs/architecture/api.md).
     */
    private static function whenOf(?string $description): ?string
    {
        return null !== $description && 1 === preg_match(self::OFFERED_ONLY, $description, $condition) ? $condition[1] : null;
    }

    /**
     * @return list<ClassFacts> the classes the presenters of a query's output port hold, the links resources among them
     */
    private function classesHeldByThePresentersOf(ClassFacts $handler): array
    {
        $presenters = array_filter($this->facts->classes(), static fn (ClassFacts $class): bool => array_any($handler->outputPorts, $class->implements(...)));
        $held = [];
        foreach ($presenters as $presenter) {
            $held = $this->heldFrom($presenter, $held);
        }

        return array_values($held);
    }

    /**
     * @param array<string, ClassFacts> $held
     *
     * @return array<string, ClassFacts> what a class holds, and what that holds, by name
     */
    private function heldFrom(ClassFacts $class, array $held): array
    {
        foreach ($class->holds as $name) {
            $facts = $this->facts->find($name);
            if (null !== $facts && !isset($held[$name])) {
                $held[$name] = $facts;
                $held = $this->heldFrom($facts, $held);
            }
        }

        return $held;
    }

    /**
     * @param list<string> $routes
     *
     * @return list<string> the ids of the commands the controllers of these routes send
     */
    private function commandsRoutedBy(Board $board, array $routes): array
    {
        $commands = [];
        foreach ($this->facts->classes() as $controller) {
            if (array_any($routes, static fn (string $route): bool => \in_array($route, $controller->routes, true))) {
                $calls = array_values(array_filter($this->facts->calls, static fn (Call $call): bool => $call->caller === $controller->name));
                $commands = [...$commands, ...array_filter($this->useCasesSentBy($board, $calls), static fn (string $id): bool => str_starts_with($id, StickyType::Command->value.':'))];
            }
        }

        return $commands;
    }

    /**
     * The daily schedule runs a task that the console may run too.
     *
     * @return list<string>
     */
    private static function actorsEntering(ClassFacts $entrance): array
    {
        $scheduled = array_any($entrance->interfaces, static fn (string $interface): bool => self::SCHEDULED_TASK === new ClassName($interface)->short());

        return [...($scheduled ? [self::SCHEDULE] : []), ...self::actorsAsking($entrance)];
    }

    /**
     * @return list<string>
     */
    private static function actorsAsking(ClassFacts $entrance): array
    {
        return match (true) {
            $entrance->console => [self::OPERATOR],
            str_contains($entrance->name, self::CONTROLLERS) => [] === $entrance->roles ? [self::ANYONE] : $entrance->roles,
            str_contains($entrance->name, self::TOOLS) => [self::AI],
            default => [],
        };
    }

    /**
     * Follows the calls of an entrance through the classes of the delivery up to the use cases on the board it
     * creates. A named constructor of a use case's message creates it in its own class.
     *
     * @param list<Call>          $calls
     * @param array<string, true> $visited
     *
     * @return list<string> their ids
     */
    private function useCasesSentBy(Board $board, array $calls, array $visited = []): array
    {
        $sent = [];
        foreach ($calls as $call) {
            $target = $call->target.'::'.$call->targetMethod;
            $useCases = self::useCasesNamed($board, $call->target);
            if ($call->isCreation()) {
                $sent = [...$sent, ...$useCases];
            } elseif ((null === new ClassName($call->target)->context() || [] !== $useCases) && !isset($visited[$target])) {
                $visited[$target] = true;
                $sent = [...$sent, ...$this->useCasesSentBy($board, $this->facts->callsFrom($call->target, $call->targetMethod), $visited)];
            }
        }

        return $sent;
    }

    /**
     * @return list<string> the ids of the use cases on the board whose message is this class
     */
    private static function useCasesNamed(Board $board, string $class): array
    {
        return array_values(array_filter([StickyType::Command->value.':'.$class, StickyType::ReadModel->value.':'.$class], $board->has(...)));
    }

    /**
     * @return list<ClassFacts>
     */
    private function handlersOf(string $context, string $kind): array
    {
        return array_values(array_filter(
            $this->facts->classes(),
            static fn (ClassFacts $class): bool => $class->implements($kind) && new ClassName($class->name)->isOf($context),
        ));
    }

    /**
     * A tag naming the use case's context wins over a tag without one: two contexts may name a use case alike.
     */
    private function titleOf(ClassName $name): ?string
    {
        return $this->titles[$name->context().'/'.$name->short()] ?? $this->titles[$name->short()] ?? null;
    }

    /**
     * @param list<Elsewhere> $readBy
     */
    private function useCaseOf(StickyType $type, string $class, string $context, ?string $detail = null, ?string $module = null, array $readBy = []): Sticky
    {
        $name = new ClassName($class);
        $label = StickyType::ReadModel === $type ? Wording::ofReadModel($name->short(), $detail) : Wording::ofIntention($name->short());

        return new Sticky($type->value.':'.$class, self::slugOf($type, $name, $context), $type, $label, $this->titleOf($name), $name->short(), $class, (string) $name->context(), $module ?? $name->module(), $detail, readBy: $readBy);
    }

    private function stickyOf(StickyType $type, string $class, string $context): Sticky
    {
        $name = new ClassName($class);
        $detail = StickyType::Event === $type && $name->isAPublishedEvent() ? self::PUBLISHED : null;

        $label = StickyType::Policy === $type ? Wording::ofReaction($name->short()) : Wording::ofIntention($name->short());

        return new Sticky($type->value.':'.$class, self::slugOf($type, $name, $context), $type, $label, null, $name->short(), $class, (string) $name->context(), $name->module(), $detail);
    }

    /**
     * A post-it's short name in a link to its wall: its type and its class's short name. A published event is
     * stamped, and a post-it of another context names that context first.
     */
    private static function slugOf(StickyType $type, ClassName $name, string $context): string
    {
        $kind = StickyType::Event === $type && $name->isAPublishedEvent() ? self::PUBLISHED_SLUG : $type->value;
        $wall = $name->isOf($context) ? '' : $name->context().'/';

        return $wall.$kind.':'.$name->short();
    }
}
