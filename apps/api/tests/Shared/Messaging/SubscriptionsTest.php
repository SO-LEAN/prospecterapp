<?php

declare(strict_types=1);

namespace Tests\App\Shared\Messaging;

use App\Shared\DependencyInjection\SubscriptionsPass;
use App\Shared\Messaging\Reaction;
use App\Shared\Messaging\Subscriptions;
use App\Shared\Messaging\TransportedEvent;
use App\Shared\Typed;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ServiceLocator;

/**
 * The subscriptions of `config/subscriptions.json` as the delivery `sync` reads them, as the rules of the bus pass the
 * events to the worker: a subscription naming the payloads it listens to (`payload`, a pattern of EventBridge on the
 * payload of the detail) reacts to those only (ADR 0127). The push into Google listens to the facts of the exchanges
 * planned, cancelled or erased; a recipient of a campaign is processed by the facts of an exchange done or missed
 * (ADR 0145), and to handle again by those of an exchange erased (ADR 0152).
 */
class SubscriptionsTest extends TestCase
{
    private const string PUSH = 'connectors-push-exchange';
    private const string FIGURES = 'reporting-figures';
    private const string PROCESS = 'campaigns-process-recipient';
    private const string REOPEN = 'campaigns-reopen-recipient';
    private const string FORGET_APPOINTMENT = 'booking-forget-appointment-of-erased-exchange';

    /**
     * @param array<string, mixed> $payload
     * @param list<string>         $reacting
     */
    #[DataProvider('factsOfExchanges')]
    public function testAFactOfAnExchangeReachesThePushOnlyWhenThePushNeedsIt(array $payload, array $reacting): void
    {
        $this->assertSame($reacting, $this->subscriptionsReactingTo('prospecter.prospecting', 'ExchangeFactsChanged.v4', $payload));
    }

    /**
     * @return iterable<string, array{array<string, mixed>, list<string>}>
     */
    public static function factsOfExchanges(): iterable
    {
        yield 'planned, or moved' => [self::fact(present: true, status: 'planned'), [self::FIGURES, self::PUSH]];
        yield 'cancelled' => [self::fact(present: true, status: 'cancelled'), [self::FIGURES, self::PUSH]];
        yield 'erased' => [self::fact(present: false, status: null), [self::FIGURES, self::PUSH, self::REOPEN, self::FORGET_APPOINTMENT]];
        yield 'done, or noted once it happened' => [self::fact(present: true, status: 'done'), [self::FIGURES, self::PROCESS]];
        yield 'missed' => [self::fact(present: true, status: 'missed'), [self::FIGURES, self::PROCESS]];
    }

    public function testAContactForgottenReachesTheEventsNamingThem(): void
    {
        $this->assertContains('connectors-unname-forgotten-contact', $this->subscriptionsReactingTo('prospecter.personal-data', 'ContactForgotten.v1', ['accountId' => 'an-account', 'memberId' => 'a-member', 'personId' => 'a-person', 'occurredAt' => '2026-11-16T09:00:00.000000Z']));
    }

    /**
     * @param array<string, mixed> $payload
     *
     * @return list<string> the subscriptions reacting to the event, in the order of the file
     */
    private function subscriptionsReactingTo(string $source, string $detailType, array $payload): array
    {
        $declared = SubscriptionsPass::listedIn(dirname(__DIR__, 3).SubscriptionsPass::FILE);
        $reactions = [];

        foreach ($declared as $name => $subscription) {
            foreach ($subscription['events'] as $event) {
                $reactions[Subscriptions::reactionKey($name, $event)] = static fn (): Reaction => self::reactionNamed($name);
            }
        }

        $reacting = new Subscriptions(new ServiceLocator($reactions), $declared)->reactingTo(new TransportedEvent($source, $detailType, 'an-event', '2026-11-16T09:00:00.000000Z', 'a-correlation', null, 0, $payload));

        return array_map(static fn (Reaction $reaction): string => property_exists($reaction, 'name') ? Typed::string($reaction->name) : '', $reacting);
    }

    /**
     * A reaction known by the name of its subscription.
     */
    private static function reactionNamed(string $name): Reaction
    {
        return new readonly class($name) implements Reaction {
            public function __construct(public string $name)
            {
            }

            public function __invoke(object $received): void
            {
            }
        };
    }

    /**
     * @return array<string, mixed>
     */
    private static function fact(bool $present, ?string $status): array
    {
        return ['accountId' => 'an-account', 'exchangeId' => 'an-exchange', 'revision' => 2, 'present' => $present, 'status' => $status, 'notedAt' => null, 'occurredAt' => '2026-11-16T09:00:00.000000Z'];
    }
}
