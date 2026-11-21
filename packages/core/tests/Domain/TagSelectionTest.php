<?php

declare(strict_types=1);

namespace Tests\Core\Domain;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Prospecter\Core\Campaigns\Domain\Exception\InvalidTag;
use Prospecter\Core\Campaigns\Domain\Exception\InvalidTagSelection;
use Prospecter\Core\Campaigns\Domain\Model\Tag;
use Prospecter\Core\Campaigns\Domain\Model\TagSelection;

final class TagSelectionTest extends TestCase
{
    /**
     * @param list<string> $typed
     */
    #[DataProvider('selections')]
    public function testEachTagChosenIsWrittenAsATagAndChosenOnce(array $typed, string $chosen): void
    {
        $this->assertSame($chosen, implode(' ', array_map(static fn (Tag $tag): string => (string) $tag, TagSelection::of($typed)->tags)));
    }

    /**
     * @return iterable<string, array{list<string>, string}>
     */
    public static function selections(): iterable
    {
        yield 'one' => [['#ESN'], '#ESN'];
        yield 'as typed' => [['esn', 'éditeur logiciel'], '#ESN #EDITEUR_LOGICIEL'];
        yield 'five' => [['#A', '#B', '#C', '#D', '#E'], '#A #B #C #D #E'];
        yield 'twice' => [['#ESN', 'esn'], '#ESN'];
        yield 'six typed, five once written' => [['#A', '#B', '#C', '#D', '#E', '#a'], '#A #B #C #D #E'];
    }

    /**
     * @param list<string> $typed
     */
    #[DataProvider('outOfRange')]
    public function testFromOneToFiveTagsAreChosen(array $typed): void
    {
        $this->expectException(InvalidTagSelection::class);
        $this->expectExceptionMessageIs('Choose from 1 to 5 tags');

        TagSelection::of($typed);
    }

    /**
     * @return iterable<string, array{list<string>}>
     */
    public static function outOfRange(): iterable
    {
        yield 'none' => [[]];
        yield 'six' => [['#A', '#B', '#C', '#D', '#E', '#F']];
    }

    public function testWhatMakesNoTagIsRefused(): void
    {
        $this->expectException(InvalidTag::class);

        TagSelection::of(['#ESN', '###']);
    }

    public function testASelectionIsAmongTheTagsCarriedWhenEachOfItsTagsIs(): void
    {
        $selection = TagSelection::of(['#ESN', '#EMAIL']);

        $this->assertTrue($selection->isAllAmong([Tag::written('#EMAIL'), Tag::written('#PHP'), Tag::written('#ESN')]));
        $this->assertFalse($selection->isAllAmong([Tag::written('#ESN')]));
    }
}
