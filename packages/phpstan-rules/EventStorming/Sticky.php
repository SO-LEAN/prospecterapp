<?php

declare(strict_types=1);

namespace Prospecter\PHPStan\EventStorming;

/**
 * A post-it of the event storming. Its slug names it in a link to the wall. Its label is in English, from its name,
 * that of its class or role. Its feature is the title of its use case's feature, in French. A post-it of another
 * context, or of none of the context's modules, shows where a chain comes from or goes to. An external system is
 * answered by the contexts its adapters read, and by the published services they take. A published service is read by
 * what takes it in the other contexts.
 */
final readonly class Sticky implements \JsonSerializable
{
    /**
     * @param list<string>    $answeredBy
     * @param list<Elsewhere> $answers
     * @param list<Elsewhere> $readBy
     */
    public function __construct(
        public string $id,
        public string $slug,
        public StickyType $type,
        public string $label,
        public ?string $feature,
        public string $name,
        public ?string $class,
        public string $context,
        public ?string $module,
        public ?string $detail = null,
        public array $answeredBy = [],
        public array $answers = [],
        public array $readBy = [],
    ) {
    }

    public function isAUseCase(): bool
    {
        return \in_array($this->type, [StickyType::Command, StickyType::ReadModel], true);
    }

    /**
     * @return array<string, string|list<string>|list<Elsewhere>|null>
     */
    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'type' => $this->type->value,
            'label' => $this->label,
            'feature' => $this->feature,
            'name' => $this->name,
            'class' => $this->class,
            'context' => $this->context,
            'module' => $this->module,
            'detail' => $this->detail,
            'answeredBy' => $this->answeredBy,
            'answers' => $this->answers,
            'readBy' => $this->readBy,
        ];
    }
}
