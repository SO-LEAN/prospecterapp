<?php

declare(strict_types=1);

namespace Prospecter\Core\SharedKernel\Domain;

/**
 * What an external reference points to: a natural person or an organization.
 */
enum Designated: string
{
    case Person = 'person';
    case Organization = 'organization';
}
