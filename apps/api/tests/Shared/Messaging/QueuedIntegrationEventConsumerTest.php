<?php

namespace Tests\App\Shared\Messaging;

use App\Demo\DemoData;
use App\Shared\Messaging\IntegrationFailures;
use App\Shared\Messaging\QueuedIntegrationEventConsumer;
use Bref\Context\Context;
use Prospecter\Core\Prospecting\Domain\Event\ObservationsWritten;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\MemberId;
use Prospecter\Core\SharedKernel\Domain\OrganizationId;
use Tests\App\Base\ApiTestCase;

/**
 * Locally, the rules of the subscriptions put the invocations of the worker in a queue, from which the worker takes
 * them as many at once as its reserved concurrency: one beyond it waits in the queue instead of being refused and
 * lost. The worker applies each as the rule gives it on AWS, a failure given again by the queue, then kept in the
 * failures as Lambda keeps what it gives up after its two retries.
 */
class QueuedIntegrationEventConsumerTest extends ApiTestCase
{
    use InvokesTheWorker;

    private const string SUBSCRIPTION = 'prospecting-failing-reaction';
    private const int LAST_RECEIVE = 3;

    public function testAQueuedInvocationIsAppliedAsTheRuleGivesIt(): void
    {
        $invocation = $this->invocationOf(self::SUBSCRIPTION, $this->observationsWritten());

        $this->consume(self::queued($invocation, receivedTimes: 1));

        $this->assertSame([$invocation['detail']['eventId']], array_column($this->inboxOf('prospecting', self::SUBSCRIPTION), 'SK'));
        $this->assertSame([], $this->failures());
    }

    public function testAnInvocationGivenDirectlyIsAppliedToo(): void
    {
        $invocation = $this->invocationOf(self::SUBSCRIPTION, $this->observationsWritten());

        $this->consume($invocation);

        $this->assertCount(1, $this->inboxOf('prospecting', self::SUBSCRIPTION));
    }

    public function testAFailureBeforeTheLastReceiveIsThrownForTheQueueToGiveItAgain(): void
    {
        $this->reaction()->failWith(new \RuntimeException('The table does not answer'));
        $invocation = $this->invocationOf(self::SUBSCRIPTION, $this->observationsWritten());

        try {
            $this->consume(self::queued($invocation, receivedTimes: self::LAST_RECEIVE - 1));
            $this->fail('the failure was not thrown');
        } catch (\RuntimeException $failure) {
            $this->assertSame('The table does not answer', $failure->getMessage());
        }

        $this->assertSame([], $this->failures());
    }

    public function testAFailureAtTheLastReceiveIsKeptInTheFailuresAsLambdaKeepsWhatItGivesUp(): void
    {
        $this->reaction()->failWith(new \RuntimeException('The table does not answer'));
        $invocation = $this->invocationOf(self::SUBSCRIPTION, $this->observationsWritten());

        $this->consume(self::queued($invocation, receivedTimes: self::LAST_RECEIVE));

        $failures = $this->failures();
        $this->assertCount(1, $failures);
        $this->assertSame($invocation, $failures[0]['requestPayload']);
        $this->assertSame(IntegrationFailures::RETRIES_EXHAUSTED, $failures[0]['requestContext']['condition']);
        $this->assertSame('The table does not answer', $failures[0]['responsePayload']['errorMessage']);
    }

    public function testARefusalIsKeptOnceWithoutARetry(): void
    {
        $invocation = ['subscription' => 'prospecting-unknown'] + $this->invocationOf(self::SUBSCRIPTION, $this->observationsWritten());

        $this->consume(self::queued($invocation, receivedTimes: 1));

        $this->assertSame([IntegrationFailures::REFUSED], array_column(array_column($this->failures(), 'requestContext'), 'condition'));
    }

    private function consume(mixed $event): void
    {
        self::getContainer()->get(QueuedIntegrationEventConsumer::class)->handle($event, Context::fake());
    }

    /**
     * The event the mapping of the queue gives the worker: one message, the invocation as the rule put it there.
     *
     * @param array<string, mixed> $invocation
     *
     * @return array{Records: list<array<string, mixed>>}
     */
    private static function queued(array $invocation, int $receivedTimes): array
    {
        return ['Records' => [[
            'messageId' => self::newIdentity(),
            'receiptHandle' => 'receipt',
            'body' => json_encode($invocation, \JSON_THROW_ON_ERROR),
            'attributes' => ['ApproximateReceiveCount' => (string) $receivedTimes],
            'messageAttributes' => [],
            'eventSource' => 'aws:sqs',
        ]]];
    }

    private function observationsWritten(): ObservationsWritten
    {
        return new ObservationsWritten(new AccountId(DemoData::SOLEAN_IT), new MemberId(DemoData::PROSPECTOR), new OrganizationId(self::newIdentity()), new \DateTimeImmutable());
    }
}
