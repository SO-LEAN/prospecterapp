<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Application\RecognizeCallingContact;

use Prospecter\Core\PersonalData\Application\PersonResponse;
use Prospecter\Core\PersonalData\Domain\Model\Person;
use Prospecter\Core\PersonalData\Domain\Model\Purpose;
use Prospecter\Core\PersonalData\Domain\Port\Readers;
use Prospecter\Core\PersonalData\Domain\Repository\PersonRepository;
use Prospecter\Core\SharedKernel\Application\QueryHandler;
use Prospecter\Core\SharedKernel\Domain\MemberId;
use Prospecter\Core\SharedKernel\Domain\PhoneNumber;

final readonly class RecognizeCallingContactHandler implements QueryHandler
{
    public function __construct(private PersonRepository $persons, private Readers $readers)
    {
    }

    public function __invoke(RecognizeCallingContact $query, RecognizeCallingContactOutput $output): void
    {
        $reader = $this->readers->readerFrom(new MemberId($query->readerId));
        $reader->ensureMayRecognizeByPhoneNumber();
        $contacts = $this->persons->withPhoneNumber($reader->accountId, Purpose::Prospecting, PhoneNumber::of($query->phoneNumber));

        $output->present(array_map(static fn (Person $contact): PersonResponse => PersonResponse::of($contact, $reader), $contacts));
    }
}
