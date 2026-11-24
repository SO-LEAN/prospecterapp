<?php

declare(strict_types=1);

namespace Tests\Core\Application;

use PHPUnit\Framework\TestCase;
use Prospecter\Core\PersonalData\Application\Contact\NameContacts\NameContactsHandler;
use Prospecter\Core\PersonalData\Application\PersonCard;
use Prospecter\Core\PersonalData\Application\Port\PersonCardRetriever;
use Prospecter\Core\PersonalData\Contract\Service\NameContacts\ContactNameResponse;
use Prospecter\Core\PersonalData\Contract\Service\NameContacts\KeptContactNames;
use Prospecter\Core\PersonalData\Contract\Service\NameContacts\NameContacts;
use Prospecter\Core\PersonalData\Domain\Model\PersonId;
use Prospecter\Core\PersonalData\Domain\Model\Reader;
use Prospecter\Core\PersonalData\Domain\Port\Readers;
use Prospecter\Core\SharedKernel\Domain\Contact\PhoneNumber;
use Prospecter\Core\SharedKernel\Domain\Identity\AccountId;
use Prospecter\Core\SharedKernel\Domain\Identity\MemberId;

/**
 * A person card shows a contact's phone number as it was typed, when it was kept before numbers were checked. No
 * scenario can write such a number, because the value objects refuse it.
 */
final class NameContactsHandlerTest extends TestCase
{
    private const string MEMBER = '0190a1c0-0000-7000-8000-0000000000aa';
    private const string ACCOUNT = '0190a1c0-0000-7000-8000-0000000000ac';
    private const string PERSON = '0190a1c0-0000-7000-8000-0000000000ad';
    private const string READER = '0190a1c0-0000-7000-8000-0000000000ae';

    public function testAPhoneKeptBeforeTheNumbersWereCheckedIsNone(): void
    {
        $this->assertSame([null], array_map(static fn (ContactNameResponse $named): ?string => $named->phoneNumber, $this->namesOfAContactWithThePhone('12 34')));
    }

    /**
     * @return list<ContactNameResponse>
     */
    private function namesOfAContactWithThePhone(string $phoneNumber): array
    {
        $kept = new KeptContactNames();
        new NameContactsHandler(self::cardsWithThePhone($phoneNumber), self::aReaderOfTheClear())(new NameContacts(self::MEMBER, [self::PERSON]), $kept);

        return $kept->names;
    }

    private static function cardsWithThePhone(string $phoneNumber): PersonCardRetriever
    {
        return new readonly class($phoneNumber) implements PersonCardRetriever {
            public function __construct(private string $phoneNumber)
            {
            }

            public function get(PersonId $id, Reader $reader): PersonCard
            {
                return new PersonCard($id->value, false, 'Ada', 'Lovelace', 'ms', null, 'ada@acme.fr', $this->phoneNumber, null, [], [], null, [], new \DateTimeImmutable('2026-11-21 10:00 UTC'), new \DateTimeImmutable('2026-11-21 10:00 UTC'));
            }

            /**
             * @param list<PersonId> $ids
             */
            public function ofIds(array $ids, Reader $reader): array
            {
                return array_map(fn (PersonId $id): PersonCard => $this->get($id, $reader), $ids);
            }

            public function contactsWithPhoneNumber(PhoneNumber $phoneNumber, Reader $reader): array
            {
                return [];
            }
        };
    }

    private static function aReaderOfTheClear(): Readers
    {
        return new readonly class(new AccountId(self::ACCOUNT), new PersonId(self::READER)) implements Readers {
            public function __construct(private AccountId $accountId, private PersonId $person)
            {
            }

            public function readerFrom(MemberId $id): Reader
            {
                return new Reader($id, $this->accountId, $this->person, true);
            }
        };
    }
}
