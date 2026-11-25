<?php

declare(strict_types=1);

namespace App\Me\Resource;

use App\Shared\Resource\LinkResource;
use App\Shared\Resource\Offers;

/**
 * A member may always change their own reminders.
 */
final readonly class RemindersLinksResource
{
    public function __construct(
        public LinkResource $self,
        #[Offers('api_me_change_reminders')]
        public LinkResource $changeMyReminders,
    ) {
    }
}
