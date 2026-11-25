<?php

declare(strict_types=1);

namespace Prospecter\PHPStan\EventStorming;

/**
 * The JSON document of every context's model, written from the facts the analysis read in the whole code. An analysis
 * that read no facts, or could not read the code, gives no document. Neither does a use case no feature names: its
 * post-it would have no French title.
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
     * @throws UntitledUseCases
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
        $untitled = array_merge(...array_map(self::untitledIn(...), $models));
        if ([] !== $untitled) {
            throw UntitledUseCases::named($untitled);
        }

        return json_encode(['models' => $models, 'warnings' => array_map(self::untagged(...), $features->untagged())], self::JSON)."\n";
    }

    /**
     * @return list<string> each use case of a model that no feature names, after its context
     */
    private static function untitledIn(Model $model): array
    {
        return array_values(array_map(
            static fn (Sticky $useCase): string => \sprintf('%s: %s', $model->context, $useCase->name),
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
