<?php

namespace App\Prospecting\Persistence;

/**
 * The keys of the items of the organizations, written by `DynamoOrganizationRepository` and read by the
 * retrievers (ADR 0088): one format for both.
 */
final class OrganizationKeys
{
    public const string ORGANIZATION = 'ORGANIZATION';

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
}
