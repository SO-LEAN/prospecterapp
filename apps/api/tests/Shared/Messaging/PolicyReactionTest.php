<?php

declare(strict_types=1);

namespace Tests\App\Shared\Messaging;

use App\Shared\CommandBus;
use App\Shared\Messaging\PolicyReaction;
use Prospecter\Core\Accounts\Application\RegisterTheFounder\RegisterTheFounder;
use Prospecter\Core\Booking\Application\CloseBooking\CloseBooking;
use Prospecter\Core\SharedKernel\Application\Handler\Policy;
use Tests\App\Base\ApiTestCase;
use Tests\App\Double\FailingCommands;

/**
 * A policy's commands go to the command bus, each through the decorators of its handler (ADR 0202). The tests fail a
 * command before its use case runs, to see it sent.
 */
class PolicyReactionTest extends ApiTestCase
{
    public function testThePolicysCommandIsSent(): void
    {
        $sent = $this->failingWhenSent(RegisterTheFounder::class);

        $this->expectExceptionObject($sent);

        $this->react(static fn (): object => self::registration());
    }

    public function testEachCommandOfAListIsSent(): void
    {
        $sent = $this->failingWhenSent(CloseBooking::class);

        $this->expectExceptionObject($sent);

        $this->react(static fn (): array => [self::registration(), self::closing()]);
    }

    public function testNoCommandSendsNothing(): void
    {
        $notSentYet = $this->failingWhenSent(RegisterTheFounder::class);

        $this->react(static fn (): ?object => null);

        $this->expectExceptionObject($notSentYet);
        self::service(CommandBus::class)->dispatch(self::registration());
    }

    /**
     * @param class-string $command
     */
    private function failingWhenSent(string $command): \RuntimeException
    {
        $failure = new \RuntimeException($command.' sent');
        self::service(FailingCommands::class)->failTheNext($command, $failure);

        return $failure;
    }

    /**
     * @param \Closure(): (object|list<object>|null) $commands what the policy returns
     */
    private function react(\Closure $commands): void
    {
        $policy = new readonly class($commands) implements Policy {
            /**
             * @param \Closure(): (object|list<object>|null) $commands
             */
            public function __construct(private \Closure $commands)
            {
            }

            /**
             * @return object|list<object>|null
             */
            public function __invoke(object $event): object|array|null
            {
                return ($this->commands)();
            }
        };

        new PolicyReaction($policy, self::service(CommandBus::class))(new \stdClass());
    }

    private static function registration(): RegisterTheFounder
    {
        return new RegisterTheFounder(self::newIdentity(), self::newIdentity(), self::newIdentity(), new \DateTimeImmutable(self::THE_MOMENT_OF_THE_TESTS));
    }

    private static function closing(): CloseBooking
    {
        return new CloseBooking(self::newIdentity(), new \DateTimeImmutable(self::THE_MOMENT_OF_THE_TESTS));
    }
}
