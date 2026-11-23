<?php

declare(strict_types=1);

namespace App\Campaigns\Persistence;

/**
 * The keys of the items in Campaigns' table. The repository writes them and the retriever reads them, so both share one
 * format (ADR 0088). An account's taggings share its partition, which is read at once to propose its tags. Beside them
 * are the facts that its contacts' tags are derived from (ADR 0141): each contact's fact is followed by its links. The
 * partition also holds its campaigns, read at once (ADR 0144).
 */
final class CampaignsKeys
{
    private const string ACCOUNT = 'ACCOUNT';
    private const string TAGGING = 'TAGGING';
    private const string CAMPAIGN = 'CAMPAIGN';
    private const string ORGANIZATION = 'ORGANIZATION';
    private const string ORGANIZATION_FACT = 'FACT#ORGANIZATION';
    private const string CONTACT_FACT = 'FACT#CONTACT';
    /** sorts after `#` and after every character of an id */
    private const string AFTER_EVERY_LINK = '#~';

    public static function account(string $id): string
    {
        return self::ACCOUNT.'#'.$id;
    }

    public static function tagging(string $kind, string $id): string
    {
        return self::TAGGING.'#'.$kind.'#'.$id;
    }

    public static function campaign(string $id): string
    {
        return self::CAMPAIGN.'#'.$id;
    }

    /**
     * @return array{string, string} the campaigns' first and last sort keys
     */
    public static function campaigns(): array
    {
        return [self::CAMPAIGN.'#', self::CAMPAIGN.self::AFTER_EVERY_LINK];
    }

    public static function organizationFact(string $id): string
    {
        return self::ORGANIZATION_FACT.'#'.$id;
    }

    /** The organization whose fact has this key. */
    public static function organizationOfFact(string $key): string
    {
        return substr($key, strlen(self::organizationFact('')));
    }

    /** A contact's fact, the first of its keys. Its links follow it. */
    public static function contactFact(string $id): string
    {
        return self::CONTACT_FACT.'#'.$id;
    }

    /** An organization's fact names the contact, so the contact inherits the organization's tags. */
    public static function link(string $contact, string $organization): string
    {
        return self::contactFact($contact).'#'.self::ORGANIZATION.'#'.$organization;
    }

    /**
     * @return array{string, string} the first and last sort keys of a contact's fact and its links
     */
    public static function factAndLinksOf(string $contact): array
    {
        return [self::contactFact($contact), self::contactFact($contact).self::AFTER_EVERY_LINK];
    }

    /**
     * @return array{string, string} the first and last sort keys of the organizations' facts
     */
    public static function organizationFacts(): array
    {
        return [self::ORGANIZATION_FACT.'#', self::ORGANIZATION_FACT.self::AFTER_EVERY_LINK];
    }

    /**
     * @return array{string, string} the first and last sort keys of the contacts' facts and their links
     */
    public static function contactFacts(): array
    {
        return [self::CONTACT_FACT.'#', self::CONTACT_FACT.self::AFTER_EVERY_LINK];
    }

    /**
     * @return array{string, string} the first and last sort keys of the taggings, without the facts beside them
     */
    public static function taggings(): array
    {
        return [self::TAGGING.'#', self::TAGGING.self::AFTER_EVERY_LINK];
    }
}
