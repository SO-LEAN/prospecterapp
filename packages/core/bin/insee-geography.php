<?php

declare(strict_types=1);

/*
 * Writes data/insee-geography.php, the tables the geographic tags are derived from (ADR 0142), from the base of the
 * attraction areas of the cities 2020 INSEE publishes (https://www.insee.fr/fr/information/4803954, Licence Ouverte
 * 2.0): one file per geography, the newest first, the former ones giving the codes of the municipalities merged since.
 *
 * php bin/insee-geography.php AAV2020_au_01-01-2026.xlsx AAV2020_au_01-01-2025.xlsx ... AAV2020_au_01-01-2020.xlsx
 */

const AREAS_SHEET = 'AAV2020';
const MUNICIPALITIES_SHEET = 'Composition_communale';
const HEADER_FIRST_CELL = ['AAV2020', 'CODGEO'];
const NOT_AN_AREA = '000';
// the population of an area in 2017, by tranche: 1 below 50,000 inhabitants, 2 to 5 above
const SMALL_AREAS = ['0', '1'];
const ARRONDISSEMENTS = [
    '75056' => ['75101', '75120'],
    '69123' => ['69381', '69389'],
    '13055' => ['13201', '13216'],
];
const REGIONS = [
    '01' => 'Guadeloupe', '02' => 'Martinique', '03' => 'Guyane', '04' => 'La Réunion', '06' => 'Mayotte',
    '11' => 'Île-de-France', '24' => 'Centre-Val de Loire', '27' => 'Bourgogne-Franche-Comté', '28' => 'Normandie',
    '32' => 'Hauts-de-France', '44' => 'Grand Est', '52' => 'Pays de la Loire', '53' => 'Bretagne',
    '75' => 'Nouvelle-Aquitaine', '76' => 'Occitanie', '84' => 'Auvergne-Rhône-Alpes',
    '93' => "Provence-Alpes-Côte d'Azur", '94' => 'Corse',
];
const TARGET = __DIR__.'/../data/insee-geography.php';

/**
 * @return list<array<string, string>> the rows of the sheet below its header, by the name of their column
 */
function rowsOf(string $workbook, string $sheet): array
{
    $zip = new ZipArchive();

    if (true !== $zip->open($workbook)) {
        throw new RuntimeException(sprintf('%s is no workbook', $workbook));
    }

    $strings = sharedStrings((string) $zip->getFromName('xl/sharedStrings.xml'));
    $xml = (string) $zip->getFromName(sheetPath($zip, $sheet));
    $zip->close();

    $reader = XMLReader::XML($xml);
    $header = null;
    $rows = [];

    while ($reader->read()) {
        if (XMLReader::ELEMENT !== $reader->nodeType || 'row' !== $reader->localName) {
            continue;
        }

        $row = cellsOf(new SimpleXMLElement($reader->readOuterXml()), $strings);

        if (null === $header) {
            $header = in_array($row['A'] ?? null, HEADER_FIRST_CELL, true) ? $row : null;

            continue;
        }

        if ('' !== ($row['A'] ?? '')) {
            $rows[] = array_combine(array_values($header), array_map(static fn (string $column): string => $row[$column] ?? '', array_keys($header)));
        }
    }

    return $rows;
}

/**
 * @return list<string>
 */
function sharedStrings(string $xml): array
{
    $strings = [];

    foreach (new SimpleXMLElement($xml)->si as $item) {
        $strings[] = isset($item->t) ? (string) $item->t : implode('', array_map(strval(...), iterator_to_array($item->r->t ?? [], false)));
    }

    return $strings;
}

function sheetPath(ZipArchive $zip, string $sheet): string
{
    $workbook = new SimpleXMLElement((string) $zip->getFromName('xl/workbook.xml'));
    $relations = new SimpleXMLElement((string) $zip->getFromName('xl/_rels/workbook.xml.rels'));

    foreach ($workbook->sheets->sheet as $candidate) {
        if ($sheet !== (string) $candidate['name']) {
            continue;
        }

        $id = (string) $candidate->attributes('r', true)['id'];

        foreach ($relations->Relationship as $relation) {
            if ($id === (string) $relation['Id']) {
                return 'xl/'.ltrim(str_replace('/xl/', '', (string) $relation['Target']), '/');
            }
        }
    }

    throw new RuntimeException(sprintf('No sheet %s', $sheet));
}

/**
 * @param list<string> $strings
 *
 * @return array<string, string> the values of the row by the letter of their column
 */
function cellsOf(SimpleXMLElement $row, array $strings): array
{
    $cells = [];

    foreach ($row->c as $cell) {
        $column = preg_replace('/\d+/', '', (string) $cell['r']);
        $value = (string) ($cell->v ?? $cell->is->t ?? '');
        $cells[$column] = 's' === (string) $cell['t'] ? $strings[(int) $value] : $value;
    }

    return $cells;
}

/**
 * @return array<string, string> the label of each area of 50,000 inhabitants or more, by its code
 */
function bigAreasOf(string $workbook): array
{
    $areas = [];

    foreach (rowsOf($workbook, AREAS_SHEET) as $area) {
        if (NOT_AN_AREA !== $area['AAV2020'] && !in_array($area['TAAV2017'], SMALL_AREAS, true)) {
            $areas[$area['AAV2020']] = $area['LIBAAV2020'];
        }
    }

    ksort($areas, SORT_STRING);

    return $areas;
}

/**
 * @param array<string, string> $areas
 * @param list<string>          $workbooks the newest first
 *
 * @return array{array<string, string>, array<string, string>} the area of each municipality of a big area, by its
 *                                                             code, in any geography since 2020, the newest winning; the region of each department
 */
function municipalitiesOf(array $areas, array $workbooks): array
{
    $municipalities = [];
    $departments = [];

    foreach ($workbooks as $workbook) {
        foreach (rowsOf($workbook, MUNICIPALITIES_SHEET) as $municipality) {
            $departments[$municipality['DEP']] ??= $municipality['REG'];
            $municipalities[$municipality['CODGEO']] ??= $municipality['AAV2020'];
        }
    }

    $municipalities = array_filter($municipalities, static fn (string $area): bool => isset($areas[$area]));

    foreach (ARRONDISSEMENTS as $city => [$first, $last]) {
        foreach (range((int) $first, (int) $last) as $arrondissement) {
            $municipalities[(string) $arrondissement] = $municipalities[$city];
        }
    }

    ksort($municipalities, SORT_STRING);
    ksort($departments, SORT_STRING);

    return [$municipalities, $departments];
}

/**
 * @param array<string, array<array-key, string>> $tables
 */
function exported(array $tables): string
{
    $lines = array_map(
        static fn (string $table, array $entries): string => sprintf("    '%s' => [\n%s    ],\n", $table, implode('', array_map(entryOf(...), array_keys($entries), $entries))),
        array_keys($tables),
        $tables,
    );

    return "[\n".implode('', $lines).']';
}

/**
 * A code stays a string, as INSEE writes it: '67482', never the integer PHP would make of it.
 */
function entryOf(int|string $code, string $value): string
{
    return sprintf("'%s'=>%s,\n", $code, var_export($value, true));
}

/**
 * @param list<string> $workbooks
 */
function write(array $workbooks): void
{
    $areas = bigAreasOf($workbooks[0]);
    [$municipalities, $departments] = municipalitiesOf($areas, $workbooks);
    $geography = sprintf('janvier %s', preg_match('/01-01-(\d{4})/', basename($workbooks[0]), $year) ? $year[1] : '?');

    file_put_contents(TARGET, sprintf(
        <<<'PHP'
            <?php

            // Generated by bin/insee-geography.php: never edit it by hand (ADR 0142).
            // Source : Insee, base des aires d'attraction des villes 2020, géographie au 1er %s (Licence Ouverte 2.0).
            // The areas of 50,000 inhabitants or more by their code, the area of each municipality of one of them by its
            // code (with the arrondissements of Paris, Lyon and Marseille and the codes of the municipalities merged since
            // 2020), the region of each department by its code, and the name of each region.

            return %s;

            PHP,
        $geography,
        exported(['areas' => $areas, 'municipalities' => $municipalities, 'departments' => $departments, 'regions' => REGIONS]),
    ));

    printf("%d areas, %d municipalities, %d departments: %s\n", count($areas), count($municipalities), count($departments), realpath(TARGET));
}

write(array_slice($argv, 1) ?: throw new InvalidArgumentException('The workbooks of INSEE, the newest first'));
