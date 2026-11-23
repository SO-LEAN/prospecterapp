<?php

declare(strict_types=1);

namespace App\Shared\Messaging;

use Bref\Context\Context;
use Bref\Event\Handler;
use Bref\Event\Sqs\SqsEvent;
use Bref\Event\Sqs\SqsRecord;

/**
 * The local stack's worker (ADR 0131). MiniStack refuses an invocation beyond the worker's reserved concurrency, and
 * a rule does not retry it. AWS keeps the invocation until the worker has room. So locally, the rules put the
 * invocations in a queue. The queue's mapping gives them to the worker as many at once as its concurrency allows.
 * Each invocation is applied as the rule gives it on AWS. An invocation sent directly (app:integration:redrive) is
 * applied as it comes.
 *
 * When the worker throws a failure, the queue delivers the invocation again. At the last receive, the invocation goes
 * to the failures, the way Lambda keeps what it gives up after its two retries. When this consumer cannot decode a
 * body, it gives the worker null, and the worker refuses it.
 */
final readonly class QueuedIntegrationEventConsumer implements Handler
{
    /** the first attempt and Lambda's two retries */
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
     * @throws \Throwable the failure that the queue delivers again
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
