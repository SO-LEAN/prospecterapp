<?php

declare(strict_types=1);

namespace Tests\Core\Domain;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Prospecter\Core\Campaigns\Domain\Model\Locality;
use Prospecter\Core\Campaigns\Domain\Model\Tag;
use Prospecter\Core\Campaigns\Domain\Rule\AutomaticTags;

final class GeographicTagsTest extends TestCase
{
    /**
     * @return iterable<string, array{?string, ?string, string}>
     */
    public static function localities(): iterable
    {
        yield 'a municipality of Corsica' => ['2A004', 'FR', '#AJACCIO #2A #CORSE'];
        yield 'an overseas department' => ['97209', 'MQ', '#FORT_DE_FRANCE #972 #MARTINIQUE'];
        yield 'an arrondissement of Marseille, a double name' => ['13201', null, '#MARSEILLE_AIX_EN_PROVENCE #13 #PROVENCE_ALPES_COTE_D_AZUR'];
        yield 'a cross-border area, named after its foreign pole' => ['68297', 'FR', '#BALE_SAINT_LOUIS #68 #GRAND_EST'];
        yield 'a country written in lower case' => ['67482', 'fr', '#STRASBOURG #67 #GRAND_EST'];
        yield 'a municipality newer than the tables' => ['67999', 'FR', '#67 #GRAND_EST'];
        yield 'a code of no department' => ['00000', 'FR', ''];
        yield 'no municipality' => [null, 'FR', ''];
        yield 'another country' => ['67482', 'DE', ''];
    }

    #[DataProvider('localities')]
    public function testTheTagsOfALocality(?string $municipalityCode, ?string $country, string $tags): void
    {
        $this->assertSame($tags, implode(' ', array_map(strval(...), new Locality($municipalityCode, $country)->geographicTags())));
    }

    /**
     * @return iterable<string, array{string, bool}>
     */
    public static function names(): iterable
    {
        yield 'a big area' => ['SAINT_ETIENNE', true];
        yield 'a region' => ['ILE_DE_FRANCE', true];
        yield 'a department' => ['2B', true];
        yield 'a small area' => ['WISSEMBOURG', false];
        yield 'a tag of the account' => ['ESN', false];
    }

    #[DataProvider('names')]
    public function testTheNamesItReserves(string $name, bool $reserved): void
    {
        $this->assertSame($reserved, AutomaticTags::reserve($name));
        $this->assertSame($reserved, Tag::written(Tag::MARK.$name)->isAutomatic());
    }
}
