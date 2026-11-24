<?php

declare(strict_types=1);

namespace App\Demo;

use App\Reporting\RebuildOfTheFigures;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Counts the demonstration's figures once a command has loaded it. That command is the only one to write, so the
 * rebuild it is given counts its first attempt without waiting for the worker, and waits only on a retry (ADR 0209).
 */
final readonly class DemoFigures
{
    public function __construct(private RebuildOfTheFigures $rebuild)
    {
    }

    public function count(SymfonyStyle $io): void
    {
        $this->rebuild->run($io);
    }
}
