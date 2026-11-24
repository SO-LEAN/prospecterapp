<?php

declare(strict_types=1);

namespace Prospecter\PHPStan\EventStorming;

/**
 * The JSON document of a context's model, written from the facts the analysis read. An analysis that read no facts,
 * or could not read the code, gives no document.
 */
final readonly class ModelDocument
{
    private const int JSON = \JSON_PRETTY_PRINT | \JSON_UNESCAPED_SLASHES | \JSON_UNESCAPED_UNICODE | \JSON_THROW_ON_ERROR;

    public function __construct(private string $context, private string $features, private string $subscriptions)
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
        $model = new ContextStorming(Facts::read($facts), new FeatureTitles($this->features)->byUseCase(), $this->readSubscriptions())->model($this->context);

        return json_encode($model, self::JSON)."\n";
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
