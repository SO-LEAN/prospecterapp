<?php

declare(strict_types=1);

final class MeetingDocument
{
    public static function of(string $account, string $member, string $moment, string $contact, string $topic): self
    {
        return new self();
    }
}
