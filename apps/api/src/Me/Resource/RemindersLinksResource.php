<?php

namespace App\Me\Resource;

use App\Shared\Resource\LinkResource;

/**
 * A member may always change their own reminders.
 */
final readonly class RemindersLinksResource
{
    public function __construct(
        public LinkResource $self,
        public LinkResource $changeMyReminders,
    ) {
    }
}
