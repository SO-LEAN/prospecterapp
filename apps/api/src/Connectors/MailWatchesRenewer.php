<?php

namespace App\Connectors;

use App\Shared\CommandBus;
use App\Shared\ScheduledTask;
use Prospecter\Core\Connectors\Application\Port\MailImportsRetriever;
use Prospecter\Core\Connectors\Application\RenewMailWatch\RenewMailWatch;
use Psr\Log\LoggerInterface;
use Symfony\Component\Clock\ClockInterface;

/**
 * Gives each import of the e-mails active, in every account, the renewal of the watch of its mailbox (ADR 0154): what
 * the daily schedule of an instance runs, and the console. Each is a command of its own, a renewal the provider
 * refuses leaving its import to reconnect.
 */
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
     * A renewal failing, Google failing or the topic of its account refusing Gmail, is logged and the others go on: no
     * schedule retries, and the failing import stays active, renewed the next day before its week ends.
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
            $this->logger->error('The watch of the mailbox of a member could not be renewed', ['member' => $member, 'exception' => $failure]);

            return false;
        }
    }
}
