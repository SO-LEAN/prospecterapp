<?php

declare(strict_types=1);

namespace Tests\Core\Acceptance;

use Prospecter\Core\Accounts\Domain\Account;
use Prospecter\Core\Accounts\Domain\Member;
use Prospecter\Core\PersonalData\Domain\Person;
use Prospecter\Core\PersonalData\Domain\PersonName;
use Prospecter\Core\Prospecting\Domain\Organization;
use Prospecter\Core\SharedKernel\Domain\Address;
use Prospecter\Core\SharedKernel\Domain\ContactDetails;
use Prospecter\Core\SharedKernel\Domain\Email;
use Prospecter\Core\SharedKernel\Domain\File;
use Prospecter\Core\SharedKernel\Domain\GeoPoint;
use Prospecter\Core\SharedKernel\Domain\LegalIdentity;
use Prospecter\Core\SharedKernel\Domain\MemberId;
use Prospecter\Core\SharedKernel\Domain\PhoneNumber;
use Prospecter\Core\SharedKernel\Domain\Uuid;

/**
 * The words of the scenarios: French labels of the Gherkin tables, how they become value objects and how
 * they describe the stored aggregates, so that every feature speaks the same language.
 */
final class Vocabulary
{
    /** The intentions by name, whatever the aggregate: the same intention has the same words everywhere. */
    private const array INTENTS = [
        'ReviseLegalIdentity' => 'réviser l\'identité',
        'ChangeContactDetails' => 'changer les coordonnées',
        'Relocate' => 'déménager',
        'WriteObservations' => 'écrire des observations',
        'AttachToHolding' => 'rattacher à une holding',
        'DetachFromHolding' => 'détacher de la holding',
        'ChangeLogo' => 'changer le logo',
        'RemoveLogo' => 'retirer le logo',
        'Rename' => 'changer le nom',
        'ChangePicture' => 'changer la photo',
        'ChangeJobTitle' => 'changer la fonction',
        'Forget' => 'effacer la fiche',
        'AddContact' => 'rattacher un contact',
        'RemoveContact' => 'retirer un contact',
        'StopProspecting' => 'arrêter de prospecter',
        'SearchByText' => 'chercher par texte',
        'ConsultTimeline' => 'consulter la timeline',
        'NoteExchange' => 'noter un échange',
    ];

    private const string MOMENT = 'Y-m-d H:i';

    /** The channels as a table names them, by their value in the requests. */
    private const array CHANNELS = [
        'Appel' => 'call',
        'SMS' => 'sms',
        'E-mail' => 'email',
        'LinkedIn' => 'linkedIn',
        'Rendez-vous' => 'meeting',
        'Autre' => 'other',
    ];

    /** The channels as a sentence names an exchange through them. */
    private const array EXCHANGES = [
        'un appel' => 'call',
        'un SMS' => 'sms',
        'un e-mail' => 'email',
        'un message LinkedIn' => 'linkedIn',
        'un rendez-vous' => 'meeting',
    ];

    private const array DIRECTIONS = [
        'reçu' => 'inbound',
        'envoyé' => 'outbound',
    ];

    private const string FRENCH_MOMENT = 'd/m/Y H:i';

    /** The roles a member works with, as an administrator gives them. */
    private const array ROLES = [
        'prospecteur' => 'ROLE_PROSPECTOR',
        'données personnelles' => 'ROLE_PERSONAL_DATA',
        'administrateur' => 'ROLE_ADMIN',
    ];

    private const array LABELS = [
        'raison sociale', 'forme juridique', 'type', 'e-mail', 'téléphone', 'langue',
        'rue', 'code postal', 'ville', 'pays', 'observations', 'holding', 'prénom', 'nom', 'fonction', 'rôles',
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
     * @param list<string> $intents names of the intentions, as the use cases answer them
     *
     * @return list<string>
     */
    public static function describeIntents(array $intents): array
    {
        return array_map(static fn (string $intent): string => self::INTENTS[$intent], $intents);
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
        return new ContactDetails(Email::orNone($values['e-mail']), PhoneNumber::orNone($values['téléphone']), $values['langue']);
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
     * @return list<string> the roles named, comma separated, as the use cases take them
     */
    public static function roles(?string $roles): array
    {
        $labels = null === $roles ? [] : array_map(trim(...), explode(',', $roles));

        return array_map(static fn (string $label): string => self::ROLES[$label] ?? throw new \LogicException(sprintf('Unknown role "%s"', $label)), $labels);
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
     * @param \Closure(Uuid): string $nameOf
     *
     * @return array<string, string>
     */
    public static function describeOrganization(Organization $organization, \Closure $nameOf, MemberId $me): array
    {
        $identity = $organization->legalIdentity();
        $contact = $organization->contactDetails();
        $address = $organization->address();

        return [
            'raison sociale' => (string) $identity->corporateName,
            'forme juridique' => (string) $identity->form,
            'type' => (string) $identity->type,
            'e-mail' => (string) $contact->email,
            'téléphone' => (string) $contact->phoneNumber?->international(),
            'langue' => (string) $contact->language,
            'rue' => (string) $address?->street,
            'code postal' => (string) $address?->postalCode,
            'ville' => (string) $address?->city,
            'pays' => (string) $address?->country,
            'observations' => (string) $organization->observations(),
            'position' => self::describeGeoPoint($organization->geoPoint()),
            'logo' => self::describeFile($organization->logo()),
            'holding' => null === $organization->holdingId() ? '' : $nameOf($organization->holdingId()),
            'compte' => $nameOf($organization->accountId()),
            'saisie par' => $organization->createdBy()->equals($me) ? 'moi' : 'un autre membre',
            'créée le' => self::describeMoment($organization->createdAt()),
            'modifiée le' => self::describeMoment($organization->modifiedAt()),
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function describeAccount(Member $me, Account $account, Person $myPerson): array
    {
        return [
            'prénom' => (string) $myPerson->name()->firstName,
            'nom' => (string) $myPerson->name()->lastName,
            'e-mail' => (string) $myPerson->contactDetails()->email,
            'téléphone' => (string) $myPerson->contactDetails()->phoneNumber?->international(),
            'langue' => (string) $myPerson->contactDetails()->language,
            'photo' => self::describeFile($myPerson->picture()),
            'raison sociale de mon organisation' => (string) $account->legalIdentity()->corporateName,
            'forme juridique de mon organisation' => (string) $account->legalIdentity()->form,
            'langue de mon organisation' => (string) $account->contactDetails()->language,
            'rue de mon organisation' => (string) $account->address()?->street,
            'ville de mon organisation' => (string) $account->address()?->city,
            'position de mon organisation' => self::describeGeoPoint($account->geoPoint()),
            'logo de mon organisation' => self::describeFile($account->logo()),
            'mon profil modifié le' => self::describeMoment($myPerson->modifiedAt()),
            'mon organisation modifiée le' => self::describeMoment($account->modifiedAt()),
        ];
    }

    public static function describeGeoPoint(?GeoPoint $geoPoint): string
    {
        return self::describePosition($geoPoint?->longitude, $geoPoint?->latitude);
    }

    public static function describePosition(?float $longitude, ?float $latitude): string
    {
        return null === $longitude || null === $latitude ? 'aucune' : sprintf('%s, %s', $longitude, $latitude);
    }

    public static function describeMoment(\DateTimeImmutable $moment): string
    {
        return $moment->format(self::MOMENT);
    }

    /**
     * A moment as a French sentence says it: "18/10/2026 à 19h00", or as a table writes it: "18/10/2026 19:00".
     */
    public static function frenchMoment(string $moment): \DateTimeImmutable
    {
        $written = (string) preg_replace('/^(\S+) à (\d{1,2})h(\d{2})$/', '$1 $2:$3', $moment);

        return \DateTimeImmutable::createFromFormat('!'.self::FRENCH_MOMENT, $written) ?: throw new \LogicException(sprintf('"%s" is no moment', $moment));
    }

    public static function describeFrenchMoment(\DateTimeImmutable $moment): string
    {
        return $moment->format(self::FRENCH_MOMENT);
    }

    public static function channel(string $label): string
    {
        return self::CHANNELS[$label] ?? throw new \LogicException(sprintf('No channel "%s"', $label));
    }

    public static function describeChannel(string $channel): string
    {
        return (string) array_search($channel, self::CHANNELS, true);
    }

    /**
     * The channel of "un appel", "un SMS"...
     */
    public static function channelOfAnExchange(string $exchange): string
    {
        return self::EXCHANGES[$exchange] ?? throw new \LogicException(sprintf('No channel for "%s"', $exchange));
    }

    public static function direction(string $label): string
    {
        return self::DIRECTIONS[$label] ?? throw new \LogicException(sprintf('No direction "%s"', $label));
    }

    public static function describeDirection(string $direction): string
    {
        return (string) array_search($direction, self::DIRECTIONS, true);
    }

    /**
     * A stored file as the scenarios name it: "acme.png (png, 2048 octets)", or "aucun".
     */
    public static function describeFile(?File $file): string
    {
        return $file ? sprintf('%s (%s, %d octets)', basename($file->url), $file->extension, $file->size) : 'aucun';
    }
}
