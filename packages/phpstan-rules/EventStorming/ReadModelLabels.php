<?php

declare(strict_types=1);

namespace Prospecter\PHPStan\EventStorming;

/**
 * Gives each view of a context its own label. Views whose labels are the same are each named after their query
 * instead, wherever they stand, and a post-it of another wall that leads to one of them names it so. Views that still
 * share a label fail the generation.
 */
final readonly class ReadModelLabels
{
    /**
     * @param list<Model> $models
     *
     * @return list<Model>
     *
     * @throws SharedReadModelLabels
     */
    public static function distinct(array $models): array
    {
        $readModels = self::readModelsIn($models);
        $labels = self::queryLabelsOfShared($readModels);
        self::refuseShared(array_map(static fn (Sticky $readModel): Sticky => self::relabeled($readModel, $labels), $readModels));

        return array_map(static fn (Model $model): Model => $model->withStickies(array_map(static fn (Sticky $sticky): Sticky => self::relabeled($sticky, $labels), $model->stickies)), $models);
    }

    /**
     * @param list<Model> $models
     *
     * @return array<string, Sticky> each read model of the walls once, by place
     */
    private static function readModelsIn(array $models): array
    {
        $readModels = [];
        foreach ($models as $model) {
            foreach ($model->stickies as $sticky) {
                if (StickyType::ReadModel === $sticky->type) {
                    $readModels[self::placeOfView($sticky)] ??= $sticky;
                }
            }
        }

        return $readModels;
    }

    /**
     * @param array<string, Sticky> $readModels
     *
     * @return array<string, string> the label of each read model sharing its label with another, from its query, by place
     */
    private static function queryLabelsOfShared(array $readModels): array
    {
        $uses = array_count_values(array_map(self::labelInContext(...), $readModels));
        $shared = array_filter($readModels, static fn (Sticky $readModel): bool => $uses[self::labelInContext($readModel)] > 1);

        return array_map(static fn (Sticky $readModel): string => Wording::ofReadModel($readModel->name, null), $shared);
    }

    /**
     * @param array<string, Sticky> $readModels
     *
     * @throws SharedReadModelLabels naming the queries of each label that several read models of one context share
     */
    private static function refuseShared(array $readModels): void
    {
        $queries = [];
        foreach ($readModels as $readModel) {
            $queries[$readModel->context][$readModel->label][] = $readModel->name;
        }
        $shared = [];
        foreach ($queries as $context => $byLabel) {
            foreach (array_filter($byLabel, static fn (array $names): bool => \count($names) > 1) as $names) {
                sort($names);
                $shared[] = \sprintf('%s: %s', $context, implode(', ', $names));
            }
        }
        if ([] !== $shared) {
            throw SharedReadModelLabels::named($shared);
        }
    }

    /**
     * @param array<string, string> $labels
     */
    private static function relabeled(Sticky $sticky, array $labels): Sticky
    {
        $answers = array_map(static fn (Elsewhere $place): Elsewhere => $place->withLabel($labels[self::placeOf($place->context, $place->slug)] ?? $place->label), $sticky->answers);

        return $sticky->withLabel($labels[self::placeOfView($sticky)] ?? $sticky->label)->withAnswers($answers);
    }

    private static function labelInContext(Sticky $view): string
    {
        return $view->context.'/'.$view->label;
    }

    private static function placeOfView(Sticky $view): string
    {
        return self::placeOf($view->context, StickyType::ReadModel->value.':'.$view->name);
    }

    private static function placeOf(?string $context, ?string $slug): string
    {
        return $context.'/'.$slug;
    }
}
