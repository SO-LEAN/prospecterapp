<?php

declare(strict_types=1);

use Behat\Config\Config;
use Behat\Config\Profile;
use Behat\Config\Suite;
use Tests\Core\Acceptance\Context\AccountContext;
use Tests\Core\Acceptance\Context\AllOrNothingContext;
use Tests\Core\Acceptance\Context\AttachmentContext;
use Tests\Core\Acceptance\Context\CallerContext;
use Tests\Core\Acceptance\Context\ExchangeContext;
use Tests\Core\Acceptance\Context\ExchangeDetailContext;
use Tests\Core\Acceptance\Context\MemberContext;
use Tests\Core\Acceptance\Context\OrganizationContext;
use Tests\Core\Acceptance\Context\PersonalDataContext;
use Tests\Core\Acceptance\Context\PersonalDataFactsContext;
use Tests\Core\Acceptance\Context\PlannedExchangeContext;
use Tests\Core\Acceptance\Context\ProspectingFactsContext;
use Tests\Core\Acceptance\Context\PublishedLanguageContext;
use Tests\Core\Acceptance\Context\ReminderContext;
use Tests\Core\Acceptance\Context\ReportingContext;
use Tests\Core\Acceptance\Context\ScenarioContext;
use Tests\Core\Acceptance\Context\ToDoContext;

// Acceptance tests at the entrance of the hexagon: the use cases with in-memory ports (tests/Double)
return new Config()
    ->withProfile(new Profile('default')
        ->withSuite(new Suite('business_rules')
            ->withPaths('%paths.base%/features')
            ->withContexts(ScenarioContext::class, OrganizationContext::class, AccountContext::class, MemberContext::class, PersonalDataContext::class, PublishedLanguageContext::class, CallerContext::class, ExchangeContext::class, ExchangeDetailContext::class, PlannedExchangeContext::class, ToDoContext::class, AttachmentContext::class, AllOrNothingContext::class, ProspectingFactsContext::class, PersonalDataFactsContext::class, ReportingContext::class, ReminderContext::class)));
