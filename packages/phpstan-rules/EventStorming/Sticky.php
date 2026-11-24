<?php

declare(strict_types=1);

namespace Prospecter\PHPStan\EventStorming;

/**
 * A post-it of the event storming. Its label is in the business's words, its name is that of its class or role.
 * A post-it of another context, or of none of the context's modules, shows where a chain comes from or goes to.
 */
final readonly class Sticky implements \JsonSerializable
{
    public function __construct(
        public string $id,
        public StickyType $type,
        public string $label,
        public string $name,
        public ?string $class,
        public string $context,
        public ?string $module,
        public ?string $detail = null,
    ) {
    }

    /**
     * @return array<string, string|null>
     */
    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type->value,
            'label' => $this->label,
            'name' => $this->name,
            'class' => $this->class,
            'context' => $this->context,
            'module' => $this->module,
            'detail' => $this->detail,
        ];
    }
}
