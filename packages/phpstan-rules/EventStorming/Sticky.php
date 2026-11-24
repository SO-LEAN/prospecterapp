<?php

declare(strict_types=1);

namespace Prospecter\PHPStan\EventStorming;

/**
 * A post-it of the event storming. Its slug names it in a link to the wall. Its label is in English, from its name,
 * that of its class or role. Its feature is the title of its use case's feature, in French. A post-it of another
 * context, or of none of the context's modules, shows where a chain comes from or goes to. An external system is
 * answered by the contexts its adapters read.
 */
final readonly class Sticky implements \JsonSerializable
{
    /**
     * @param list<string> $answeredBy
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
    ) {
    }

    /**
     * @return array<string, string|list<string>|null>
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
        ];
    }
}
