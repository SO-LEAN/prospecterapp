<?php

declare(strict_types=1);

namespace App\Identification\Mcp;

/**
 * What a LinkedIn sync brings into Prospecter: the contacts only, or the messages with their contacts.
 */
enum WhatLinkedInSynchronizes: string
{
    case Contacts = 'contacts';
    case Messages = 'messages';
}
