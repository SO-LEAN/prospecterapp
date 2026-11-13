<?php

namespace App\Me;

use App\Me\Resource\RemindersResource;
use App\Shared\Links;
use Prospecter\Core\Accounts\Application\ConsultMyReminders\ConsultMyReminders;
use Prospecter\Core\SharedKernel\Application\QueryHandler;

/**
 * My reminders as the API shows them; a controller calls it after a command, which returns nothing, to answer with
 * the fresh resource.
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
        ($this->consultMyReminders)(new ConsultMyReminders($me), $presenter);

        return $presenter->resource;
    }
}
