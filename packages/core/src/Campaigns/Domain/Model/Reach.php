<?php

declare(strict_types=1);

namespace Prospecter\Core\Campaigns\Domain\Model;

/**
 * How a contact is reached, as PersonalData tells it without the details themselves (ADR 0141). It sets the contact's
 * automatic tags.
 */
final readonly class Reach
{
    private const array TAGS = ['email' => '#EMAIL', 'phone' => '#PHONE', 'mobile' => '#SMS', 'linkedIn' => '#LINKEDIN'];

    public function __construct(
        public bool $email,
        public bool $phone,
        public bool $mobile,
        public bool $linkedIn,
    ) {
    }

    public static function none(): self
    {
        return new self(false, false, false, false);
    }

    /**
     * Every tag a reach may set, in this order: #EMAIL, #PHONE, #SMS, #LINKEDIN.
     *
     * @return list<Tag>
     */
    #[\NoDiscard]
    public static function everyAutomaticTag(): array
    {
        return array_map(Tag::written(...), array_values(self::TAGS));
    }

    /**
     * #EMAIL with an e-mail, #PHONE with a phone, #SMS when it is a mobile, #LINKEDIN with a LinkedIn profile.
     *
     * @return list<Tag>
     */
    #[\NoDiscard]
    public function automaticTags(): array
    {
        $carried = array_filter(['email' => $this->email, 'phone' => $this->phone, 'mobile' => $this->mobile, 'linkedIn' => $this->linkedIn]);

        return array_map(static fn (string $means): Tag => Tag::written(self::TAGS[$means]), array_keys($carried));
    }
}
