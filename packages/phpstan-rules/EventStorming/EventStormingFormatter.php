<?php

declare(strict_types=1);

namespace Prospecter\PHPStan\EventStorming;

use PHPStan\Analyser\Error;
use PHPStan\Command\AnalysisResult;
use PHPStan\Command\ErrorFormatter\ErrorFormatter;
use PHPStan\Command\Output;

/**
 * Writes the event storming of a context, as JSON, from the facts the analysis read (event-storming.neon). The
 * errors of the other rules an extension brings are lint's business, and are left out. An analysis that could not
 * read the code writes no model, and fails.
 */
final readonly class EventStormingFormatter implements ErrorFormatter
{
    private const int JSON = \JSON_PRETTY_PRINT | \JSON_UNESCAPED_SLASHES | \JSON_UNESCAPED_UNICODE | \JSON_THROW_ON_ERROR;

    public function __construct(private string $context, private string $features, private string $subscriptions)
    {
    }

    public function formatErrors(AnalysisResult $analysisResult, Output $output): int
    {
        $facts = array_find($analysisResult->getFileSpecificErrors(), static fn (Error $error): bool => EventStormingFactsRule::IDENTIFIER === $error->getIdentifier());
        $failures = [...$analysisResult->getNotFileSpecificErrors(), ...array_map(static fn ($error): string => $error->getMessage(), $analysisResult->getInternalErrorObjects())];
        if (null === $facts || [] !== $failures) {
            $output->writeLineFormatted(implode("\n", ['The code could not be read for the event storming:', ...$failures]));

            return 1;
        }
        $model = new ContextStorming(Facts::read($facts->getMetadata()), new FeatureTitles($this->features)->byUseCase(), $this->readSubscriptions())->model($this->context);
        $output->writeRaw(json_encode($model, self::JSON)."\n");

        return 0;
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function readSubscriptions(): array
    {
        $subscriptions = json_decode((string) file_get_contents($this->subscriptions), true, flags: \JSON_THROW_ON_ERROR);

        /** @var array<string, array<string, mixed>> */
        return \is_array($subscriptions) ? $subscriptions : [];
    }
}
