<?php

namespace App\Shared\Messaging;

use Bref\Context\Context;
use Bref\Event\Handler;
use Bref\Event\Sqs\SqsEvent;
use Bref\Event\Sqs\SqsRecord;

/**
 * The worker of the local stack (ADR 0129): MiniStack refuses an invocation beyond the reserved concurrency of the
 * worker, and a rule does not retry it, where AWS keeps it until the worker has room. Locally, the rules put the
 * invocations in a queue, whose mapping gives them to the worker as many at once as its concurrency: each is applied
 * as the rule gives it on AWS, and an invocation given directly (app:integration:redrive) as it comes.
 *
 * A failure the worker throws is given again by the queue, then, at its last receive, kept in the failures as Lambda
 * keeps what it gives up after its two retries.
 */
final readonly class QueuedIntegrationEventConsumer implements Handler
{
    /** the first attempt and the two retries of Lambda */
    private const int RECEIVES_OF_AN_INVOCATION = 3;

    public function __construct(private IntegrationEventConsumer $worker, private IntegrationFailures $failures)
    {
    }

    public function handle(mixed $event, Context $context): null
    {
        if (!self::isQueued($event)) {
            return $this->worker->handle($event, $context);
        }

        foreach (new SqsEvent($event)->getRecords() as $record) {
            $this->apply($record, $context);
        }

        return null;
    }

    /**
     * @throws \Throwable the failure the queue gives again
     */
    private function apply(SqsRecord $record, Context $context): void
    {
        $invocation = json_decode($record->getBody(), true);

        try {
            $this->worker->handle($invocation, $context);
        } catch (\Throwable $failure) {
            if ($record->getApproximateReceiveCount() < self::RECEIVES_OF_AN_INVOCATION) {
                throw $failure;
            }

            $this->failures->keep($invocation, $failure, $context, IntegrationFailures::RETRIES_EXHAUSTED);
        }
    }

    private static function isQueued(mixed $event): bool
    {
        return is_array($event) && array_key_exists('Records', $event);
    }
}
