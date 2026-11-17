<?php

namespace App\Campaigns\Persistence;

/**
 * The keys of the items of the table of Campaigns, written by the repository and read by the retriever (ADR 0088):
 * one format for both. The taggings of an account share its partition, read at once to propose its tags.
 */
final class CampaignsKeys
{
    private const string ACCOUNT = 'ACCOUNT';
    private const string TAGGING = 'TAGGING';

    public static function account(string $id): string
    {
        return self::ACCOUNT.'#'.$id;
    }

    public static function tagging(string $kind, string $id): string
    {
        return self::TAGGING.'#'.$kind.'#'.$id;
    }
}
