<?php

declare(strict_types=1);

namespace Tests\Core\Domain;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Prospecter\Core\Booking\Domain\Exception\IncompleteBooker;
use Prospecter\Core\Booking\Domain\Model\Booker;

final class BookerTest extends TestCase
{
    #[DataProvider('incomplete')]
    public function testIsRefusedWithoutOneOfItsFields(string $firstName, string $lastName, string $phone, string $email): void
    {
        $this->expectException(IncompleteBooker::class);

        Booker::named($firstName, $lastName, $phone, $email);
    }

    /**
     * @return iterable<string, array{string, string, string, string}>
     */
    public static function incomplete(): iterable
    {
        yield 'no first name' => ['', 'Lovelace', '06 12 34 56 78', 'ada@acme.fr'];
        yield 'no last name' => ['Ada', '', '06 12 34 56 78', 'ada@acme.fr'];
        yield 'no phone' => ['Ada', 'Lovelace', '', 'ada@acme.fr'];
        yield 'no e-mail' => ['Ada', 'Lovelace', '06 12 34 56 78', ''];
        yield 'blanks only' => ['  ', 'Lovelace', '06 12 34 56 78', 'ada@acme.fr'];
    }

    public function testKeepsWhatTheBookerSaysTrimmed(): void
    {
        $booker = Booker::named(' Ada ', ' Lovelace ', '06 12 34 56 78', ' ADA@acme.fr ');

        $this->assertSame(['Ada', 'Lovelace', '+33612345678', 'ada@acme.fr'], [$booker->firstName, $booker->lastName, $booker->phone->value, $booker->email->value]);
    }
}
