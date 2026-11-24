<?php

declare(strict_types=1);

namespace Tests\App\Shared\Messaging;

use App\Shared\CommandBus;
use App\Shared\Messaging\PolicyReaction;
use Prospecter\Core\Accounts\Application\Member\RegisterTheFounder\RegisterTheFounder;
use Prospecter\Core\Accounts\Application\Policy\RegisterTheFounderPolicy;
use Prospecter\Core\Accounts\Domain\Event\AccountOpened;
use Prospecter\Core\Accounts\Domain\Model\ProfileId;
use Prospecter\Core\Booking\Application\Appointment\ForgetAppointmentOfErasedExchange\ForgetAppointmentOfErasedExchange;
use Prospecter\Core\Booking\Application\BookingPage\CloseBooking\CloseBooking;
use Prospecter\Core\Booking\Application\Policy\ForgetAppointmentOfErasedExchangePolicy;
use Prospecter\Core\Prospecting\Contract\Event\ExchangeFactsChanged;
use Prospecter\Core\SharedKernel\Application\Handler\Policy;
use Prospecter\Core\SharedKernel\Domain\Identity\AccountId;
use Prospecter\Core\SharedKernel\Domain\Identity\MemberId;
use Tests\App\Base\ApiTestCase;
use Tests\App\Double\FailingCommands;

/**
 * A policy's commands go to the command bus, each through the decorators of its handler (ADR 0203). The tests fail a
 * command before its use case runs, to see it sent.
 */
class PolicyReactionTest extends ApiTestCase
{
    public function testThePolicysCommandIsSent(): void
    {
        $sent = $this->failingWhenSent(RegisterTheFounder::class);

        $this->expectExceptionObject($sent);

        $this->react(new RegisterTheFounderPolicy(), new AccountOpened(new AccountId(self::newIdentity()), new MemberId(self::newIdentity()), new ProfileId(self::newIdentity()), self::now()));
    }

    public function testEachCommandOfAListIsSent(): void
    {
        $sent = $this->failingWhenSent(CloseBooking::class);

        $this->expectExceptionObject($sent);

        $this->react(self::policyReturning(self::registration(), self::closing()), new \stdClass());
    }

    public function testNoCommandSendsNothing(): void
    {
        $notSentYet = $this->failingWhenSent(ForgetAppointmentOfErasedExchange::class);

        $this->react(new ForgetAppointmentOfErasedExchangePolicy(), new ExchangeFactsChanged(self::newIdentity(), self::newIdentity(), 2, true, 'planned', null, self::now()));

        $this->expectExceptionObject($notSentYet);
        self::service(CommandBus::class)->dispatch(new ForgetAppointmentOfErasedExchange(self::newIdentity(), self::now()));
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

    private function react(Policy $policy, object $event): void
    {
        new PolicyReaction($policy, self::service(CommandBus::class))($event);
    }

    /**
     * A policy returning a list, as an event asking for several commands.
     */
    private static function policyReturning(object ...$commands): Policy
    {
        return new readonly class(array_values($commands)) implements Policy {
            /**
             * @param list<object> $commands
             */
            public function __construct(private array $commands)
            {
            }

            /**
             * @return list<object>
             */
            public function __invoke(object $event): array
            {
                return $this->commands;
            }
        };
    }

    private static function registration(): RegisterTheFounder
    {
        return new RegisterTheFounder(self::newIdentity(), self::newIdentity(), self::newIdentity(), self::now());
    }

    private static function closing(): CloseBooking
    {
        return new CloseBooking(self::newIdentity(), self::now());
    }

    private static function now(): \DateTimeImmutable
    {
        return new \DateTimeImmutable(self::THE_MOMENT_OF_THE_TESTS);
    }
}
