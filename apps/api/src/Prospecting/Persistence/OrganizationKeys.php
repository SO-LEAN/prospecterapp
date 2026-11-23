<?php

declare(strict_types=1);

namespace App\Prospecting\Persistence;

/**
 * The keys of the organizations' items. `DynamoOrganizationRepository` writes them and the retrievers read them (ADR
 * 0088), so both share one format.
 */
final class OrganizationKeys
{
    public const string ORGANIZATION = 'ORGANIZATION';
    public const string EMAIL = 'EMAIL';
    public const string SIRET = 'SIRET';
    public const string REFERENCE = 'REFERENCE';
    public const string WEBSITE = 'WEBSITE';

    public static function organization(string $id): string
    {
        return self::ORGANIZATION.'#'.$id;
    }

    /**
     * The organizations an account prospects, in `GSI1`.
     */
    public static function ofAccount(string $accountId): string
    {
        return 'ACCOUNT#'.$accountId;
    }

    /**
     * The links to the organizations that name a contact.
     */
    public static function contact(string $person): string
    {
        return 'CONTACT#'.$person;
    }

    /**
     * The links to an account's organizations whose email has this domain (ADR 0146).
     */
    public static function ofEmailDomain(string $accountId, string $domain): string
    {
        return sprintf('EMAIL_DOMAIN#%s#%s', $accountId, $domain);
    }

    /**
     * The links to an account's organizations that resemble each other by their name and their city.
     */
    public static function ofResemblance(string $accountId, string $key): string
    {
        return sprintf('RESEMBLANCE#%s#%s', $accountId, $key);
    }

    /**
     * A value that only one organization holds in its account: its SIRET, a link, its website's domain or its email.
     * The item of the value's kind (`EMAIL`, `SIRET`, `REFERENCE`, `WEBSITE`) names its holder in `ownerId`.
     */
    public static function reservation(string $kind, string $accountId, string $value): string
    {
        return sprintf('%s#%s#%s', $kind, $accountId, $value);
    }
}
