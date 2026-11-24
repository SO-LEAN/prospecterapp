<?php

declare(strict_types=1);

namespace Prospecter\PHPStan\EventStorming;

/**
 * The title of the feature of each use case. A feature names its use cases by its tags (@OpenBooking).
 */
final readonly class FeatureTitles
{
    private const string TITLE = '/^\s*Fonctionnalité:\s*(.+?)\s*$/mu';

    private const string TAG = '/^\s*(@.+?)\s*$/mu';

    public function __construct(private string $directory)
    {
    }

    /**
     * @return array<string, string> the titles by the short name of their use case
     */
    public function byUseCase(): array
    {
        $titles = [];
        foreach ($this->features() as ['title' => $title, 'useCases' => $useCases]) {
            foreach ($useCases as $useCase) {
                $titles[$useCase] = $title;
            }
        }

        return $titles;
    }

    /**
     * @return list<string> the files of the features that name no use case by its tag
     */
    public function untagged(): array
    {
        return array_keys(array_filter($this->features(), static fn (array $feature): bool => [] === $feature['useCases']));
    }

    /**
     * @return array<string, array{title: string, useCases: list<string>}> each feature with a title, by its file
     */
    private function features(): array
    {
        $features = [];
        foreach (glob($this->directory.'/*.feature') ?: [] as $feature) {
            $text = (string) file_get_contents($feature);
            if (1 === preg_match(self::TITLE, $text, $title, \PREG_OFFSET_CAPTURE)) {
                $features[basename($feature)] = ['title' => $title[1][0], 'useCases' => self::useCasesTaggedIn(substr($text, 0, $title[0][1]))];
            }
        }

        return $features;
    }

    /**
     * @return list<string>
     */
    private static function useCasesTaggedIn(string $header): array
    {
        preg_match_all(self::TAG, $header, $lines);

        return array_map(
            static fn (string $tag): string => ltrim($tag, '@'),
            array_merge(...array_map(static fn (string $line): array => preg_split('/\s+/', $line) ?: [], $lines[1])),
        );
    }
}
