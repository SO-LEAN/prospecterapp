<?php

namespace Tests\App\Shared\Messaging;

use App\Demo\DemoData;
use App\Shared\Messaging\Cause;
use App\Shared\Messaging\EventCodec;
use App\Shared\Messaging\EventLoop;
use App\Shared\Messaging\IntegrationEventConsumer;
use App\Shared\Messaging\IntegrationFailures;
use App\Shared\Messaging\UnknownSubscription;
use App\Shared\Persistence\ConditionFailed;
use Aws\Command;
use Aws\DynamoDb\Exception\DynamoDbException;
use Bref\Context\Context;
use PHPUnit\Framework\Attributes\DataProvider;
use Prospecter\Core\PersonalData\Contract\Event\ContactFactsChanged;
use Prospecter\Core\PersonalData\Contract\Event\ContactForgotten;
use Prospecter\Core\Prospecting\Contract\Event\OrganizationFactsChanged;
use Prospecter\Core\Prospecting\Domain\Event\ObservationsWritten;
use Prospecter\Core\Prospecting\Domain\Event\ProspectingStopped;
use Prospecter\Core\Reporting\Application\ConsultFigures\FiguresResponse;
use Prospecter\Core\Reporting\Application\Port\FiguresRetriever;
use Prospecter\Core\Reporting\Application\Port\ProjectionVersions;
use Prospecter\Core\Reporting\Application\RebuildFigures\RebuildFigures;
use Prospecter\Core\Reporting\Application\RebuildFigures\RebuildFiguresHandler;
use Prospecter\Core\Reporting\Domain\Model\Month;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\Exception\NotPossibleInItsState;
use Prospecter\Core\SharedKernel\Domain\MemberId;
use Prospecter\Core\SharedKernel\Domain\OrganizationId;
use Psr\Log\LogLevel;
use Tests\App\Base\ApiTestCase;
use Tests\App\Base\DeliversThroughTheOutbox;
use Tests\App\Double\FailingReaction;
use Tests\App\Double\InMemoryLogger;
use Tests\App\Double\InMemorySqs;

/**
 * The worker applies an event given by the rule of a subscription, in the shape of its input transformer, once: its
 * inbox, what its reaction writes and what it publishes in one transaction, the events published one level deeper.
 * A refusal goes to the failures without a retry, any other failure is thrown for Lambda to retry, writing nothing.
 */
class IntegrationEventConsumerTest extends ApiTestCase
{
    use DeliversThroughTheOutbox;

    private const string FAILURES = 'prospecter-test-integration-failures';
    private const string CORRELATION = '0199f1d2-6a3b-7c4d-8e5f-0123456789ab';
    private const string CAUSE = '0199f1d2-6a3b-7c4d-8e5f-0123456789ac';

    private string $organization;
    private string $contact;

    protected function setUp(): void
    {
        parent::setUp();
        $this->login();
        $this->organization = $this->requestJson('POST', '/api/organizations', ['legalIdentity' => ['corporateName' => 'ACME']])['id'];
        $this->contact = $this->requestJson('POST', '/api/contacts', ['name' => ['firstName' => 'Ada'], 'organizationIds' => [$this->organization]])['id'];
        $this->requestJson('POST', '/api/contacts/'.$this->contact.'/exchanges', ['channel' => 'call', 'direction' => 'inbound', 'occurredAt' => '2026-10-10T09:00:00+00:00']);
        $this->deliverThroughTheOutbox();
    }

    protected function tearDown(): void
    {
        $this->deliverAsTheTestsDo();
        parent::tearDown();
    }

    public function testAReactionPublishesWhatItCausesOneLevelDeeperInTheOutboxOfItsCommit(): void
    {
        $invocation = $this->invocationOf('prospecting-stop-keeping-exchanges', $this->prospectingStopped(), depth: 2);

        $this->consume($invocation);

        $items = $this->outboxOf('prospecting');
        $this->assertCount(1, $items);
        $events = json_decode($items[0]['events'], true, flags: \JSON_THROW_ON_ERROR);
        $this->assertContains('ExchangeLeftWithoutOrganization.v1', array_column($events, 'detailType'));
        $this->assertSame([[3, self::CORRELATION]], array_values(array_unique(array_map(static fn (array $event): array => [$event['detail']['depth'], $event['detail']['correlationId']], $events), \SORT_REGULAR)));
        $published = array_filter($events, static fn (array $event): bool => 'prospecter.prospecting.internal' === $event['source']);
        $this->assertSame([$invocation['detail']['eventId']], array_values(array_unique(array_map(static fn (array $event): string => $event['detail']['causationId'], $published))));
    }

    public function testAnEventDeliveredTwiceIsAppliedOnce(): void
    {
        $invocation = $this->invocationOf('prospecting-stop-keeping-exchanges', $this->prospectingStopped());

        $this->consume($invocation);
        $this->consume($invocation);

        $this->assertCount(1, $this->outboxOf('prospecting'));
        $this->assertCount(1, $this->inboxOf('prospecting', 'prospecting-stop-keeping-exchanges'));
        $this->assertSame([], $this->failures());
    }

    public function testAReactionFromAnotherContextKeepsItsInboxInTheTableOfItsOwn(): void
    {
        $this->consume($this->invocationOf('prospecting-detach-forgotten-contact', $this->contactForgotten()));

        $this->assertCount(1, $this->inboxOf('prospecting', 'prospecting-detach-forgotten-contact'));
        $this->assertSame([], $this->inboxOf('personal_data', 'prospecting-detach-forgotten-contact'));
    }

    public function testTwoSubscriptionsToTheSameEventDoNotReplayEachOther(): void
    {
        $event = $this->contactForgotten();
        $detach = $this->invocationOf('prospecting-detach-forgotten-contact', $event);
        $keep = ['subscription' => 'prospecting-keep-exchanges-of-forgotten-contact'] + $detach;

        $this->consume($detach);
        $this->consume($keep);

        $this->assertSame([$detach['detail']['eventId']], array_column($this->inboxOf('prospecting', 'prospecting-detach-forgotten-contact'), 'SK'));
        $this->assertSame([$detach['detail']['eventId']], array_column($this->inboxOf('prospecting', 'prospecting-keep-exchanges-of-forgotten-contact'), 'SK'));
    }

    public function testARefusedEventGoesToTheFailuresWithoutARetry(): void
    {
        $this->reaction()->failWith(NotPossibleInItsState::because('Refused by the reaction'));
        $invocation = $this->invocationOf('prospecting-failing-reaction', $this->observationsWritten());

        $this->consume($invocation);

        $failures = $this->failures();
        $this->assertCount(1, $failures);
        $this->assertSame($invocation, $failures[0]['requestPayload']);
        $this->assertSame(IntegrationFailures::REFUSED, $failures[0]['requestContext']['condition']);
        $this->assertSame('Refused by the reaction', $failures[0]['responsePayload']['errorMessage']);
        $this->assertSame([], $this->inboxOf('prospecting', 'prospecting-failing-reaction'));
    }

    public function testARefusalIsLoggedWithTheEventItsCorrelationAndItsReason(): void
    {
        $this->reaction()->failWith(NotPossibleInItsState::because('Refused by the reaction'));
        $invocation = $this->invocationOf('prospecting-failing-reaction', $this->observationsWritten());

        $this->consume($invocation);

        $logged = array_values(array_filter(self::getContainer()->get(InMemoryLogger::class)->records, static fn (array $record): bool => LogLevel::ERROR === $record['level']));
        $this->assertCount(1, $logged);
        $this->assertSame(
            ['subscription' => 'prospecting-failing-reaction', 'eventId' => $invocation['detail']['eventId'], 'correlationId' => self::CORRELATION, 'reason' => 'notPossibleInItsState', 'field' => ''],
            array_intersect_key($logged[0]['context'], array_flip(['subscription', 'eventId', 'correlationId', 'reason', 'field'])),
        );
    }

    public function testAFailureIsThrownForLambdaToRetryWritingNothingNorItsInbox(): void
    {
        $this->reaction()->failWith(new \RuntimeException('The table does not answer'));
        $invocation = $this->invocationOf('prospecting-failing-reaction', $this->observationsWritten());

        try {
            $this->consume($invocation);
            $this->fail('the failure was not thrown');
        } catch (\RuntimeException $failure) {
            $this->assertSame('The table does not answer', $failure->getMessage());
        }

        $this->assertSame([], $this->inboxOf('prospecting', 'prospecting-failing-reaction'));
        $this->assertSame([], $this->failures());
        $this->reaction()->stopFailing();
        $this->consume($invocation);
        $this->assertCount(1, $this->inboxOf('prospecting', 'prospecting-failing-reaction'));
    }

    public function testAConflictIsAppliedAgainFromTheRead(): void
    {
        $this->reaction()->failWith(self::conflict(), times: 3);
        $invocation = $this->invocationOf('prospecting-failing-reaction', $this->observationsWritten());

        $this->consume($invocation);

        $this->assertSame(4, $this->reaction()->calls);
        $this->assertCount(1, $this->inboxOf('prospecting', 'prospecting-failing-reaction'));
        $this->assertSame([], $this->failures());
    }

    public function testAConflictBeyondThreeRetriesIsThrownForLambdaToRetry(): void
    {
        $this->reaction()->failWith(self::conflict());
        $invocation = $this->invocationOf('prospecting-failing-reaction', $this->observationsWritten());

        try {
            $this->consume($invocation);
            $this->fail('the conflict was not thrown');
        } catch (ConditionFailed) {
            $this->assertSame(4, $this->reaction()->calls);
        }

        $this->assertSame([], $this->inboxOf('prospecting', 'prospecting-failing-reaction'));
        $this->assertSame([], $this->failures());
    }

    public function testASubscriptionReactsToEachEventByTheHandlerItNamesForIt(): void
    {
        $this->rebuildTheFigures();
        $before = $this->figures();
        $organization = new OrganizationFactsChanged(DemoData::SOLEAN_IT, self::newIdentity(), 1, true, true, new \DateTimeImmutable());
        $contact = new ContactFactsChanged(DemoData::SOLEAN_IT, self::newIdentity(), 1, true, new \DateTimeImmutable());

        $this->consume($this->invocationOf('reporting-figures', $organization));
        $this->consume($this->invocationOf('reporting-figures', $contact));

        $after = $this->figures();
        $this->assertSame([$before->activeOrganizations + 1, $before->locatedOrganizations + 1, $before->contacts + 1], [$after->activeOrganizations, $after->locatedOrganizations, $after->contacts]);
        $this->assertSame([], $this->failures());
    }

    public function testAnEventASubscriptionDoesNotListenToIsRefused(): void
    {
        $invocation = ['subscription' => 'reporting-figures'] + $this->invocationOf('prospecting-failing-reaction', $this->observationsWritten());

        $this->consume($invocation);

        $this->assertSame([UnknownSubscription::class], array_column(array_column($this->failures(), 'responsePayload'), 'errorType'));
    }

    public function testAnEventDeeperThanTheMaximumIsRefused(): void
    {
        $invocation = $this->invocationOf('prospecting-failing-reaction', $this->observationsWritten(), depth: EventLoop::MAX_EVENT_DEPTH + 1);

        $this->consume($invocation);

        $this->assertSame([EventLoop::class], array_column(array_column($this->failures(), 'responsePayload'), 'errorType'));
        $this->assertSame([], $this->inboxOf('prospecting', 'prospecting-failing-reaction'));
    }

    /**
     * @param array<string, mixed> $invocation
     */
    #[DataProvider('unreadable')]
    public function testWhatTheWorkerCannotReadIsRefused(array $invocation): void
    {
        $this->consume($invocation);

        $this->assertSame([$invocation], array_column($this->failures(), 'requestPayload'));
    }

    /**
     * @return iterable<string, array{array<string, mixed>}>
     */
    public static function unreadable(): iterable
    {
        $detail = ['eventId' => self::CAUSE, 'occurredAt' => '2026-10-15T09:00:00.000000Z', 'correlationId' => self::CORRELATION, 'causationId' => null, 'depth' => 0, 'payload' => []];

        yield 'an unknown subscription' => [['subscription' => 'prospecting-unknown', 'source' => 'prospecter.prospecting.internal', 'detailType' => 'ObservationsWritten.v1', 'detail' => $detail]];
        yield 'an unknown version' => [['subscription' => 'prospecting-failing-reaction', 'source' => 'prospecter.prospecting.internal', 'detailType' => 'ObservationsWritten.v9', 'detail' => $detail]];
        yield 'a detail missing' => [['subscription' => 'prospecting-failing-reaction', 'source' => 'prospecter.prospecting.internal', 'detailType' => 'ObservationsWritten.v1']];
    }

    /**
     * @param array<string, mixed> $invocation
     */
    private function consume(array $invocation): void
    {
        self::getContainer()->get(IntegrationEventConsumer::class)->handle($invocation, Context::fake());
    }

    /**
     * The invocation the rule of the subscription gives the worker, by its input transformer.
     *
     * @return array{subscription: string, source: string, detailType: string, detail: array<string, mixed>}
     */
    private function invocationOf(string $subscription, object $event, int $depth = 0): array
    {
        $transported = self::getContainer()->get(EventCodec::class)->encode($event, new Cause(self::CORRELATION, self::CAUSE, $depth));
        $carried = json_decode(json_encode($transported->toEventBridge(), \JSON_THROW_ON_ERROR), true, flags: \JSON_THROW_ON_ERROR);

        return ['subscription' => $subscription, 'source' => $carried['source'], 'detailType' => $carried['detail-type'], 'detail' => $carried['detail']];
    }

    /**
     * A transaction cancelled by a condition carrying no refusal, or by another transaction on its items.
     */
    private static function conflict(): ConditionFailed
    {
        return ConditionFailed::in(new DynamoDbException('Transaction cancelled', new Command('TransactWriteItems'), ['code' => 'TransactionCanceledException', 'body' => ['CancellationReasons' => [['Code' => 'TransactionConflict']]]]));
    }

    private function rebuildTheFigures(): void
    {
        self::getContainer()->get(RebuildFiguresHandler::class)(new RebuildFigures());
    }

    private function figures(): FiguresResponse
    {
        $versions = self::getContainer()->get(ProjectionVersions::class);

        return self::getContainer()->get(FiguresRetriever::class)->of((int) $versions->active(), new AccountId(DemoData::SOLEAN_IT), Month::of(new \DateTimeImmutable()));
    }

    private function prospectingStopped(): ProspectingStopped
    {
        return new ProspectingStopped(new AccountId(DemoData::SOLEAN_IT), new MemberId(DemoData::PROSPECTOR), new OrganizationId($this->organization), new \DateTimeImmutable());
    }

    private function observationsWritten(): ObservationsWritten
    {
        return new ObservationsWritten(new AccountId(DemoData::SOLEAN_IT), new MemberId(DemoData::PROSPECTOR), new OrganizationId($this->organization), new \DateTimeImmutable());
    }

    private function contactForgotten(): ContactForgotten
    {
        return new ContactForgotten(DemoData::SOLEAN_IT, DemoData::PROSPECTOR, $this->contact, new \DateTimeImmutable());
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function inboxOf(string $table, string $subscription): array
    {
        return self::getContainer()->get('app.table.'.$table)->query('INBOX#'.$subscription);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function failures(): array
    {
        return self::getContainer()->get(InMemorySqs::class)->messagesOf(self::FAILURES);
    }

    private function reaction(): FailingReaction
    {
        return self::getContainer()->get(FailingReaction::class);
    }
}
