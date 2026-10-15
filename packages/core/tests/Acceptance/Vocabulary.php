<?php

declare(strict_types=1);

namespace Tests\Core\Acceptance;

use Prospecter\Core\Entity\File;
use Prospecter\Core\Entity\GeoPoint;
use Prospecter\Core\Entity\Organization;
use Prospecter\Core\Entity\User;

/**
 * The words of the scenarios: French labels of the Gherkin tables, and how each one reads the
 * business objects, so that every feature speaks the same language.
 */
final class Vocabulary
{
    /** Organization labels => request parameter. */
    public const array ORGANIZATION = [
        'raison sociale' => 'corporateName',
        'forme juridique' => 'form',
        'type' => 'type',
        'e-mail' => 'email',
        'téléphone' => 'phoneNumber',
        'langue' => 'language',
        'rue' => 'street',
        'code postal' => 'postalCode',
        'ville' => 'city',
        'pays' => 'country',
        'observations' => 'observations',
        'logo' => 'logo',
        'holding' => 'holdBy',
    ];

    /** Account labels => request parameter. */
    public const array ACCOUNT = [
        'identifiant' => 'userName',
        'prénom' => 'firstName',
        'nom' => 'lastName',
        'e-mail' => 'email',
        'téléphone' => 'phoneNumber',
        'langue' => 'language',
        'photo' => 'picture',
        'raison sociale de mon organisation' => 'organizationCorporateName',
        'forme juridique de mon organisation' => 'organizationForm',
        'logo de mon organisation' => 'organizationLogo',
    ];

    /**
     * Request parameters from a table of labels; labels left out are absent (null), an empty cell too.
     *
     * @param array<string, string> $table
     * @param array<string, string> $labels
     *
     * @return array<string, string|null>
     */
    public static function parameters(array $table, array $labels): array
    {
        $parameters = array_fill_keys(array_values($labels), null);

        foreach ($table as $label => $value) {
            $parameter = $labels[$label] ?? throw new \InvalidArgumentException(sprintf('Unknown label "%s", expected one of: %s', $label, implode(', ', array_keys($labels))));
            $parameters[$parameter] = '' === $value ? null : $value;
        }

        return $parameters;
    }

    /**
     * What a scenario can say about a stored organization, by label.
     *
     * @return array<string, string>
     */
    public static function describeOrganization(Organization $organization): array
    {
        $address = $organization->getAddress();
        $geoPoint = $organization->getGeoPoint();

        return [
            'raison sociale' => (string) $organization->getCorporateName(),
            'forme juridique' => (string) $organization->getForm(),
            'type' => (string) $organization->getType(),
            'e-mail' => (string) $organization->getEmail(),
            'téléphone' => (string) $organization->getPhoneNumber(),
            'langue' => (string) $organization->getLanguage(),
            'rue' => (string) $address?->street,
            'code postal' => (string) $address?->postalCode,
            'ville' => (string) $address?->city,
            'pays' => (string) $address?->country,
            'observations' => (string) $organization->getObservations(),
            'position' => self::describeGeoPoint($geoPoint),
            'logo' => self::describeFile($organization->getLogo()),
            'holding' => (string) $organization->getHoldBy()?->getCorporateName(),
            'propriétaire' => (string) $organization->getOwnedBy()?->getCorporateName(),
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function describeAccount(User $user, Organization $organization): array
    {
        return [
            'identifiant' => $user->getUserName(),
            'prénom' => (string) $user->getFirstName(),
            'nom' => (string) $user->getLastName(),
            'e-mail' => (string) $user->getEmail(),
            'téléphone' => (string) $user->getPhoneNumber(),
            'langue' => (string) $user->getLanguage(),
            'photo' => self::describeFile($user->getPicture()),
            'raison sociale de mon organisation' => (string) $organization->getCorporateName(),
            'forme juridique de mon organisation' => (string) $organization->getForm(),
            'langue de mon organisation' => (string) $organization->getLanguage(),
            'logo de mon organisation' => self::describeFile($organization->getLogo()),
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
