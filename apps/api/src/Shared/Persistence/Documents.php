<?php

namespace App\Shared\Persistence;

use Prospecter\Core\SharedKernel\Domain\Address;
use Prospecter\Core\SharedKernel\Domain\ContactDetails;
use Prospecter\Core\SharedKernel\Domain\Designated;
use Prospecter\Core\SharedKernel\Domain\Email;
use Prospecter\Core\SharedKernel\Domain\Exception\BusinessRuleViolation;
use Prospecter\Core\SharedKernel\Domain\ExternalReference;
use Prospecter\Core\SharedKernel\Domain\ExternalReferences;
use Prospecter\Core\SharedKernel\Domain\File;
use Prospecter\Core\SharedKernel\Domain\GeoPoint;
use Prospecter\Core\SharedKernel\Domain\LegalIdentity;
use Prospecter\Core\SharedKernel\Domain\Origin;
use Prospecter\Core\SharedKernel\Domain\PhoneNumber;
use Prospecter\Core\SharedKernel\Domain\Siret;
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

    public static function momentOrNone(?\DateTimeImmutable $moment): ?string
    {
        return null === $moment ? null : self::moment($moment);
    }

    public static function momentOfOrNone(?string $moment): ?\DateTimeImmutable
    {
        return null === $moment ? null : self::momentOf($moment);
    }

    /**
     * How an aggregate was created; written before it was kept, by hand.
     *
     * @param array<string, mixed> $document
     */
    public static function originOf(array $document): Origin
    {
        return Origin::tryFrom($document['origin'] ?? '') ?? Origin::Hand;
    }

    /**
     * @return array{corporateName: ?string, form: ?string, type: ?string}
     */
    public static function legalIdentity(LegalIdentity $legalIdentity): array
    {
        return ['corporateName' => $legalIdentity->corporateName, 'form' => $legalIdentity->form, 'type' => $legalIdentity->type, 'siret' => $legalIdentity->siret?->value];
    }

    /**
     * @param array<string, mixed> $document
     */
    public static function legalIdentityOf(array $document): LegalIdentity
    {
        return new LegalIdentity($document['corporateName'] ?? null, $document['form'] ?? null, $document['type'] ?? null, Siret::orNone($document['siret'] ?? null));
    }

    /**
     * @return array{email: ?string, phoneNumber: ?string, language: ?string}
     */
    public static function contactDetails(ContactDetails $contactDetails): array
    {
        return ['email' => $contactDetails->email?->value, 'phoneNumber' => $contactDetails->phoneNumber?->value, 'language' => $contactDetails->language];
    }

    /**
     * @param array<string, mixed> $document
     */
    public static function contactDetailsOf(array $document): ContactDetails
    {
        return new ContactDetails(Email::orNone($document['email'] ?? null), self::phoneNumberOf($document['phoneNumber'] ?? null), $document['language'] ?? null);
    }

    /**
     * A number kept before the numbers were checked (ADR 0072) is restored as it was typed when it is no valid
     * number: reading an aggregate never fails, saving it again keeps its number in E.164 when it is one.
     */
    private static function phoneNumberOf(?string $stored): ?PhoneNumber
    {
        try {
            return PhoneNumber::orNone($stored);
        } catch (BusinessRuleViolation) {
            return Hydrator::restore(PhoneNumber::class, ['value' => $stored]);
        }
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
     * The links of a person or an organization: what recognizes each, its source and its identifier, beside the address
     * that opens it.
     *
     * @return list<array{source: string, identifier: string, address: string}>
     */
    public static function references(ExternalReferences $references): array
    {
        return array_map(static fn (ExternalReference $reference): array => ['source' => $reference->source, 'identifier' => $reference->identifier, 'address' => $reference->address], $references->all());
    }

    /**
     * The links as they were recognized when they were noted.
     *
     * @param list<array{source: string, identifier: string, address: string}> $documents
     */
    public static function referencesOf(array $documents): ExternalReferences
    {
        return ExternalReferences::of(array_map(static fn (array $reference): ExternalReference => ExternalReference::restored($reference['source'], $reference['identifier'], $reference['address']), $documents));
    }

    /**
     * The links of a document written before the links were, recognized by their addresses (a LinkedIn profile, a
     * LinkedIn page).
     *
     * @param list<string> $addresses
     */
    public static function referencesBeforeTheLinks(array $addresses, Designated $designated): ExternalReferences
    {
        return ExternalReferences::designating($addresses, $designated);
    }

    /**
     * The links recognized again by their addresses, a source known since taking over the links of its host, each
     * today's rules refuse kept as it was noted.
     *
     * @param list<array{source: string, identifier: string, address: string}> $documents
     *
     * @return list<array{source: string, identifier: string, address: string}>
     */
    public static function referencesRecognizedAgain(array $documents, Designated $designated): array
    {
        return self::references(ExternalReferences::of(array_map(static fn (ExternalReference $reference): ExternalReference => $reference->recognizedAgain($designated), self::referencesOf($documents)->all())));
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
