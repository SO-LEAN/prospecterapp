<?php

namespace App\Shared\Messaging;

use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Middleware\MiddlewareInterface;
use Symfony\Component\Messenger\Middleware\StackInterface;

/**
 * Every event a command and its reactions publish carries the same correlation, given when the command is sent.
 */
final readonly class CorrelatedCommands implements MiddlewareInterface
{
    public function __construct(private Causation $causation)
    {
    }

    public function handle(Envelope $envelope, StackInterface $stack): Envelope
    {
        return $this->causation->withinACommand(static fn (): Envelope => $stack->next()->handle($envelope, $stack));
    }
}
