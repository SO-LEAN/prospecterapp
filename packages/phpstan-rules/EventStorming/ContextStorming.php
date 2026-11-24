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

    private const string SOURCE = 'prospecter.';

    private const string PUBLISHED = 'Événement publié';

    private const string PUBLISHED_SLUG = 'published';

    private const string ENTRY = '__invoke';

    /**
     * @param array<string, string> $titles        the title of the feature of each use case, by its short name
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
        $this->stickTheActors($board, $context);

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
            if ([] === $events) {
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

        return new Sticky(StickyType::ExternalSystem->value.':'.$port, self::slugOf(StickyType::ExternalSystem, $name, $context), StickyType::ExternalSystem, Wording::ofIntention($name->short()), null, $name->short(), $port, (string) $name->context(), null, '' === $detail ? null : $detail, array_values($read));
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
            $presented = implode(', ', array_map(static fn (string $class): string => new ClassName($class)->short(), $handler->presents));
            foreach ($handler->firstParameter() as $query) {
                $sticky = $this->useCaseOf(StickyType::ReadModel, $query, $context, '' === $presented ? null : $presented, new ClassName($handler->name)->module());
                $board->stick($sticky);
                $this->tellWhatItAsks($board, $sticky, $handler, $context);
            }
        }
    }

    /**
     * An actor sends a use case through an entrance of the delivery: the role a controller requires, anyone when it
     * requires none, and the member's AI through a tool of the MCP server.
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
     * @return list<string>
     */
    private static function actorsEntering(ClassFacts $entrance): array
    {
        return match (true) {
            str_contains($entrance->name, self::CONTROLLERS) => [] === $entrance->roles ? [self::ANYONE] : $entrance->roles,
            str_contains($entrance->name, self::TOOLS) => [self::AI],
            default => [],
        };
    }

    /**
     * Follows the calls of an entrance through the classes of the delivery up to the use cases on the board it
     * creates.
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
            if ($call->isCreation()) {
                $sent = [...$sent, ...array_values(array_filter(
                    [StickyType::Command->value.':'.$call->target, StickyType::ReadModel->value.':'.$call->target],
                    $board->has(...),
                ))];
            } elseif (null === new ClassName($call->target)->context() && !isset($visited[$target])) {
                $visited[$target] = true;
                $sent = [...$sent, ...$this->useCasesSentBy($board, $this->facts->callsFrom($call->target, $call->targetMethod), $visited)];
            }
        }

        return $sent;
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

    private function useCaseOf(StickyType $type, string $class, string $context, ?string $detail = null, ?string $module = null): Sticky
    {
        $name = new ClassName($class);
        $feature = $this->titles[$name->short()] ?? null;
        $label = StickyType::ReadModel === $type ? Wording::ofReadModel($name->short(), $detail) : Wording::ofIntention($name->short());

        return new Sticky($type->value.':'.$class, self::slugOf($type, $name, $context), $type, $label, $feature, $name->short(), $class, (string) $name->context(), $module ?? $name->module(), $detail);
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
