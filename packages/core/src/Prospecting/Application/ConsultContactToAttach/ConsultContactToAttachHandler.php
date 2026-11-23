<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\ConsultContactToAttach;

use Prospecter\Core\Prospecting\Application\Port\ContactsToAttachRetriever;
use Prospecter\Core\Prospecting\Domain\Model\ContactId;
use Prospecter\Core\SharedKernel\Application\QueryHandler;
use Prospecter\Core\SharedKernel\Domain\MemberId;

final readonly class ConsultContactToAttachHandler implements QueryHandler
{
    public function __construct(private ContactsToAttachRetriever $contactsToAttachRetriever)
    {
    }

    public function __invoke(ConsultContactToAttach $query, ConsultContactToAttachOutput $output): void
    {
        $output->present($this->contactsToAttachRetriever->findHeldBy(new MemberId($query->prospectorId), new ContactId($query->personId)));
    }
}
