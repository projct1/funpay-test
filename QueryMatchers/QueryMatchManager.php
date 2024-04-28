<?php

namespace FpDbTest\QueryMatchers;

use FpDbTest\Enums\CustomQueryParamModifiersEnum;

class QueryMatchManager
{
    protected int $offset = 0;
    protected string $query = '';
    /** @var QueryMatchItem[] $matches массив результата работы preg_match_all */
    protected array $matches = [];

    public function __construct(array $matches, array $args)
    {
        foreach ($matches[0] as $index => $match) {
            $this->matches[$index] = new QueryMatchItem(
                $match[0],
                $match[1],
                $matches[1][$index][0] ?? null,
                array_slice(
                    $args,
                    $index ? count($this->matches[$index-1]->values) + $index - 1 : $index,
                    substr_count($match[0], CustomQueryParamModifiersEnum::default->value)
                )
            );
        }
    }

    public function setQuery(string $query): void
    {
        $this->query = $query;
    }

    public function getQuery(): string
    {
        return $this->query;
    }

    public function getMatches(): array
    {
        return $this->matches;
    }

    public function replaceMatch(QueryMatchItem $item): void
    {
        $this->query = substr_replace($this->query, $item->values[0], $item->position + $this->offset, strlen($item->match));
        $this->offset += strlen($item->values[0]) - strlen($item->match);
    }
}
