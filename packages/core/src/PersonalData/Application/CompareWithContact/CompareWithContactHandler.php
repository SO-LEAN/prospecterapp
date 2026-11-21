<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Application\CompareWithContact;

use Prospecter\Core\PersonalData\Domain\Model\ContactSupplement;
use Prospecter\Core\PersonalData\Domain\Port\Readers;
use Prospecter\Core\PersonalData\Domain\Repository\PersonRepository;
use Prospecter\Core\SharedKernel\Application\Comparison;
use Prospecter\Core\SharedKernel\Application\QueryHandler;
use Prospecter\Core\SharedKernel\Domain\MemberId;
use Prospecter\Core\SharedKernel\Domain\PersonId;

/**
 * Asks the contact itself, restored by its repository, the rule its completion follows: the answer and the write
 * never differ (ADR 0177).
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
