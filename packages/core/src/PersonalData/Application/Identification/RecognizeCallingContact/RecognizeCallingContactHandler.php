<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Application\Identification\RecognizeCallingContact;

use Prospecter\Core\PersonalData\Application\Port\PersonCardRetriever;
use Prospecter\Core\PersonalData\Domain\Port\Readers;
use Prospecter\Core\SharedKernel\Application\Handler\QueryHandler;
use Prospecter\Core\SharedKernel\Domain\Contact\PhoneNumber;
use Prospecter\Core\SharedKernel\Domain\Identity\MemberId;

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
