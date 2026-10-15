<?php

declare(strict_types=1);

namespace Tests\Core\Acceptance;

use Prospecter\Core\Domain\Organization\Address;
use Prospecter\Core\Domain\Organization\GeoPoint;
use Prospecter\Core\Domain\Organization\LegalIdentity;
use Prospecter\Core\Domain\Organization\Organization;
use Prospecter\Core\Domain\Organization\OrganizationId;
use Prospecter\Core\Domain\Shared\ContactDetails;
use Prospecter\Core\Domain\Shared\Email;
use Prospecter\Core\Domain\Shared\File;
use Prospecter\Core\Domain\User\PersonName;
use Prospecter\Core\Domain\User\User;

/**
 * The words of the scenarios: French labels of the Gherkin tables, how they become value objects and how
 * they describe the stored aggregates, so that every feature speaks the same language.
 */
final class Vocabulary
{
    private const array LABELS = [
        'raison sociale', 'forme juridique', 'type', 'e-mail', 'téléphone', 'langue',
        'rue', 'code postal', 'ville', 'pays', 'observations', 'holding', 'prénom', 'nom',
    ];

    /**
     * @param array<string, string> $table
     *
     * @return array<string, string|null> every known label, null when left out or empty
     */
    public static function values(array $table): array
    {
        $unknown = array_diff(array_keys($table), self::LABELS);
        Expect::that([] === $unknown, 'Unknown labels: '.implode(', ', $unknown));

        return array_merge(
            array_fill_keys(self::LABELS, null),
            array_map(static fn (string $value): ?string => '' === $value ? null : $value, $table),
        );
    }

    /**
     * @param array<string, string|null> $values
     */
    public static function legalIdentity(array $values): LegalIdentity
    {
        return new LegalIdentity($values['raison sociale'], $values['forme juridique'], $values['type']);
    }

    /**
     * @param array<string, string|null> $values
     */
    public static function contactDetails(array $values): ContactDetails
    {
        return new ContactDetails(Email::orNone($values['e-mail']), $values['téléphone'], $values['langue']);
    }

    /**
     * @param array<string, string|null> $values
     */
    public static function address(array $values): ?Address
    {
        $address = new Address($values['rue'], $values['code postal'], $values['ville'], $values['pays']);

        return $address->isEmpty() ? null : $address;
    }

    /**
     * @param array<string, string|null> $values
     */
    public static function personName(array $values): PersonName
    {
        return new PersonName($values['prénom'], $values['nom']);
    }

    /**
     * What a scenario can say about a stored organization, by label.
     *
     * @param \Closure(OrganizationId): string $nameOf
     *
     * @return array<string, string>
     */
    public static function describeOrganization(Organization $organization, \Closure $nameOf): array
    {
        $identity = $organization->legalIdentity();
        $contact = $organization->contactDetails();
        $address = $organization->address();

        return [
            'raison sociale' => (string) $identity->corporateName,
            'forme juridique' => (string) $identity->form,
            'type' => (string) $identity->type,
            'e-mail' => (string) $contact->email,
            'téléphone' => (string) $contact->phoneNumber,
            'langue' => (string) $contact->language,
            'rue' => (string) $address?->street,
            'code postal' => (string) $address?->postalCode,
            'ville' => (string) $address?->city,
            'pays' => (string) $address?->country,
            'observations' => (string) $organization->observations(),
            'position' => self::describeGeoPoint($organization->geoPoint()),
            'logo' => self::describeFile($organization->logo()),
            'holding' => null === $organization->holdingId() ? '' : $nameOf($organization->holdingId()),
            'propriétaire' => null === $organization->ownerId() ? '' : $nameOf($organization->ownerId()),
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function describeAccount(User $user, Organization $organization): array
    {
        return [
            'identifiant' => $user->userName(),
            'prénom' => (string) $user->name()->firstName,
            'nom' => (string) $user->name()->lastName,
            'e-mail' => (string) $user->contactDetails()->email,
            'téléphone' => (string) $user->contactDetails()->phoneNumber,
            'langue' => (string) $user->contactDetails()->language,
            'photo' => self::describeFile($user->picture()),
            'raison sociale de mon organisation' => (string) $organization->legalIdentity()->corporateName,
            'forme juridique de mon organisation' => (string) $organization->legalIdentity()->form,
            'langue de mon organisation' => (string) $organization->contactDetails()->language,
            'logo de mon organisation' => self::describeFile($organization->logo()),
        ];
    }

    public static function describeGeoPoint(?GeoPoint $geoPoint): string
    {
        return $geoPoint ? sprintf('%s, %s', $geoPoint->longitude, $geoPoint->latitude) : 'aucune';
    }

    /**
     * A stored file as the scenarios name it: "acme.png (png, 2048 octets)", or "aucun".
     */
    public static function describeFile(?File $file): string
    {
        return $file ? sprintf('%s (%s, %d octets)', basename($file->url), $file->extension, $file->size) : 'aucun';
    }
}
