<?php

declare(strict_types=1);

namespace Prospecter\PHPStan\EventStorming;

/**
 * The JSON document of every context's model, written from the facts the analysis read in the whole code. An analysis
 * that read no facts, or could not read the code, gives no document.
 */
final readonly class ModelDocument
{
    private const int JSON = \JSON_PRETTY_PRINT | \JSON_UNESCAPED_SLASHES | \JSON_UNESCAPED_UNICODE | \JSON_THROW_ON_ERROR;

    public function __construct(private string $features, private string $subscriptions)
    {
    }

    /**
     * @param array<mixed>|null $facts    the metadata of EventStormingFactsRule, null when the analysis gave none
     * @param list<string>      $failures what kept the analysis from reading the code
     *
     * @throws UnreadableCode
     */
    public function of(?array $facts, array $failures): string
    {
        if (null === $facts || [] !== $failures) {
            throw UnreadableCode::because($failures);
        }
        $facts = Facts::read($facts);
        $features = new FeatureTitles($this->features);
        $storming = new ContextStorming($facts, $features->byUseCase(), $this->readSubscriptions());
        $models = array_map($storming->model(...), $facts->contexts());
        $warnings = [...array_merge(...array_map(self::untitledIn(...), $models)), ...array_map(self::untagged(...), $features->untagged())];

        return json_encode(['models' => $models, 'warnings' => $warnings], self::JSON)."\n";
    }

    /**
     * @return list<string> a warning for each use case of a model that no feature names: its post-it has no French title
     */
    private static function untitledIn(Model $model): array
    {
        return array_values(array_map(
            static fn (Sticky $useCase): string => \sprintf('%s: no feature names the use case %s', $model->context, $useCase->name),
            array_filter($model->stickies, static fn (Sticky $sticky): bool => $sticky->isAUseCase() && null === $sticky->feature),
        ));
    }

    private static function untagged(string $feature): string
    {
        return \sprintf('The feature %s names no use case in its tags', $feature);
    }

    /**
     * @return array<array<mixed>>
     */
    private function readSubscriptions(): array
    {
        $subscriptions = json_decode((string) file_get_contents($this->subscriptions), true, flags: \JSON_THROW_ON_ERROR);

        return \is_array($subscriptions) ? array_filter($subscriptions, \is_array(...)) : [];
    }
}
