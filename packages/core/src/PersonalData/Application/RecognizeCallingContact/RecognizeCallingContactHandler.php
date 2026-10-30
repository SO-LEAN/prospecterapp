<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Application\RecognizeCallingContact;

use Prospecter\Core\PersonalData\Application\Port\PersonCardRetriever;
use Prospecter\Core\PersonalData\Domain\Port\Readers;
use Prospecter\Core\SharedKernel\Application\QueryHandler;
use Prospecter\Core\SharedKernel\Domain\MemberId;
use Prospecter\Core\SharedKernel\Domain\PhoneNumber;

final readonly class RecognizeCallingContactHandler implements QueryHandler
{
    public function __construct(private PersonCardRetriever $personCardRetriever, private Readers $readers)
    {
    }

    public function __invoke(RecognizeCallingContact $query, RecognizeCallingContactOutput $output): void
    {
        $reader = $this->readers->readerFrom(new MemberId($query->readerId));
        $reader->ensureMayRecognizeByPhoneNumber();

        $output->present($this->personCardRetriever->contactsWithPhoneNumber(PhoneNumber::of($query->phoneNumber), $reader));
    }
}
