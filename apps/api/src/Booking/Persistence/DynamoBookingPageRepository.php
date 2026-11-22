<?php

declare(strict_types=1);

namespace App\Booking\Persistence;

use App\Shared\Persistence\Table;
use App\Shared\Typed;
use Prospecter\Core\Booking\Domain\Exception\UnknownBookingPage;
use Prospecter\Core\Booking\Domain\Model\BookingCode;
use Prospecter\Core\Booking\Domain\Model\BookingPage;
use Prospecter\Core\Booking\Domain\Repository\BookingPageRepository;
use Prospecter\Core\SharedKernel\Domain\MemberId;

/**
 * The booking page of each host in the table of Booking, and the link of its code to it (ADR 0151): a new code put
 * only where none is, its former link deleted in the same transaction.
 */
final readonly class DynamoBookingPageRepository implements BookingPageRepository
{
    private const string NO_SUCH_CODE_YET = 'attribute_not_exists(PK)';

    public function __construct(private Table $bookingTable)
    {
    }

    public function get(MemberId $host): BookingPage
    {
        return $this->find($host) ?? throw UnknownBookingPage::of($host);
    }

    public function find(MemberId $host): ?BookingPage
    {
        $document = $this->bookingTable->item(BookingKeys::page($host->value), BookingKeys::PAGE)['page'] ?? null;

        return null === $document ? null : BookingPageDocument::page($document);
    }

    public function findByCode(BookingCode $code): ?BookingPage
    {
        $host = Typed::stringOrNull($this->bookingTable->item(BookingKeys::code($code->value), BookingKeys::CODE)['host'] ?? null);

        return null === $host ? null : $this->find(new MemberId($host));
    }

    public function save(BookingPage $page): void
    {
        $document = BookingPageDocument::of($page);
        $host = $page->host()->value;
        $code = Typed::stringOrNull($document['code']);
        $storedCode = $this->storedCodeOf($host);
        $writes = [$this->bookingTable->putting(['PK' => BookingKeys::page($host), 'SK' => BookingKeys::PAGE, 'page' => $document])];

        if ($code !== $storedCode && null !== $code) {
            $writes[] = $this->bookingTable->putting(['PK' => BookingKeys::code($code), 'SK' => BookingKeys::CODE, 'host' => $host], self::NO_SUCH_CODE_YET);
        }

        if ($code !== $storedCode && null !== $storedCode) {
            $writes[] = $this->bookingTable->deleting(BookingKeys::code($storedCode), BookingKeys::CODE);
        }

        $this->bookingTable->transact($writes);
    }

    private function storedCodeOf(string $host): ?string
    {
        $stored = $this->bookingTable->item(BookingKeys::page($host), BookingKeys::PAGE)['page'] ?? null;

        return null === $stored ? null : Typed::stringOrNull(Typed::document($stored)['code'] ?? null);
    }
}
