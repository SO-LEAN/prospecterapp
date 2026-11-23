<?php

declare(strict_types=1);

namespace Fixtures\CacheOfServices;

final readonly class Reminder
{
    public function mailerOver(mixed $transport): Mailer
    {
        return new Mailer($transport);
    }
}
