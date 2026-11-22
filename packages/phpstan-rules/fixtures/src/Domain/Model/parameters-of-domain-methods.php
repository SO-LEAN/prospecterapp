<?php

declare(strict_types=1);

final class Meeting
{
    public static function planned(string $account, string $member, string $moment, string $contact, string $topic): self
    {
        return new self($account, $member, $moment, $contact, $topic, 0);
    }

    public function __construct(string $account, string $member, string $moment, string $contact, string $topic, int $length)
    {
    }

    public static function held(string $gesture, string $contact, string $topic, int $length): self
    {
        return new self($gesture, '', '', $contact, $topic, $length);
    }

    private static function moved(string $account, string $member, string $moment, string $contact, string $topic): self
    {
        return self::planned($account, $member, $moment, $contact, $topic);
    }
}
