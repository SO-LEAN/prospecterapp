<?php

namespace App\Connectors;

use App\Shared\CommandBus;
use App\Shared\ScheduledTask;
use Prospecter\Core\Connectors\Application\Port\MailImportsRetriever;
use Prospecter\Core\Connectors\Application\RenewMailWatch\RenewMailWatch;
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
    ) {
    }

    public function name(): string
    {
        return 'renew-gmail-watches';
    }

    public function run(): array
    {
        return ['renewed' => $this->renew()];
    }

    /**
     * @return int how many it renewed
     */
    public function renew(): int
    {
        $now = $this->clock->now();
        $active = $this->mailImports->active();

        foreach ($active as $member) {
            $this->commands->dispatch(new RenewMailWatch($member, self::GOOGLE, $now));
        }

        return count($active);
    }
}
