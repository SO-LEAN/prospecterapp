<?php

declare(strict_types=1);

use Behat\Config\Config;
use Behat\Config\Profile;
use Behat\Config\Suite;
use Tests\Core\Acceptance\Context\AccountContext;
use Tests\Core\Acceptance\Context\AllOrNothingContext;
use Tests\Core\Acceptance\Context\AttachmentContext;
use Tests\Core\Acceptance\Context\BookingContext;
use Tests\Core\Acceptance\Context\BookSlotContext;
use Tests\Core\Acceptance\Context\CalendarContext;
use Tests\Core\Acceptance\Context\CalendarPushContext;
use Tests\Core\Acceptance\Context\CalendarSyncContext;
use Tests\Core\Acceptance\Context\CallerContext;
use Tests\Core\Acceptance\Context\CampaignContext;
use Tests\Core\Acceptance\Context\ConnectorContext;
use Tests\Core\Acceptance\Context\ContactTagsContext;
use Tests\Core\Acceptance\Context\ContactToAttachContext;
use Tests\Core\Acceptance\Context\EstablishmentContext;
use Tests\Core\Acceptance\Context\ExchangeContext;
use Tests\Core\Acceptance\Context\ExchangeDetailContext;
use Tests\Core\Acceptance\Context\FingerprintContext;
use Tests\Core\Acceptance\Context\GeographicTagsContext;
use Tests\Core\Acceptance\Context\GoogleClientContext;
use Tests\Core\Acceptance\Context\IdentificationContext;
use Tests\Core\Acceptance\Context\LinkContext;
use Tests\Core\Acceptance\Context\MailboxMailContext;
use Tests\Core\Acceptance\Context\MailImportContext;
use Tests\Core\Acceptance\Context\MemberContext;
use Tests\Core\Acceptance\Context\MyDayContext;
use Tests\Core\Acceptance\Context\OrganizationContext;
use Tests\Core\Acceptance\Context\PersonalDataContext;
use Tests\Core\Acceptance\Context\PersonalDataFactsContext;
use Tests\Core\Acceptance\Context\PlannedExchangeContext;
use Tests\Core\Acceptance\Context\ProspectingFactsContext;
use Tests\Core\Acceptance\Context\PublishedLanguageContext;
use Tests\Core\Acceptance\Context\ReminderContext;
use Tests\Core\Acceptance\Context\ReportingContext;
use Tests\Core\Acceptance\Context\ScenarioContext;
use Tests\Core\Acceptance\Context\TagContext;
use Tests\Core\Acceptance\Context\ToDoContext;
use Tests\Core\Acceptance\Context\VideoCallLinkContext;
use Tests\Core\Acceptance\Context\WhatIsToProcessContext;

// Acceptance tests at the entrance of the hexagon: the use cases with in-memory ports (tests/Double)
return new Config()
    ->withProfile(new Profile('default')
        ->withSuite(new Suite('business_rules')
            ->withPaths('%paths.base%/features')
            ->withContexts(ScenarioContext::class, OrganizationContext::class, LinkContext::class, EstablishmentContext::class, AccountContext::class, MemberContext::class, PersonalDataContext::class, PublishedLanguageContext::class, CallerContext::class, ExchangeContext::class, ExchangeDetailContext::class, FingerprintContext::class, PlannedExchangeContext::class, ToDoContext::class, AttachmentContext::class, AllOrNothingContext::class, ProspectingFactsContext::class, PersonalDataFactsContext::class, ReportingContext::class, ReminderContext::class, CalendarContext::class, MyDayContext::class, WhatIsToProcessContext::class, ContactToAttachContext::class, ConnectorContext::class, GoogleClientContext::class, MailImportContext::class, MailboxMailContext::class, CalendarPushContext::class, CalendarSyncContext::class, VideoCallLinkContext::class, TagContext::class, ContactTagsContext::class, CampaignContext::class, GeographicTagsContext::class, BookingContext::class, BookSlotContext::class, IdentificationContext::class)));
