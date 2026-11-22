<?php

declare(strict_types=1);

namespace Tests\App\Shared\Messaging;

use App\Shared\Messaging\Cause;
use App\Shared\Messaging\EventCodec;
use App\Shared\Typed;
use Tests\App\Double\FailingReaction;
use Tests\App\Double\InMemorySqs;

/**
 * A test giving the worker what the rule of a subscription gives it, then reading the inbox of the subscription and
 * the queue of the failures.
 */
trait InvokesTheWorker
{
    private const string FAILURES = 'prospecter-test-integration-failures';
    private const string CORRELATION = '0199f1d2-6a3b-7c4d-8e5f-0123456789ab';
    private const string CAUSE = '0199f1d2-6a3b-7c4d-8e5f-0123456789ac';

    /**
     * The invocation the rule of the subscription gives the worker, by its input transformer.
     *
     * @return array{subscription: string, source: string, detailType: string, detail: array<string, mixed>}
     */
    private function invocationOf(string $subscription, object $event, int $depth = 0): array
    {
        $transported = self::service(EventCodec::class)->encode($event, new Cause(self::CORRELATION, self::CAUSE, $depth));
        $carried = Typed::document(json_decode(json_encode($transported->toEventBridge(), \JSON_THROW_ON_ERROR), true, flags: \JSON_THROW_ON_ERROR));

        return ['subscription' => $subscription, 'source' => Typed::string($carried['source']), 'detailType' => Typed::string($carried['detail-type']), 'detail' => Typed::document($carried['detail'])];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function inboxOf(string $table, string $subscription): array
    {
        return self::tableOf($table)->query('INBOX#'.$subscription);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function failures(): array
    {
        return self::service(InMemorySqs::class)->messagesOf(self::FAILURES);
    }

    private function reaction(): FailingReaction
    {
        return self::service(FailingReaction::class);
    }
}
