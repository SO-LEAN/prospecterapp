<?php

declare(strict_types=1);

namespace App\Tags;

/** What is tagged, by the kind the core gives it, and the routes of its tags. */
enum TagRoutes: string
{
    case Organization = 'organization';
    case Contact = 'contact';

    public function show(): string
    {
        return $this->prefix().'_tags';
    }

    public function tag(): string
    {
        return $this->prefix().'_tag';
    }

    public function takeOff(): string
    {
        return $this->prefix().'_take_off_tag';
    }

    private function prefix(): string
    {
        return match ($this) {
            self::Organization => 'api_organizations',
            self::Contact => 'api_contacts',
        };
    }
}
