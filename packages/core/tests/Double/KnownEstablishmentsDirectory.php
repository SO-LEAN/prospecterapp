<?php

declare(strict_types=1);

namespace Tests\Core\Double;

use Prospecter\Core\Prospecting\Domain\Model\Establishment;
use Prospecter\Core\Prospecting\Domain\Port\EstablishmentDirectory;

/**
 * The public company directory as the scenarios see it. It holds the establishments they declare, in the order
 * they declare them. It finds an establishment when its name contains the name searched, whatever the case. It
 * stops answering for a while when a scenario says so.
 */
final class KnownEstablishmentsDirectory implements EstablishmentDirectory
{
    /** @var list<Establishment> */
    private array $establishments = [];
    private bool $answering = true;

    public function know(Establishment $establishment): void
    {
        $this->establishments[] = $establishment;
    }

    public function stopAnswering(): void
    {
        $this->answering = false;
    }

    public function named(string $name): array
    {
        $this->ensureAnswering();

        return array_values(array_filter($this->establishments, static fn (Establishment $establishment): bool => str_contains(mb_strtolower($establishment->name), mb_strtolower($name))));
    }

    public function namedIn(string $name, string $municipalityCode): array
    {
        return array_values(array_filter($this->named($name), static fn (Establishment $establishment): bool => $establishment->municipalityCode === $municipalityCode));
    }

    private function ensureAnswering(): void
    {
        if (!$this->answering) {
            throw new \RuntimeException('The directory does not answer');
        }
    }
}
