<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Application\Contact\CompareWithContact;

use Prospecter\Core\PersonalData\Domain\Model\ContactSupplement;
use Prospecter\Core\PersonalData\Domain\Model\PersonId;
use Prospecter\Core\PersonalData\Domain\Port\Readers;
use Prospecter\Core\PersonalData\Domain\Repository\PersonRepository;
use Prospecter\Core\SharedKernel\Application\Handler\QueryHandler;
use Prospecter\Core\SharedKernel\Application\Response\Comparison;
use Prospecter\Core\SharedKernel\Domain\Identity\MemberId;

/**
 * Restores the contact from its repository and asks the contact itself to apply the rule its completion follows. So
 * the answer and the completion's write never differ (ADR 0177).
 */
final readonly class CompareWithContactHandler implements QueryHandler
{
    public function __construct(private PersonRepository $persons, private Readers $readers)
    {
    }

    public function __invoke(CompareWithContact $query, CompareWithContactOutput $output): void
    {
        $completion = $this->persons->get(new PersonId($query->id))->completionWith(ContactSupplement::given($query->civility, $query->jobTitle, $query->email, $query->phoneNumber, $query->links), by: $this->readers->readerFrom(new MemberId($query->readerId)));
        $output->present(new Comparison($completion->completed, $completion->differing));
    }
}
