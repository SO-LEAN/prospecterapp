<?php

declare(strict_types=1);

namespace App\Prospecting\Persistence;

/**
 * The keys of the items of the organizations, written by `DynamoOrganizationRepository` and read by the
 * retrievers (ADR 0088): one format for both.
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
     * The links to the organizations naming a contact.
     */
    public static function contact(string $person): string
    {
        return 'CONTACT#'.$person;
    }

    /**
     * The links to the organizations of an account whose email has this domain (ADR 0146).
     */
    public static function ofEmailDomain(string $accountId, string $domain): string
    {
        return sprintf('EMAIL_DOMAIN#%s#%s', $accountId, $domain);
    }

    /**
     * The links to the organizations of an account resembling each other, by their name and their city.
     */
    public static function ofResemblance(string $accountId, string $key): string
    {
        return sprintf('RESEMBLANCE#%s#%s', $accountId, $key);
    }

    /**
     * What an organization holds alone in its account, its SIRET, a link, the domain of its website or its email:
     * the item of its kind (`EMAIL`, `SIRET`, `REFERENCE`, `WEBSITE`) names its holder, `ownerId`.
     */
    public static function reservation(string $kind, string $accountId, string $value): string
    {
        return sprintf('%s#%s#%s', $kind, $accountId, $value);
    }
}
