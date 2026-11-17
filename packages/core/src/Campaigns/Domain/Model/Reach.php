<?php

declare(strict_types=1);

namespace Prospecter\Core\Campaigns\Domain\Model;

/**
 * How a contact is reached, as PersonalData tells it without its details (ADR 0141): the automatic tags it sets.
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
