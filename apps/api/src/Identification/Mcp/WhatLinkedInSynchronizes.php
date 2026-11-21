<?php

namespace App\Identification\Mcp;

/**
 * What a synchronization of LinkedIn brings into Prospecter: the contacts only, or the messages with their contacts.
 */
enum WhatLinkedInSynchronizes: string
{
    case Contacts = 'contacts';
    case Messages = 'messages';
}
