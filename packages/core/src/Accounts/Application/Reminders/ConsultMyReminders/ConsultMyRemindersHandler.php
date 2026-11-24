<?php

declare(strict_types=1);

namespace Prospecter\Core\Accounts\Application\Reminders\ConsultMyReminders;

use Prospecter\Core\Accounts\Application\Port\MyRemindersRetriever;
use Prospecter\Core\SharedKernel\Application\Handler\QueryHandler;
use Prospecter\Core\SharedKernel\Domain\Identity\MemberId;

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
