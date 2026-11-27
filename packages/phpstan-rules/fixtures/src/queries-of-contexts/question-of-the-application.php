<?php

declare(strict_types=1);

namespace App\Fixtures;

use Prospecter\Core\Prospecting\Contract\Service\NameOrganizationsOfContacts\NameOrganizationsOfContacts;
use Tests\PHPStan\QueriesOfContexts\SendInvitation;

final readonly class AsksOneContextAndItself
{
    public function organizations(): NameOrganizationsOfContacts
    {
        return new NameOrganizationsOfContacts('account', ['person']);
    }

    public function invitation(): SendInvitation
    {
        return new SendInvitation();
    }
}
