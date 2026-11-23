<?php

declare(strict_types=1);

namespace App\Me;

use App\Me\Resource\RemindersResource;
use App\Shared\Links;
use Prospecter\Core\Accounts\Application\ConsultMyReminders\ConsultMyReminders;
use Prospecter\Core\SharedKernel\Application\QueryHandler;

/**
 * Reads my reminders as the API shows them. A command returns nothing, so a controller calls this reader after a
 * command to answer with the fresh resource.
 */
final readonly class MyRemindersReader
{
    public function __construct(
        private QueryHandler $consultMyReminders,
        private Links $links,
    ) {
    }

    public function read(string $me): RemindersResource
    {
        $presenter = new RemindersResourcePresenter($this->links);
        $this->consultMyReminders->__invoke(new ConsultMyReminders($me), $presenter);

        return $presenter->resource;
    }
}
