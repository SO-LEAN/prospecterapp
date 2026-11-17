<?php

namespace App\Campaigns\Persistence;

/**
 * The keys of the items of the table of Campaigns, written by the repository and read by the retriever (ADR 0088):
 * one format for both. The taggings of an account share its partition, read at once to propose its tags; beside
 * them, the facts the tags of its contacts derive from (ADR 0141), the fact of a contact followed by its links.
 */
final class CampaignsKeys
{
    private const string ACCOUNT = 'ACCOUNT';
    private const string TAGGING = 'TAGGING';
    private const string ORGANIZATION = 'ORGANIZATION';
    private const string ORGANIZATION_FACT = 'FACT#ORGANIZATION';
    private const string CONTACT_FACT = 'FACT#CONTACT';
    /** sorts after `#` and every character of an identity */
    private const string AFTER_EVERY_LINK = '#~';

    public static function account(string $id): string
    {
        return self::ACCOUNT.'#'.$id;
    }

    public static function tagging(string $kind, string $id): string
    {
        return self::TAGGING.'#'.$kind.'#'.$id;
    }

    public static function organizationFact(string $id): string
    {
        return self::ORGANIZATION_FACT.'#'.$id;
    }

    /**
     * The fact of a contact, the first of its keys: its links follow it.
     */
    public static function contactFact(string $id): string
    {
        return self::CONTACT_FACT.'#'.$id;
    }

    /**
     * The fact of an organization names the contact: it inherits its tags.
     */
    public static function link(string $contact, string $organization): string
    {
        return self::contactFact($contact).'#'.self::ORGANIZATION.'#'.$organization;
    }

    /**
     * @return array{string, string} the first and the last sort keys of the fact of a contact and of its links
     */
    public static function factAndLinksOf(string $contact): array
    {
        return [self::contactFact($contact), self::contactFact($contact).self::AFTER_EVERY_LINK];
    }

    /**
     * @return array{string, string} the first and the last sort keys of the facts of the organizations
     */
    public static function organizationFacts(): array
    {
        return [self::ORGANIZATION_FACT.'#', self::ORGANIZATION_FACT.self::AFTER_EVERY_LINK];
    }

    /**
     * @return array{string, string} the first and the last sort keys of the facts of the contacts and of their links
     */
    public static function contactFacts(): array
    {
        return [self::CONTACT_FACT.'#', self::CONTACT_FACT.self::AFTER_EVERY_LINK];
    }

    /**
     * @return array{string, string} the first and the last sort keys of the taggings, the facts beside them left out
     */
    public static function taggings(): array
    {
        return [self::TAGGING.'#', self::TAGGING.self::AFTER_EVERY_LINK];
    }
}
