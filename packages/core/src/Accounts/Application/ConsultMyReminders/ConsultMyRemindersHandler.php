<?php

declare(strict_types=1);

namespace Prospecter\Core\Accounts\Application\ConsultMyReminders;

use Prospecter\Core\Accounts\Application\Port\MyRemindersRetriever;
use Prospecter\Core\SharedKernel\Application\QueryHandler;
use Prospecter\Core\SharedKernel\Domain\MemberId;

final readonly class ConsultMyRemindersHandler implements QueryHandler
{
    public function __construct(private MyRemindersRetriever $myRemindersRetriever)
    {
    }

    public function __invoke(ConsultMyReminders $query, ConsultMyRemindersOutput $output): void
    {
        $output->present($this->myRemindersRetriever->of(new MemberId($query->memberId)));
    }
}
