<?php

declare(strict_types=1);

namespace App\Tags;

/** What is tagged, by the kind the core gives it. */
enum TagHolder: string
{
    case Organization = 'organization';
    case Contact = 'contact';
}
