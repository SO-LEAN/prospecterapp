<?php

declare(strict_types=1);

namespace Prospecter\Core\Campaigns\Domain\Rule;

use Prospecter\Core\Campaigns\Domain\Model\Tag;
use Prospecter\Core\Campaigns\Domain\Model\TagFamily;

/**
 * The tags that Prospecter proposes to every account, by family, in the order proposed. They are only suggestions: an
 * account's tag may have the same value (ADR 0138).
 */
final readonly class PredefinedTags
{
    private const array BY_FAMILY = [
        TagFamily::OrganizationType->value => ['APPORTEUR', 'ESN', 'EDITEUR', 'FINAL', 'CABINET_RECRUTEMENT', 'PORTAGE', 'PARTENAIRE'],
        TagFamily::Relationship->value => ['CLIENT', 'DECIDEUR', 'TECH', 'RH', 'ACHATS', 'ANCIEN_COLLEGUE', 'RECOMMANDE'],
        TagFamily::Technology->value => [
            'PHP', 'SYMFONY', 'LARAVEL', 'JAVASCRIPT', 'TYPESCRIPT', 'REACT', 'VUE', 'ANGULAR', 'NODE', 'JAVA', 'DOTNET',
            'PYTHON', 'GO', 'AWS', 'AZURE', 'GCP', 'DOCKER', 'KUBERNETES', 'DEVOPS', 'DATA', 'IA',
        ],
    ];

    /**
     * @return array<string, list<string>> each family's tags, written with their `#`
     */
    #[\NoDiscard]
    public static function byFamily(): array
    {
        return array_map(static fn (array $names): array => array_map(static fn (string $name): string => Tag::MARK.$name, $names), self::BY_FAMILY);
    }

    #[\NoDiscard]
    public static function include(string $tag): bool
    {
        return array_any(self::byFamily(), static fn (array $tags): bool => in_array($tag, $tags, true));
    }
}
