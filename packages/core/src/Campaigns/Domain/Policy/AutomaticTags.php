<?php

declare(strict_types=1);

namespace Prospecter\Core\Campaigns\Domain\Policy;

/**
 * The names of the tags Prospecter sets by itself, never put by hand (ADR 0138): how a contact is reached, and where
 * an organization is, its department and its region. The names of the attraction areas join them with their table.
 */
final readonly class AutomaticTags
{
    private const array CHANNELS = ['EMAIL', 'PHONE', 'SMS', 'LINKEDIN'];
    private const string DEPARTMENT = '/^(\d{2}|97\d|2[AB])$/';
    private const array REGIONS = [
        'GUADELOUPE', 'MARTINIQUE', 'GUYANE', 'LA_REUNION', 'MAYOTTE', 'ILE_DE_FRANCE', 'CENTRE_VAL_DE_LOIRE',
        'BOURGOGNE_FRANCHE_COMTE', 'NORMANDIE', 'HAUTS_DE_FRANCE', 'GRAND_EST', 'PAYS_DE_LA_LOIRE', 'BRETAGNE',
        'NOUVELLE_AQUITAINE', 'OCCITANIE', 'AUVERGNE_RHONE_ALPES', 'PROVENCE_ALPES_COTE_D_AZUR', 'CORSE',
    ];

    #[\NoDiscard]
    public static function reserve(string $name): bool
    {
        return in_array($name, self::CHANNELS, true)
            || 1 === preg_match(self::DEPARTMENT, $name)
            || in_array($name, self::REGIONS, true);
    }
}
