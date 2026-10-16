<?php

declare(strict_types=1);

namespace Tests\Core\Domain;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Prospecter\Core\SharedKernel\Domain\SearchTerms;

class SearchTermsTest extends TestCase
{
    public function testTheWordsAreInLowerCaseWithoutAccentsSplitOnAnythingButLettersAndDigits(): void
    {
        $this->assertSame(['societe', 'generale', 'd', 'electricite', 'contact', 'sge', '2', 'test'], SearchTerms::of("Société Générale d'Électricité", 'contact@sge-2.test')->words);
    }

    public function testAWordIsKeptOnce(): void
    {
        $this->assertSame(['acme'], SearchTerms::of('ACME', 'acme', null)->words);
    }

    #[DataProvider('searches')]
    public function testATextIsFoundWhenEachSearchedWordBeginsOneOfItsWords(string $search, bool $found): void
    {
        $this->assertSame($found, SearchTerms::of('ACME Corporation', 'contact@acme.test')->findBy(SearchTerms::of($search)));
    }

    /**
     * @return iterable<string, array{string, bool}>
     */
    public static function searches(): iterable
    {
        yield 'nothing searched' => ['', true];
        yield 'a beginning' => ['acm', true];
        yield 'two words, in any order' => ['corp ACME', true];
        yield 'a word of the email' => ['test', true];
        yield 'the middle of a word' => ['cme', false];
        yield 'one of the words missing' => ['acme initech', false];
    }

    public function testTheBeginningsOfEveryWordAreWhatAProjectionIndexes(): void
    {
        $this->assertSame(['a', 'ac', 'acm', 'acme', 'c', 'co'], SearchTerms::of('ACME co')->beginnings());
    }
}
