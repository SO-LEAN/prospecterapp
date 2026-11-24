<?php

declare(strict_types=1);

namespace App\Me;

use App\Me\Resource\RemindersLinksResource;
use App\Me\Resource\RemindersResource;
use App\Shared\Links;
use Prospecter\Core\Accounts\Application\Reminders\ConsultMyReminders\ConsultMyRemindersOutput;
use Prospecter\Core\Accounts\Application\Reminders\MyReminders;

/**
 * Presents my reminders as the API shows them. Each call builds its own presenter (ADR 0085).
 */
final class RemindersResourcePresenter implements ConsultMyRemindersOutput
{
    public private(set) RemindersResource $resource;

    public function __construct(private readonly Links $links)
    {
    }

    public function present(MyReminders $reminders): void
    {
        $this->resource = new RemindersResource(
            $reminders->enabled,
            $reminders->minutesBefore,
            new RemindersLinksResource($this->links->to('api_me_reminders'), $this->links->to('api_me_change_reminders')),
        );
    }
}
