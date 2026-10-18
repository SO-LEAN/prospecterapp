<?php

namespace App\Shared\Persistence;

use Prospecter\Core\SharedKernel\Domain\Address;
use Prospecter\Core\SharedKernel\Domain\ContactDetails;
use Prospecter\Core\SharedKernel\Domain\Email;
use Prospecter\Core\SharedKernel\Domain\File;
use Prospecter\Core\SharedKernel\Domain\GeoPoint;
use Prospecter\Core\SharedKernel\Domain\LegalIdentity;
use Prospecter\Core\SharedKernel\Domain\Slice;

/**
 * The value objects of the shared kernel as plain documents of a DynamoDB item, and back (ADR 0048).
 */
final class Documents
{
    /** A moment is kept in UTC, to the microsecond: sorting the strings sorts the moments. */
    private const string MOMENT = 'Y-m-d\TH:i:s.u\Z';

    public static function moment(\DateTimeImmutable $moment): string
    {
        return $moment->setTimezone(new \DateTimeZone('UTC'))->format(self::MOMENT);
    }

    public static function momentOf(string $moment): \DateTimeImmutable
    {
        return \DateTimeImmutable::createFromFormat(self::MOMENT, $moment, new \DateTimeZone('UTC')) ?: throw new \UnexpectedValueException(sprintf('"%s" is no stored moment', $moment));
    }

    /**
     * @return array{corporateName: ?string, form: ?string, type: ?string}
     */
    public static function legalIdentity(LegalIdentity $legalIdentity): array
    {
        return ['corporateName' => $legalIdentity->corporateName, 'form' => $legalIdentity->form, 'type' => $legalIdentity->type];
    }

    /**
     * @param array<string, mixed> $document
     */
    public static function legalIdentityOf(array $document): LegalIdentity
    {
        return new LegalIdentity($document['corporateName'] ?? null, $document['form'] ?? null, $document['type'] ?? null);
    }

    /**
     * @return array{email: ?string, phoneNumber: ?string, language: ?string}
     */
    public static function contactDetails(ContactDetails $contactDetails): array
    {
        return ['email' => $contactDetails->email?->value, 'phoneNumber' => $contactDetails->phoneNumber, 'language' => $contactDetails->language];
    }

    /**
     * @param array<string, mixed> $document
     */
    public static function contactDetailsOf(array $document): ContactDetails
    {
        return new ContactDetails(Email::orNone($document['email'] ?? null), $document['phoneNumber'] ?? null, $document['language'] ?? null);
    }

    /**
     * @return array{street: ?string, postalCode: ?string, city: ?string, country: ?string}|null
     */
    public static function address(?Address $address): ?array
    {
        return null === $address ? null : ['street' => $address->street, 'postalCode' => $address->postalCode, 'city' => $address->city, 'country' => $address->country];
    }

    /**
     * @param array<string, mixed>|null $document
     */
    public static function addressOf(?array $document): ?Address
    {
        return null === $document ? null : new Address($document['street'], $document['postalCode'], $document['city'], $document['country']);
    }

    /**
     * @return array{longitude: float, latitude: float}|null
     */
    public static function geoPoint(?GeoPoint $geoPoint): ?array
    {
        return null === $geoPoint ? null : ['longitude' => $geoPoint->longitude, 'latitude' => $geoPoint->latitude];
    }

    /**
     * @param array<string, mixed>|null $document
     */
    public static function geoPointOf(?array $document): ?GeoPoint
    {
        return null === $document ? null : new GeoPoint((float) $document['longitude'], (float) $document['latitude']);
    }

    /**
     * @return array{url: string, extension: string, size: int}|null
     */
    public static function file(?File $file): ?array
    {
        return null === $file ? null : ['url' => $file->url, 'extension' => $file->extension, 'size' => $file->size];
    }

    /**
     * @param array<string, mixed>|null $document
     */
    public static function fileOf(?array $document): ?File
    {
        return null === $document ? null : new File((string) $document['url'], (string) $document['extension'], (int) $document['size']);
    }

    /**
     * The first `max` of what was found, newest first, and the cursor of what follows (ADR 0049).
     *
     * @template T
     *
     * @param list<T>             $found newest first, one more than a slice tells whether another follows
     * @param \Closure(T): string $idOf
     *
     * @return Slice<T>
     */
    public static function slice(array $found, int $max, \Closure $idOf): Slice
    {
        $slice = array_slice($found, 0, $max);

        return new Slice($slice, count($found) > $max && [] !== $slice ? $idOf(array_last($slice)) : null);
    }
}
