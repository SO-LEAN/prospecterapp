<?php

declare(strict_types=1);

namespace App\Connectors;

use App\Shared\CommandBus;
use App\Shared\ScheduledTask;
use Monolog\Attribute\WithMonologChannel;
use Prospecter\Core\Connectors\Application\Port\MailImportsRetriever;
use Prospecter\Core\Connectors\Application\RenewMailWatch\RenewMailWatch;
use Psr\Log\LoggerInterface;
use Symfony\Component\Clock\ClockInterface;

/**
 * Renews the mailbox watch of each active e-mail import, in every account (ADR 0154). An instance's daily schedule
 * runs it, and so does the console. Each renewal is a command of its own. When the provider refuses a renewal, its
 * import becomes to reconnect.
 */
#[WithMonologChannel('mailbox')]
final readonly class MailWatchesRenewer implements ScheduledTask
{
    private const string GOOGLE = 'google';

    public function __construct(
        private MailImportsRetriever $mailImports,
        private CommandBus $commands,
        private ClockInterface $clock,
        private LoggerInterface $logger,
    ) {
    }

    public function name(): string
    {
        return 'renew-gmail-watches';
    }

    public function run(): array
    {
        return $this->renew();
    }

    /**
     * A failing renewal is logged, and the others go on. It may fail because Google fails, or because its account's
     * topic refuses Gmail. No schedule retries it. The import stays active and is renewed the next day, before its week
     * ends.
     *
     * @return array{renewed: int, failed: int}
     */
    public function renew(): array
    {
        $now = $this->clock->now();
        $active = $this->mailImports->active();
        $failed = array_filter($active, fn (string $member): bool => !$this->renewed($member, $now));

        return ['renewed' => count($active) - count($failed), 'failed' => count($failed)];
    }

    private function renewed(string $member, \DateTimeImmutable $at): bool
    {
        try {
            $this->commands->dispatch(new RenewMailWatch($member, self::GOOGLE, $at));

            return true;
        } catch (\Exception $failure) {
            $this->logger->error('A member\'s mailbox watch could not be renewed', ['member' => $member, 'exception' => $failure]);

            return false;
        }
    }
}
