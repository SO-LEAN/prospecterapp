<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Application\RecognizeCallingContact;

use Prospecter\Core\PersonalData\Application\PersonResponse;
use Prospecter\Core\PersonalData\Domain\Person;
use Prospecter\Core\PersonalData\Domain\PersonRepository;
use Prospecter\Core\PersonalData\Domain\Purpose;
use Prospecter\Core\PersonalData\Domain\ReaderService;
use Prospecter\Core\SharedKernel\Application\QueryHandler;
use Prospecter\Core\SharedKernel\Domain\MemberId;
use Prospecter\Core\SharedKernel\Domain\PhoneNumber;

final readonly class RecognizeCallingContactHandler implements QueryHandler
{
    public function __construct(private PersonRepository $persons, private ReaderService $readerService)
    {
    }

    public function __invoke(RecognizeCallingContact $query, RecognizeCallingContactOutput $output): void
    {
        $reader = $this->readerService->readerFrom(new MemberId($query->readerId));
        $reader->ensureMayRecognizeByPhoneNumber();
        $contacts = $this->persons->withPhoneNumber($reader->accountId, Purpose::Prospecting, PhoneNumber::of($query->phoneNumber));

        $output->present(array_map(static fn (Person $contact): PersonResponse => PersonResponse::of($contact, $reader), $contacts));
    }
}
