<?php

namespace App\Shared\Messaging;

use App\Shared\Persistence\DynamoUnitOfWork;
use Bref\Context\Context;
use Bref\Event\Handler;
use Prospecter\Core\SharedKernel\Application\Exception\UseCaseRefused;
use Prospecter\Core\SharedKernel\Domain\Exception\BusinessException;
use Psr\Log\LoggerInterface;

/**
 * The worker: the function the rules of the subscriptions invoke with an event, which it applies once by the reaction
 * of the subscription, in one unit of work holding its inbox, what the reaction writes and what it publishes, the
 * outbox of the reaction carrying the cause it reacted to, one level deeper.
 *
 * A failure is sorted: an event delivered again ends the invocation normally; a failure another attempt would repeat
 * (a refusal, translated by the decorators of the reaction or detected at the commit, an event it cannot read, a
 * subscription it does not know, a loop) goes by the worker to the queue of the failures, without a retry; any other
 * is thrown for Lambda to retry, then to give up into the same queue. In doubt, a failure is retried.
 */
final readonly class IntegrationEventConsumer implements Handler
{
    public function __construct(
        private Subscriptions $subscriptions,
        private EventCodec $codec,
        private Causation $causation,
        private DynamoUnitOfWork $unitOfWork,
        private Inbox $inbox,
        private IntegrationFailures $failures,
        private LoggerInterface $logger,
    ) {
    }

    public function handle(mixed $event, Context $context): null
    {
        try {
            $this->apply(Invocation::of($event));
        } catch (AlreadyApplied $delivered) {
            $this->logger->info('Event delivered again, applied once: {message}', ['message' => $delivered->getMessage()] + Invocation::traceOf($event));
        } catch (\Throwable $failure) {
            $this->fail($failure, $event, $context);
        }

        return null;
    }

    private function apply(Invocation $invocation): void
    {
        $subscription = $this->subscriptions->named($invocation->subscription);
        EventLoop::ensureNotReachedBy($invocation->event);
        $this->unitOfWork->begin();

        try {
            $this->react($subscription, $invocation->event);
            $this->unitOfWork->commit();
        } finally {
            $this->unitOfWork->discard();
        }
    }

    private function react(Subscription $subscription, TransportedEvent $event): void
    {
        if ($subscription->inbox) {
            $this->inbox->receive($subscription, $event);
        }

        $reaction = $this->subscriptions->reactionOf($subscription);
        $decoded = $this->decode($event);
        $this->causation->within($event->causeOfAReaction(), static fn () => $reaction($decoded));
    }

    /**
     * @throws UnreadableEvent
     */
    private function decode(TransportedEvent $event): object
    {
        try {
            return $this->codec->decode($event);
        } catch (\Throwable $failure) {
            throw UnreadableEvent::because($failure);
        }
    }

    /**
     * @throws \Throwable the failure another attempt may not repeat, for Lambda to retry
     */
    private function fail(\Throwable $failure, mixed $invocation, Context $context): void
    {
        $this->logger->error('Event not applied: {message}', ['message' => $failure->getMessage(), 'exception' => $failure] + Invocation::traceOf($invocation) + self::reasonOf($failure));

        if (!self::isRepeatedByAnotherAttempt($failure)) {
            throw $failure;
        }

        $this->failures->keep($invocation, $failure, $context);
    }

    private static function isRepeatedByAnotherAttempt(\Throwable $failure): bool
    {
        return $failure instanceof UseCaseRefused
            || $failure instanceof BusinessException
            || $failure instanceof UnreadableEvent
            || $failure instanceof UnknownSubscription
            || $failure instanceof EventLoop;
    }

    /**
     * @return array{reason?: string, field?: string}
     */
    private static function reasonOf(\Throwable $failure): array
    {
        return $failure instanceof UseCaseRefused ? ['reason' => $failure->reason, 'field' => $failure->field] : [];
    }
}
