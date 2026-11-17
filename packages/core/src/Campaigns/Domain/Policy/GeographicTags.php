<?php

declare(strict_types=1);

namespace Prospecter\Core\Campaigns\Domain\Policy;

use Prospecter\Core\Campaigns\Domain\Model\Locality;
use Prospecter\Core\Campaigns\Domain\Model\Tag;

/**
 * The geographic tags of a municipality (ADR 0142): the attraction area of the cities of 50,000 inhabitants or more
 * it belongs to (INSEE zoning AAV 2020), its department and its region, read from the tables generated from INSEE
 * (`data/insee-geography.php`). An address whose country is given and is not French gets none, whatever the geocoder
 * found; a municipality outside a big area, or more recent than the tables, its department and its region only.
 */
final readonly class GeographicTags
{
    private const array FRENCH_COUNTRIES = ['FR', 'GP', 'MQ', 'GF', 'RE', 'YT'];
    private const string OVERSEAS = '97';
    private const int DEPARTMENT_LENGTH = 2;
    private const int OVERSEAS_DEPARTMENT_LENGTH = 3;
    private const string FRENCH_PART = ' (partie française)';
    private const string TABLES = __DIR__.'/../../../../data/insee-geography.php';

    /**
     * @return list<Tag> the city first, then the department and the region
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

    /**
     * Whether the name is that of an area or of a region, never put by hand.
     */
    #[\NoDiscard]
    public static function reserve(string $name): bool
    {
        static $names = null;
        $names ??= array_fill_keys(array_map(
            static fn (Tag $tag): string => $tag->name,
            [...array_map(self::areaTag(...), self::tables()['areas']), ...array_map(static fn (string $region): Tag => Tag::written(Tag::MARK.$region), self::tables()['regions'])],
        ), true);

        return isset($names[$name]);
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
     * @return array{areas: array<string, string>, municipalities: array<string, string>, departments: array<string, string>, regions: array<string, string>}
     */
    private static function tables(): array
    {
        static $tables = null;

        return $tables ??= require self::TABLES;
    }
}
