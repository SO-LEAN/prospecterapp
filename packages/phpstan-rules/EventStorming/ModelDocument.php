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
        $storming = new ContextStorming($facts, new FeatureTitles($this->features)->byUseCase(), $this->readSubscriptions());
        $models = array_map($storming->model(...), $facts->contexts());

        return json_encode(['models' => $models], self::JSON)."\n";
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
