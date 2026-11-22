<?php

declare(strict_types=1);

namespace Tests\App\Double;

use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Middleware\MiddlewareInterface;
use Symfony\Component\Messenger\Middleware\StackInterface;

/**
 * A middleware of the command bus of the tests that fails the next command of a class, once a test asks it to, as its
 * use case would refuse or fail: before it runs, nothing written. It lets any other command through.
 */
final class FailingCommands implements MiddlewareInterface
{
    /** @var array<class-string, \Throwable> */
    private array $failures = [];

    /**
     * @param class-string $command
     */
    public function failTheNext(string $command, \Throwable $failure): void
    {
        $this->failures[$command] = $failure;
    }

    public function handle(Envelope $envelope, StackInterface $stack): Envelope
    {
        $command = $envelope->getMessage()::class;
        $failure = $this->failures[$command] ?? null;

        if (null !== $failure) {
            unset($this->failures[$command]);

            throw $failure;
        }

        return $stack->next()->handle($envelope, $stack);
    }
}
