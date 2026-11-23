<?php

declare(strict_types=1);

namespace Prospecter\Core\Campaigns\Domain\Rule;

use Prospecter\Core\Campaigns\Domain\Model\Locality;
use Prospecter\Core\Campaigns\Domain\Model\Tag;

/**
 * A municipality's geographic tags (ADR 0142). They are the attraction area of cities of 50,000 inhabitants or more
 * that it belongs to (INSEE zoning AAV 2020), its department and its region. They are read from the tables generated
 * from INSEE data (`data/insee-geography.php`). An address whose country is given and is not French gets none, whatever
 * the geocoder found. A municipality outside a big area, or more recent than the tables, gets only its department and
 * region.
 */
final class GeographicTags
{
    private const array FRENCH_COUNTRIES = ['FR', 'GP', 'MQ', 'GF', 'RE', 'YT'];
    private const string OVERSEAS = '97';
    private const int DEPARTMENT_LENGTH = 2;
    private const int OVERSEAS_DEPARTMENT_LENGTH = 3;
    private const string FRENCH_PART = ' (partie française)';
    private const string TABLES = __DIR__.'/../../../../data/insee-geography.php';

    /** @var array{areas: array<string>, municipalities: array<string>, departments: array<string>, regions: array<string>}|null read once */
    private static ?array $tables = null;
    /** @var array<string, true>|null the areas' and regions' names, read once */
    private static ?array $reserved = null;

    /**
     * @return list<Tag> the city, then the department and the region
     */
    public static function of(Locality $locality): array
    {
        if (null === $locality->municipalityCode || !self::isFrench($locality->country)) {
            return [];
        }

        $tables = self::tables();
        $area = $tables['municipalities'][$locality->municipalityCode] ?? null;
        $department = self::departmentOf($locality->municipalityCode);
        $region = $tables['departments'][$department] ?? null;

        return null === $region ? [] : [
            ...(null === $area ? [] : [self::areaTag($tables['areas'][$area])]),
            Tag::written(Tag::MARK.$department),
            Tag::written(Tag::MARK.$tables['regions'][$region]),
        ];
    }

    /** Whether the name is an area's or a region's. Such a tag is never put by hand. */
    #[\NoDiscard]
    public static function reserve(string $name): bool
    {
        self::$reserved ??= array_fill_keys(array_map(
            static fn (Tag $tag): string => $tag->name,
            [...array_map(self::areaTag(...), self::tables()['areas']), ...array_map(static fn (string $region): Tag => Tag::written(Tag::MARK.$region), self::tables()['regions'])],
        ), true);

        return isset(self::$reserved[$name]);
    }

    private static function isFrench(?string $country): bool
    {
        return null === $country || in_array(mb_strtoupper($country), self::FRENCH_COUNTRIES, true);
    }

    private static function departmentOf(string $municipalityCode): string
    {
        return substr($municipalityCode, 0, str_starts_with($municipalityCode, self::OVERSEAS) ? self::OVERSEAS_DEPARTMENT_LENGTH : self::DEPARTMENT_LENGTH);
    }

    private static function areaTag(string $label): Tag
    {
        return Tag::written(Tag::MARK.str_replace(self::FRENCH_PART, '', $label));
    }

    /**
     * @return array{areas: array<string>, municipalities: array<string>, departments: array<string>, regions: array<string>}
     */
    private static function tables(): array
    {
        return self::$tables ??= self::read(require self::TABLES);
    }

    /**
     * @return array{areas: array<string>, municipalities: array<string>, departments: array<string>, regions: array<string>}
     */
    private static function read(mixed $tables): array
    {
        return [
            'areas' => self::labels($tables, 'areas'),
            'municipalities' => self::labels($tables, 'municipalities'),
            'departments' => self::labels($tables, 'departments'),
            'regions' => self::labels($tables, 'regions'),
        ];
    }

    /**
     * @return array<string> by code
     */
    private static function labels(mixed $tables, string $table): array
    {
        $labels = is_array($tables) ? $tables[$table] ?? null : null;

        return is_array($labels)
            ? array_map(static fn (mixed $label): string => is_string($label) ? $label : throw new \LogicException(sprintf('The table %s of %s holds no label', $table, self::TABLES)), $labels)
            : throw new \LogicException(sprintf('%s has no table %s', self::TABLES, $table));
    }
}
