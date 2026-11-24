<?php

declare(strict_types=1);

namespace App\Prospecting\Command;

use App\Shared\CommandBus;
use Prospecter\Core\Prospecting\Application\Organization\LocateAgain\LocateAgain;
use Prospecter\Core\Prospecting\Application\Port\OrganizationsToLocateAgainRetriever;
use Symfony\Component\Clock\ClockInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Geocodes again the address of each organization located before Prospecter kept municipalities, to give it its
 * municipality (ADR 0142). The organization then carries its geographic tags. Run it once on an instance, after the
 * deployment that keeps municipalities. Each organization gets its own command. Run again, the command finds only the
 * organizations whose address the geocoder no longer finds, and counts them separately.
 */
#[AsCommand(name: 'app:organizations:locate-again', description: 'Geocode again the organizations that were located without their municipality')]
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
        $toLocate = [...$this->organizations->all()];

        foreach ($toLocate as $organization) {
            $this->commands->dispatch(new LocateAgain($organization, $this->clock->now()));
        }

        $notFound = count(array_intersect($toLocate, [...$this->organizations->all()]));
        $io->success(sprintf('%d organization(s) located again, %d whose address the geocoder no longer finds.', count($toLocate) - $notFound, $notFound));

        return Command::SUCCESS;
    }
}
