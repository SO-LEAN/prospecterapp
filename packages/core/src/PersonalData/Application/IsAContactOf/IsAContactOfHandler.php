<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Application\IsAContactOf;

use Prospecter\Core\PersonalData\Contract\Service\IsAContactOf\IsAContactOf;
use Prospecter\Core\PersonalData\Contract\Service\IsAContactOf\IsAContactOfOutput;
use Prospecter\Core\PersonalData\Domain\PersonRepository;
use Prospecter\Core\SharedKernel\Application\QueryHandler;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\PersonId;

final readonly class IsAContactOfHandler implements QueryHandler
{
    public function __construct(private PersonRepository $persons)
    {
    }

    public function __invoke(IsAContactOf $query, IsAContactOfOutput $output): void
    {
        $output->present($this->persons->get(new PersonId($query->personId))->isAContactOf(new AccountId($query->accountId)));
    }
}
