<?php

declare(strict_types=1);

namespace Tests\Core\Domain;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Prospecter\Core\Campaigns\Domain\Exception\InvalidTag;
use Prospecter\Core\Campaigns\Domain\Model\Tag;

class TagTest extends TestCase
{
    #[DataProvider('typed')]
    public function testATagIsWrittenOneWay(string $typed, string $written): void
    {
        $this->assertSame($written, (string) Tag::written($typed));
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function typed(): iterable
    {
        yield 'with its mark' => ['#ESN', '#ESN'];
        yield 'without its mark, in lower case' => ['esn', '#ESN'];
        yield 'its accents removed' => ['Éditeur', '#EDITEUR'];
        yield 'spaces and hyphens an underscore' => ['cabinet de recrutement', '#CABINET_DE_RECRUTEMENT'];
        yield 'a run of separators one underscore' => ['Salon - 2026', '#SALON_2026'];
        yield 'separators at its ends dropped' => ['  #-php- ', '#PHP'];
        yield 'its underscores kept' => ['#ANCIEN_COLLEGUE', '#ANCIEN_COLLEGUE'];
        yield 'thirty characters' => [str_repeat('A', 30), '#'.str_repeat('A', 30)];
    }

    #[DataProvider('noTags')]
    public function testWhatMakesNoTagIsRefused(string $typed): void
    {
        $this->expectException(InvalidTag::class);

        Tag::written($typed);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function noTags(): iterable
    {
        yield 'nothing' => [''];
        yield 'separators only' => ['# - !'];
        yield 'thirty-one characters' => [str_repeat('A', 31)];
    }

    #[DataProvider('reserved')]
    public function testTheAutomaticTagsAreReserved(string $typed, bool $automatic): void
    {
        $this->assertSame($automatic, Tag::written($typed)->isAutomatic());
    }

    /**
     * @return iterable<string, array{string, bool}>
     */
    public static function reserved(): iterable
    {
        yield 'a channel' => ['#LINKEDIN', true];
        yield 'a department of the mainland' => ['#67', true];
        yield 'a department of Corsica' => ['#2B', true];
        yield 'a department overseas' => ['#974', true];
        yield 'a region' => ['#PROVENCE_ALPES_COTE_D_AZUR', true];
        yield 'a predefined tag' => ['#ESN', false];
        yield 'a year' => ['#2026', false];
        yield 'a tag beginning like a channel' => ['#EMAILING', false];
    }
}
