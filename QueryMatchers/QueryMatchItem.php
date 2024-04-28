<?php

namespace FpDbTest\QueryMatchers;

use FpDbTest\Enums\CustomQueryParamModifiersEnum;

final class QueryMatchItem
{
    public function __construct(
        public string $match,      //один из спецификаторов, либо условный блок в сыром виде и с фигурными скобками
        public int $position,      //позиция в строке запроса (смещение от начала строки)
        public string $group,      //содержимое условного блока без фигурных скобок
        public mixed $value = null //подставляемое значение для совпадения
    ) { }

    public function isConditionalBlock(): bool
    {
        return str_contains($this->match, '{') && $this->group;
    }

    public function shouldntSkip(mixed $value): bool
    {
        return $this->value !== $value;
    }

    public function hasntSpecifier(): bool
    {
        return ! str_contains($this->match, CustomQueryParamModifiersEnum::default->value);
    }

    public function setValue(mixed $value): void
    {
        $this->value = $value;
    }
}
