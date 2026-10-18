<?php

declare(strict_types=1);

namespace Tests\Core\Domain;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Prospecter\Core\SharedKernel\Domain\Email;
use Prospecter\Core\SharedKernel\Domain\SearchKey;
use Prospecter\Core\SharedKernel\Domain\SearchKeys;

class SearchKeyTest extends TestCase
{
    public function testAKeyIsInLowerCaseWithoutAccentsAndKeepsOnlyLettersAndDigits(): void
    {
        $this->assertSame('societegeneraledelectricite2', (string) SearchKey::of("Société Générale d'Électricité 2"));
    }

    public function testATextWithoutLetterNorDigitHasNoKey(): void
    {
        $this->assertNull(SearchKey::of(' @-. '));
        $this->assertNull(SearchKey::of(null));
    }

    #[DataProvider('searches')]
    public function testAnAggregateIsFoundByTheBeginningOfItsNameOrOfItsEmail(?string $search, bool $found): void
    {
        $this->assertSame($found, new SearchKeys('de La Fontaine', new Email('jean@fables.test'))->areFoundBy(SearchKey::of($search)));
    }

    /**
     * @return iterable<string, array{?string, bool}>
     */
    public static function searches(): iterable
    {
        yield 'nothing searched' => [null, true];
        yield 'the beginning of the name, its spaces and case aside' => ['De la FO', true];
        yield 'the beginning of the email' => ['jean@f', true];
        yield 'the middle of the name' => ['fontaine', false];
        yield 'the middle of the email' => ['fables', false];
    }
}
