<?php

declare(strict_types=1);

namespace Tests\Core\Domain;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Prospecter\Core\Campaigns\Domain\Model\ContactTags;
use Prospecter\Core\Campaigns\Domain\Model\InheritedTag;
use Prospecter\Core\Campaigns\Domain\Model\Reach;
use Prospecter\Core\Campaigns\Domain\Model\Tag;
use Prospecter\Core\Campaigns\Domain\Model\TagSelection;
use Prospecter\Core\SharedKernel\Domain\OrganizationId;

final class ContactTagsTest extends TestCase
{
    private const string ACME = '019a0000-0000-7000-8000-000000000001';
    private const string GLOBEX = '019a0000-0000-7000-8000-000000000002';

    public function testAnInheritedTagNamesEveryOrganizationItComesFromInTheOrderOfTheirIdentities(): void
    {
        $tags = ContactTags::of([], [self::GLOBEX => self::tags('#ESN'), self::ACME => self::tags('#ESN', '#PHP')], Reach::none());

        $this->assertSame([['#ESN', [self::ACME, self::GLOBEX]], ['#PHP', [self::ACME]]], self::described($tags->inherited));
    }

    public function testATagCarriedByHandIsItsOwnShownOnce(): void
    {
        $tags = ContactTags::of(self::tags('#PHP'), [self::ACME => self::tags('#PHP', '#ESN')], Reach::none());

        $this->assertSame(['#PHP'], self::written($tags->own));
        $this->assertSame([['#ESN', [self::ACME]]], self::described($tags->inherited));
    }

    public function testEachListIsInTheOrderOfTheNamesOfTheTags(): void
    {
        $tags = ContactTags::of(self::tags('#TECH', '#CLIENT'), [self::ACME => self::tags('#PHP', '#ESN')], new Reach(true, true, true, true));

        $this->assertSame(['#CLIENT', '#TECH'], self::written($tags->own));
        $this->assertSame(['#ESN', '#PHP'], array_column(self::described($tags->inherited), 0));
        $this->assertSame(['#EMAIL', '#LINKEDIN', '#PHONE', '#SMS'], self::written($tags->automatic));
    }

    public function testAnOrganizationWithoutTagsGivesNone(): void
    {
        $this->assertSame([], ContactTags::of([], [self::ACME => []], Reach::none())->inherited);
    }

    #[DataProvider('selections')]
    public function testItsOwnInheritedAndAutomaticTagsCountAlikeForASelection(string $chosen, bool $carried): void
    {
        $tags = ContactTags::of(self::tags('#DECIDEUR'), [self::ACME => self::tags('#ESN')], new Reach(true, false, false, false));

        $this->assertSame($carried, $tags->carriesAll(TagSelection::of(explode(' ', $chosen))));
    }

    /**
     * @return iterable<string, array{string, bool}>
     */
    public static function selections(): iterable
    {
        yield 'its own' => ['#DECIDEUR', true];
        yield 'inherited' => ['#ESN', true];
        yield 'automatic' => ['#EMAIL', true];
        yield 'all three' => ['#DECIDEUR #ESN #EMAIL', true];
        yield 'one missing' => ['#ESN #PHONE', false];
        yield 'none carried' => ['#RH', false];
    }

    #[DataProvider('reaches')]
    public function testHowAContactIsReachedSetsItsAutomaticTags(Reach $reach, string $tags): void
    {
        $this->assertSame($tags, implode(' ', self::written($reach->automaticTags())));
    }

    /**
     * @return iterable<string, array{Reach, string}>
     */
    public static function reaches(): iterable
    {
        yield 'nothing' => [Reach::none(), ''];
        yield 'an e-mail' => [new Reach(true, false, false, false), '#EMAIL'];
        yield 'a landline' => [new Reach(false, true, false, false), '#PHONE'];
        yield 'a mobile' => [new Reach(false, true, true, false), '#PHONE #SMS'];
        yield 'a LinkedIn profile' => [new Reach(false, false, false, true), '#LINKEDIN'];
    }

    /**
     * @return list<Tag>
     */
    private static function tags(string ...$names): array
    {
        return array_values(array_map(Tag::written(...), $names));
    }

    /**
     * @param list<Tag> $tags
     *
     * @return list<string>
     */
    private static function written(array $tags): array
    {
        return array_map(static fn (Tag $tag): string => (string) $tag, $tags);
    }

    /**
     * @param list<InheritedTag> $inherited
     *
     * @return list<array{string, list<string>}>
     */
    private static function described(array $inherited): array
    {
        return array_map(static fn (InheritedTag $tag): array => [(string) $tag->tag, array_map(static fn (OrganizationId $organization): string => $organization->value, $tag->from)], $inherited);
    }
}
