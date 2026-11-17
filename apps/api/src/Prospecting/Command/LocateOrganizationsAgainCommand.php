<?php

namespace App\Prospecting\Command;

use App\Shared\CommandBus;
use Prospecter\Core\Prospecting\Application\LocateAgain\LocateAgain;
use Prospecter\Core\Prospecting\Application\Port\OrganizationsToLocateAgainRetriever;
use Symfony\Component\Clock\ClockInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Gives the organizations located before their municipality was kept their municipality, their address geocoded again
 * (ADR 0142), so that they carry their geographic tags: once on an instance, after the deployment that keeps it. Each
 * organization is a command of its own; run again, it finds none left, but those whose address the geocoder no longer
 * finds, which it counts apart.
 */
#[AsCommand(name: 'app:organizations:locate-again', description: 'Geocode again the organizations located without their municipality')]
final readonly class LocateOrganizationsAgainCommand
{
    public function __construct(
        private OrganizationsToLocateAgainRetriever $organizations,
        private CommandBus $commands,
        private ClockInterface $clock,
    ) {
    }

    public function __invoke(SymfonyStyle $io): int
    {
        $toLocate = 0;

        foreach ($this->organizations->all() as $organization) {
            $this->commands->dispatch(new LocateAgain($organization, $this->clock->now()));
            ++$toLocate;
        }

        $notFound = iterator_count($this->organizations->all());
        $io->success(sprintf('%d organization(s) located again, %d whose address the geocoder no longer finds.', $toLocate - $notFound, $notFound));

        return Command::SUCCESS;
    }
}
