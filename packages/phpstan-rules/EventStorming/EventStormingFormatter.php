<?php

declare(strict_types=1);

namespace Prospecter\PHPStan\EventStorming;

use PHPStan\Analyser\Error;
use PHPStan\Analyser\InternalError;
use PHPStan\Command\AnalysisResult;
use PHPStan\Command\ErrorFormatter\ErrorFormatter;
use PHPStan\Command\Output;

/**
 * Writes a context's model (ModelDocument) from what the analysis gives (event-storming.neon). The errors of the
 * other rules an extension brings are lint's business, and are left out.
 */
final readonly class EventStormingFormatter implements ErrorFormatter
{
    public function __construct(private ModelDocument $document)
    {
    }

    public function formatErrors(AnalysisResult $analysisResult, Output $output): int
    {
        $facts = array_find($analysisResult->getFileSpecificErrors(), static fn (Error $error): bool => EventStormingFactsRule::IDENTIFIER === $error->getIdentifier());
        $failures = [...$analysisResult->getNotFileSpecificErrors(), ...array_map(static fn (InternalError $error): string => $error->getMessage(), $analysisResult->getInternalErrorObjects())];
        try {
            $output->writeRaw($this->document->of($facts?->getMetadata(), $failures));

            return 0;
        } catch (UnreadableCode $unreadable) {
            $output->writeLineFormatted($unreadable->getMessage());

            return 1;
        }
    }
}
